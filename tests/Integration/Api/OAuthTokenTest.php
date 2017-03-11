<?php
namespace Czim\CmsAuth\Test\Integration\Api;

use Carbon\Carbon;
use Czim\CmsAuth\Sentinel\Users\EloquentUser;
use Czim\CmsAuth\Test\ApiTestCase;
use DB;

class OAuthTokenTest extends ApiTestCase
{
    const OAUTH_TEST_ACCESS_TOKEN  = 'fnJle4ZBLbXAZ1MuuxeQZ8MJvz8MyKtxgmgI5LLB';
    const OAUTH_TEST_REFRESH_TOKEN = 'bJ4KACVW2Po38rB2LPRDY051yKZpNJ1stc6R1dhX';

    /**
     * @test
     */
    function it_issues_a_token_for_a_valid_password_grant_request()
    {
        $this->call('POST', 'cms-api/auth/issue', [
            'client_id'     => static::OAUTH_CLIENT_ID,
            'client_secret' => static::OAUTH_CLIENT_SECRET,
            'grant_type'    => 'password',
            'username'      => static::USER_ADMIN_EMAIL,
            'password'      => static::USER_ADMIN_PASSWORD,
        ]);

        $this->seeStatusCode(200)
             ->seeJson()
             ->seeJsonStructure([
                 'access_token',
                 'token_type',
                 'expires_in',
                 'refresh_token',
             ]);

        $response = $this->decodeResponseJson();

        $this->assertEquals('bearer', strtolower($response['token_type']));
        $this->assertInternalType('int', $response['expires_in']);
    }
    
    /**
     * @test
     */
    function it_issues_a_token_for_a_valid_refresh_token_request()
    {
        $this->seedTestTokens();

        $this->call('POST', 'cms-api/auth/issue', [
            'client_id'     => static::OAUTH_CLIENT_ID,
            'client_secret' => static::OAUTH_CLIENT_SECRET,
            'grant_type'    => 'refresh_token',
            'refresh_token' => static::OAUTH_TEST_REFRESH_TOKEN,
        ]);

        $this->seeStatusCode(200)
            ->seeJson()
            ->seeJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'refresh_token',
            ]);
    }
    
    /**
     * @test
     */
    function it_revokes_an_access_token()
    {
        $this->seedTestTokens();

        $this->seeInDatabase('cms_oauth_access_tokens', [ 'id' => static::OAUTH_TEST_ACCESS_TOKEN ]);

        $this->call('POST', 'cms-api/auth/revoke', [
            'token'           => static::OAUTH_TEST_ACCESS_TOKEN,
            'token_type_hint' => 'access_token',
        ], [], [], $this->transformHeadersToServerVars([
            'Authorization' => 'Bearer ' . static::OAUTH_TEST_ACCESS_TOKEN,
        ]));

        $this->seeStatusCode(200);

        $this->notSeeInDatabase('cms_oauth_access_tokens', [ 'id' => static::OAUTH_TEST_ACCESS_TOKEN ]);
    }
    
    /**
     * @test
     */
    function it_revokes_a_refresh_token()
    {
        $this->seedTestTokens();

        $this->seeInDatabase('cms_oauth_refresh_tokens', [ 'id' => static::OAUTH_TEST_REFRESH_TOKEN ]);

        $this->call('POST', 'cms-api/auth/revoke', [
            'token'           => static::OAUTH_TEST_REFRESH_TOKEN,
            'token_type_hint' => 'refresh_token',
        ], [], [], $this->transformHeadersToServerVars([
            'Authorization' => 'Bearer ' . static::OAUTH_TEST_ACCESS_TOKEN,
        ]));

        $this->seeStatusCode(200);

        $this->notSeeInDatabase('cms_oauth_refresh_tokens', [ 'id' => static::OAUTH_TEST_REFRESH_TOKEN ]);
    }
    
    /**
     * @test
     */
    function it_silently_ignores_revoking_an_invalid_token()
    {
        $this->seedTestTokens();

        $this->seeInDatabase('cms_oauth_access_tokens', [ 'id' => static::OAUTH_TEST_ACCESS_TOKEN ]);

        $this->call('POST', 'cms-api/auth/revoke', [
            'token'           => 'ANONEXISTANTTOKENTHATTOBEIGNORED',
            'token_type_hint' => 'access_token',
        ], [], [], $this->transformHeadersToServerVars([
            'Authorization' => 'Bearer ' . static::OAUTH_TEST_ACCESS_TOKEN,
        ]));

        $this->seeStatusCode(200);

        $this->seeInDatabase('cms_oauth_access_tokens', [ 'id' => static::OAUTH_TEST_ACCESS_TOKEN ]);
    }
    
    /**
     * @test
     */
    function it_denies_access_for_an_invalid_password_grant()
    {
        $this->call('POST', 'cms-api/auth/issue', [
            'client_id'     => static::OAUTH_CLIENT_ID,
            'client_secret' => static::OAUTH_CLIENT_SECRET,
            'grant_type'    => 'password',
            'username'      => static::USER_ADMIN_EMAIL,
            'password'      => 'wrong-password',
        ]);
        $this->seeStatusCode(401);

        $this->call('POST', 'cms-api/auth/issue', [
            'client_id'     => static::OAUTH_CLIENT_ID,
            'client_secret' => static::OAUTH_CLIENT_SECRET,
            'grant_type'    => 'password',
            'username'      => 'not.a.valid@user.com',
            'password'      => 'wrong-password',
        ]);
        $this->seeStatusCode(401);
    }

    /**
     * @test
     */
    function it_denies_access_for_an_invalid_refresh_token_grant()
    {
        $this->seedTestTokens();

        $this->call('POST', 'cms-api/auth/issue', [
            'client_id'     => static::OAUTH_CLIENT_ID,
            'client_secret' => static::OAUTH_CLIENT_SECRET,
            'grant_type'    => 'refresh_token',
            'refresh_token' => 'INVALIDMADEUPREFRESHTOKEN',
        ]);
        $this->seeStatusCode(400)
             ->seeJsonContains([ 'message' => 'The refresh token is invalid.' ]);
    }
    
    /**
     * @test
     */
    function it_denies_revoking_a_token_without_valid_authorisation()
    {
        $this->seedTestTokens();

        $this->seeInDatabase('cms_oauth_access_tokens', [ 'id' => static::OAUTH_TEST_ACCESS_TOKEN ]);

        $this->call('POST', 'cms-api/auth/revoke', [
            'token'           => static::OAUTH_TEST_ACCESS_TOKEN,
            'token_type_hint' => 'access_token',
        ], [], [], $this->transformHeadersToServerVars([
            'Authorization' => 'Bearer FALSEBEARERAUTHORIZATION',
        ]));

        $this->seeStatusCode(401);
    }

    // ------------------------------------------------------------------------------
    //      Helpers
    // ------------------------------------------------------------------------------

    /**
     * Seeds basic OAuth session & tokens for testing.
     *
     * @param bool $expiredAccess
     */
    protected function seedTestTokens($expiredAccess = false)
    {
        $accessExpireTime = $expiredAccess
            ? Carbon::now()->subDay()->timestamp
            : Carbon::now()->addDay()->timestamp;

        DB::table('cms_oauth_sessions')
            ->insert([
                'id'         => 1,
                'client_id'  => static::OAUTH_CLIENT_ID,
                'owner_type' => 'user',
                'owner_id'   => EloquentUser::first()->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

        DB::table('cms_oauth_access_tokens')
            ->insert([
                'id'          => static::OAUTH_TEST_ACCESS_TOKEN,
                'session_id'  => 1,
                'expire_time' => $accessExpireTime,
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ]);

        DB::table('cms_oauth_refresh_tokens')
            ->insert([
                'id'              => static::OAUTH_TEST_REFRESH_TOKEN,
                'access_token_id' => static::OAUTH_TEST_ACCESS_TOKEN,
                'expire_time'     => Carbon::now()->addWeek()->timestamp,
                'created_at'      => Carbon::now(),
                'updated_at'      => Carbon::now(),
            ]);
    }

}
