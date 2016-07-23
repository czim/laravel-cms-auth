<?php
namespace Czim\CmsAuth\Api\OAuth\Storage;

use Carbon\Carbon;
use League\OAuth2\Server\Entity\AbstractTokenEntity;
use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Storage\AccessTokenInterface;

class FluentAccessToken extends AbstractFluentAdapter implements AccessTokenInterface
{
    /**
     * Get an instance of Entities\AccessToken.
     *
     * @param string $token The access token
     *
     * @return null|AbstractTokenEntity
     */
    public function get($token)
    {
        $table = $this->prefixTable('oauth_access_tokens');

        $result = $this->getConnection()->table($table)
                ->where("{$table}.id", $token)
                ->first();

        if (is_null($result)) {
            return null;
        }

        return (new AccessTokenEntity($this->getServer()))
               ->setId($result->id)
               ->setExpireTime((int) $result->expire_time);
    }

    /**
     * Get the scopes for an access token.
     *
     * @param \League\OAuth2\Server\Entity\AccessTokenEntity $token The access token
     *
     * @return array Array of \League\OAuth2\Server\Entity\ScopeEntity
     */
    public function getScopes(AccessTokenEntity $token)
    {
        $tokenScopesTable = $this->prefixTable('oauth_access_token_scopes');
        $scopesTable      = $this->prefixTable('oauth_scopes');

        $result = $this->getConnection()->table($tokenScopesTable)
                ->select("{$scopesTable}.*")
                ->join($scopesTable, "{$tokenScopesTable}.scope_id", '=', "{$scopesTable}.id")
                ->where("{$tokenScopesTable}.access_token_id", $token->getId())
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
     * Creates a new access token.
     *
     * @param string $token The access token
     * @param int $expireTime The expire time expressed as a unix timestamp
     * @param string|int $sessionId The session ID
     *
     * @return \League\OAuth2\Server\Entity\AccessTokenEntity
     */
    public function create($token, $expireTime, $sessionId)
    {
        $this->getConnection()->table($this->prefixTable('oauth_access_tokens'))->insert([
            'id' => $token,
            'expire_time' => $expireTime,
            'session_id' => $sessionId,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return (new AccessTokenEntity($this->getServer()))
               ->setId($token)
               ->setExpireTime((int) $expireTime);
    }

    /**
     * Associate a scope with an access token.
     *
     * @param \League\OAuth2\Server\Entity\AccessTokenEntity $token The access token
     * @param \League\OAuth2\Server\Entity\ScopeEntity $scope The scope
     *
     * @return void
     */
    public function associateScope(AccessTokenEntity $token, ScopeEntity $scope)
    {
        $this->getConnection()->table($this->prefixTable('oauth_access_token_scopes'))->insert([
            'access_token_id' => $token->getId(),
            'scope_id' => $scope->getId(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Delete an access token.
     *
     * @param \League\OAuth2\Server\Entity\AccessTokenEntity $token The access token to delete
     *
     * @return void
     */
    public function delete(AccessTokenEntity $token)
    {
        $table = $this->prefixTable('oauth_access_tokens');

        $this->getConnection()->table($table)
        ->where("{$table}.id", $token->getId())
        ->delete();
    }
}
