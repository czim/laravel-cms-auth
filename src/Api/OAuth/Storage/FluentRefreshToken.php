<?php
namespace Czim\CmsAuth\Api\OAuth\Storage;

use Carbon\Carbon;
use League\OAuth2\Server\Entity\RefreshTokenEntity;
use League\OAuth2\Server\Storage\RefreshTokenInterface;

class FluentRefreshToken extends AbstractFluentAdapter implements RefreshTokenInterface
{
    /**
     * Return a new instance of \League\OAuth2\Server\Entity\RefreshTokenEntity.
     *
     * @param string $token
     *
     * @return \League\OAuth2\Server\Entity\RefreshTokenEntity
     */
    public function get($token)
    {
        $table = $this->prefixTable('oauth_refresh_tokens');

        $result = $this->getConnection()->table($table)
                ->where("{$table}.id", $token)
                ->where("{$table}.expire_time", '>=', time())
                ->first();

        if (is_null($result)) {
            return null;
        }

        return (new RefreshTokenEntity($this->getServer()))
               ->setId($result->id)
               ->setAccessTokenId($result->access_token_id)
               ->setExpireTime((int) $result->expire_time);
    }

    /**
     * Create a new refresh token_name.
     *
     * @param  string $token
     * @param  int $expireTime
     * @param  string $accessToken
     *
     * @return \League\OAuth2\Server\Entity\RefreshTokenEntity
     */
    public function create($token, $expireTime, $accessToken)
    {
        $this->getConnection()->table($this->prefixTable('oauth_refresh_tokens'))->insert([
            'id' => $token,
            'expire_time' => $expireTime,
            'access_token_id' => $accessToken,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return (new RefreshTokenEntity($this->getServer()))
               ->setId($token)
               ->setAccessTokenId($accessToken)
               ->setExpireTime((int) $expireTime);
    }

    /**
     * Delete the refresh token.
     *
     * @param  \League\OAuth2\Server\Entity\RefreshTokenEntity $token
     *
     * @return void
     */
    public function delete(RefreshTokenEntity $token)
    {
        $this->getConnection()->table($this->prefixTable('oauth_refresh_tokens'))
        ->where('id', $token->getId())
        ->delete();
    }
}
