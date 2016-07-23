<?php
namespace Czim\CmsAuth\Api\OAuth\Storage;

use Illuminate\Database\ConnectionResolverInterface as Resolver;
use League\OAuth2\Server\Entity\ScopeEntity;
use League\OAuth2\Server\Storage\ScopeInterface;

class FluentScope extends AbstractFluentAdapter implements ScopeInterface
{
    /*
     * Limit clients to scopes.
     *
     * @var bool
     */
    protected $limitClientsToScopes = false;

    /*
     * Limit scopes to grants.
     *
     * @var bool
     */
    protected $limitScopesToGrants = false;

    /**
     * Create a new fluent scope instance.
     *
     * @param \Illuminate\Database\ConnectionResolverInterface $resolver
     * @param bool|false $limitClientsToScopes
     * @param bool|false $limitScopesToGrants
     */
    public function __construct(Resolver $resolver, $limitClientsToScopes = false, $limitScopesToGrants = false)
    {
        parent::__construct($resolver);
        $this->limitClientsToScopes = $limitClientsToScopes;
        $this->limitScopesToGrants = $limitScopesToGrants;
    }

    /**
     * Set limit clients to scopes.
     *
     * @param bool|false $limit
     */
    public function limitClientsToScopes($limit = false)
    {
        $this->limitClientsToScopes = $limit;
    }

    /**
     * Set limit scopes to grants.
     *
     * @param bool|false $limit
     */
    public function limitScopesToGrants($limit = false)
    {
        $this->limitScopesToGrants = $limit;
    }

    /**
     * Check if clients are limited to scopes.
     *
     * @return bool|false
     */
    public function areClientsLimitedToScopes()
    {
        return $this->limitClientsToScopes;
    }

    /**
     * Check if scopes are limited to grants.
     *
     * @return bool|false
     */
    public function areScopesLimitedToGrants()
    {
        return $this->limitScopesToGrants;
    }

    /**
     * Return information about a scope.
     *
     * Example SQL query:
     *
     * <code>
     * SELECT * FROM oauth_scopes WHERE scope = :scope
     * </code>
     *
     * @param string $scope The scope
     * @param string $grantType The grant type used in the request (default = "null")
     * @param string $clientId The client id used for the request (default = "null")
     *
     * @return \League\OAuth2\Server\Entity\ScopeEntity|null If the scope doesn't exist return false
     */
    public function get($scope, $grantType = null, $clientId = null)
    {
        $scopesTable = $this->prefixTable('oauth_scopes');

        $query = $this->getConnection()->table($scopesTable)
                    ->select("{$scopesTable}.id as id", "{$scopesTable}.description as description")
                    ->where("{$scopesTable}.id", $scope);

        if ($this->limitClientsToScopes === true && !is_null($clientId)) {

            $clientScopesTable = $this->prefixTable('oauth_client_scopes');

            $query = $query->join("{$clientScopesTable}", "oauth_scopes.id", '=', "{$clientScopesTable}.scope_id")
                           ->where("{$clientScopesTable}.client_id", $clientId);
        }

        if ($this->limitScopesToGrants === true && !is_null($grantType)) {

            $grantsTable      = $this->prefixTable('oauth_grants');
            $grantScopesTable = $this->prefixTable('oauth_grant_scopes');

            $query = $query->join("{$grantScopesTable}", "{$scopesTable}.id", '=', "{$grantScopesTable}.scope_id")
                           ->join($grantsTable, "{$grantsTable}.id", '=', "{$grantScopesTable}.grant_id")
                           ->where("{$grantsTable}.id", $grantType);
        }

        $result = $query->first();

        if (is_null($result)) {
            return null;
        }

        $scope = new ScopeEntity($this->getServer());
        $scope->hydrate([
            'id' => $result->id,
            'description' => $result->description,
        ]);

        return $scope;
    }
}
