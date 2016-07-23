<?php
namespace Czim\CmsAuth\Api\OAuth\Storage;

use Carbon\Carbon;
use League\OAuth2\Server\Entity\AccessTokenEntity;
use League\OAuth2\Server\Entity\AuthCodeEntity;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Entity\SessionEntity;
use League\OAuth2\Server\Storage\SessionInterface;

class FluentSession extends AbstractFluentAdapter implements SessionInterface
{
    /**
     * Get a session from it's identifier.
     *
     * @param string $sessionId
     *
     * @return SessionEntity|null
     */
    public function get($sessionId)
    {
        $table = $this->prefixTable('oauth_sessions');

        $result = $this->getConnection()->table($table)
                    ->where("{$table}.id", $sessionId)
                    ->first();

        if (is_null($result)) {
            return null;
        }

        return (new SessionEntity($this->getServer()))
               ->setId($result->id)
               ->setOwner($result->owner_type, $result->owner_id);
    }

    /**
     * Get a session from an access token.
     *
     * @param \League\OAuth2\Server\Entity\AccessTokenEntity $accessToken The access token
     *
     * @return \League\OAuth2\Server\Entity\SessionEntity
     */
    public function getByAccessToken(AccessTokenEntity $accessToken)
    {
        $sessionsTable = $this->prefixTable('oauth_sessions');
        $tokensTable   = $this->prefixTable('oauth_access_tokens');

        $result = $this->getConnection()->table($sessionsTable)
                ->select("{$sessionsTable}.*")
                ->join($tokensTable, "{$sessionsTable}.id", '=', "{$tokensTable}.session_id")
                ->where("{$tokensTable}.id", $accessToken->getId())
                ->first();

        if (is_null($result)) {
            return null;
        }

        return (new SessionEntity($this->getServer()))
               ->setId($result->id)
               ->setOwner($result->owner_type, $result->owner_id);
    }

    /**
     * Get a session's scopes.
     *
     * @param \League\OAuth2\Server\Entity\SessionEntity
     *
     * @return array Array of \League\OAuth2\Server\Entity\ScopeEntity
     */
    public function getScopes(SessionEntity $session)
    {
        $sessionScopesTable = $this->prefixTable('oauth_session_scopes');
        $scopesTable = $this->prefixTable('oauth_scopes');

        $result = $this->getConnection()->table($sessionScopesTable)
                  ->select("{$scopesTable}.*")
                  ->join($scopesTable, "{$sessionScopesTable}.scope_id", '=', "{$scopesTable}.id")
                  ->where("{$sessionScopesTable}.session_id", $session->getId())
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
     * Create a new session.
     *
     * @param string $ownerType Session owner's type (user, client)
     * @param string $ownerId Session owner's ID
     * @param string $clientId Client ID
     * @param string $clientRedirectUri Client redirect URI (default = null)
     *
     * @return int The session's ID
     */
    public function create($ownerType, $ownerId, $clientId, $clientRedirectUri = null)
    {
        return $this->getConnection()->table($this->prefixTable('oauth_sessions'))->insertGetId([
            'client_id' => $clientId,
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'client_redirect_uri' => $clientRedirectUri,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Associate a scope with a session.
     *
     * @param \League\OAuth2\Server\Entity\SessionEntity $session
     * @param \League\OAuth2\Server\Entity\ScopeEntity $scope The scopes ID might be an integer or string
     *
     * @return void
     */
    public function associateScope(SessionEntity $session, ScopeEntity $scope)
    {
        $this->getConnection()->table($this->prefixTable('oauth_session_scopes'))->insert([
            'session_id' => $session->getId(),
            'scope_id' => $scope->getId(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Get a session from an auth code.
     *
     * @param \League\OAuth2\Server\Entity\AuthCodeEntity $authCode The auth code
     *
     * @return \League\OAuth2\Server\Entity\SessionEntity
     */
    public function getByAuthCode(AuthCodeEntity $authCode)
    {
        $sessionsTable = $this->prefixTable('oauth_sessions');
        $codesTable    = $this->prefixTable('oauth_auth_codes');

        $result = $this->getConnection()->table($sessionsTable)
            ->select("{$sessionsTable}.*")
            ->join($codesTable, "{$sessionsTable}.id", '=', "{$codesTable}.session_id")
            ->where("{$codesTable}.id", $authCode->getId())
            ->first();

        if (is_null($result)) {
            return null;
        }

        return (new SessionEntity($this->getServer()))
               ->setId($result->id)
               ->setOwner($result->owner_type, $result->owner_id);
    }
}
