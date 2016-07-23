<?php
namespace Czim\CmsAuth\Api\OAuth\Storage;

use Carbon\Carbon;
use League\OAuth2\Server\Entity\AuthCodeEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Storage\AuthCodeInterface;

class FluentAuthCode extends AbstractFluentAdapter implements AuthCodeInterface
{
    /**
     * Get the auth code.
     *
     * @param  string $code
     *
     * @return \League\OAuth2\Server\Entity\AuthCodeEntity
     */
    public function get($code)
    {
        $table = $this->prefixTable('oauth_auth_codes');

        $result = $this->getConnection()->table($table)
            ->where("{$table}.id", $code)
            ->where("{$table}.expire_time", '>=', time())
            ->first();

        if (is_null($result)) {
            return null;
        }

        return (new AuthCodeEntity($this->getServer()))
            ->setId($result->id)
            ->setRedirectUri($result->redirect_uri)
            ->setExpireTime((int) $result->expire_time);
    }

    /**
     * Get the scopes for an access token.
     *
     * @param \League\OAuth2\Server\Entity\AuthCodeEntity $token The auth code
     *
     * @return array Array of \League\OAuth2\Server\Entity\ScopeEntity
     */
    public function getScopes(AuthCodeEntity $token)
    {
        $codeScopesTable = $this->prefixTable('oauth_auth_code_scopes');
        $scopesTable     = $this->prefixTable('oauth_scopes');

        $result = $this->getConnection()->table($codeScopesTable)
            ->select("{$scopesTable}.*")
            ->join($scopesTable, "{$codeScopesTable}.scope_id", '=', "{$scopesTable}.id")
            ->where("{$codeScopesTable}.auth_code_id", $token->getId())
            ->get();

        $scopes = [];

        foreach ($result as $scope) {
            $scopes[] = (new ScopeEntity($this->getServer()))->hydrate([
               'id' => $scope->id,
                'description' => $scope->description,
            ]);
        }

        return $scopes;
    }

    /**
     * Associate a scope with an access token.
     *
     * @param  \League\OAuth2\Server\Entity\AuthCodeEntity $token The auth code
     * @param  \League\OAuth2\Server\Entity\ScopeEntity $scope The scope
     *
     * @return void
     */
    public function associateScope(AuthCodeEntity $token, ScopeEntity $scope)
    {
        $this->getConnection()->table($this->prefixTable('oauth_auth_code_scopes'))->insert([
            'auth_code_id' => $token->getId(),
            'scope_id' => $scope->getId(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Delete an access token.
     *
     * @param  \League\OAuth2\Server\Entity\AuthCodeEntity $token The access token to delete
     *
     * @return void
     */
    public function delete(AuthCodeEntity $token)
    {
        $table = $this->prefixTable('oauth_auth_codes');

        $this->getConnection()->table($table)
        ->where("{$table}.id", $token->getId())
        ->delete();
    }

    /**
     * Create an auth code.
     *
     * @param string $token The token ID
     * @param int $expireTime Token expire time
     * @param int $sessionId Session identifier
     * @param string $redirectUri Client redirect uri
     *
     * @return void
     */
    public function create($token, $expireTime, $sessionId, $redirectUri)
    {
        $this->getConnection()->table($this->prefixTable('oauth_auth_codes'))->insert([
            'id' => $token,
            'session_id' => $sessionId,
            'redirect_uri' => $redirectUri,
            'expire_time' => $expireTime,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
