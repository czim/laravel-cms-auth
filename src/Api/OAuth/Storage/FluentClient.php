<?php
namespace Czim\CmsAuth\Api\OAuth\Storage;

use Carbon\Carbon;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use League\OAuth2\Server\Entity\ClientEntity;
use League\OAuth2\Server\Entity\SessionEntity;
use League\OAuth2\Server\Storage\ClientInterface;

class FluentClient extends AbstractFluentAdapter implements ClientInterface
{
    /**
     * Limit clients to grants.
     *
     * @var bool
     */
    protected $limitClientsToGrants = false;

    /**
     * Create a new fluent client instance.
     *
     * @param \Illuminate\Database\ConnectionResolverInterface $resolver
     * @param bool $limitClientsToGrants
     */
    public function __construct(Resolver $resolver, $limitClientsToGrants = false)
    {
        parent::__construct($resolver);
        $this->limitClientsToGrants = $limitClientsToGrants;
    }

    /**
     * Check if clients are limited to grants.
     *
     * @return bool
     */
    public function areClientsLimitedToGrants()
    {
        return $this->limitClientsToGrants;
    }

    /**
     * Whether or not to limit clients to grants.
     *
     * @param bool $limit
     */
    public function limitClientsToGrants($limit = false)
    {
        $this->limitClientsToGrants = $limit;
    }

    /**
     * Get the client.
     *
     * @param string $clientId
     * @param string $clientSecret
     * @param string $redirectUri
     * @param string $grantType
     *
     * @return null|\League\OAuth2\Server\Entity\ClientEntity
     */
    public function get($clientId, $clientSecret = null, $redirectUri = null, $grantType = null)
    {
        $query = null;

        $clientsTable = $this->prefixTable('oauth_clients');
        $endpointsTable = $this->prefixTable('oauth_clients');

        if (!is_null($redirectUri) && is_null($clientSecret)) {
            $query = $this->getConnection()->table($clientsTable)
                   ->select(
                       "{$clientsTable}.id as id",
                       "{$clientsTable}.secret as secret",
                       "{$endpointsTable}.redirect_uri as redirect_uri",
                       "{$clientsTable}.name as name")
                   ->join($endpointsTable, "{$clientsTable}.id", '=', "{$endpointsTable}.client_id")
                   ->where("{$clientsTable}.id", $clientId)
                   ->where("{$endpointsTable}.redirect_uri", $redirectUri);
        } elseif (!is_null($clientSecret) && is_null($redirectUri)) {
            $query = $this->getConnection()->table($clientsTable)
                   ->select(
                       "{$clientsTable}.id as id",
                       "{$clientsTable}.secret as secret",
                       "{$clientsTable}.name as name")
                   ->where("{$clientsTable}.id", $clientId)
                   ->where("{$clientsTable}.secret", $clientSecret);
        } elseif (!is_null($clientSecret) && !is_null($redirectUri)) {
            $query = $this->getConnection()->table($clientsTable)
                   ->select(
                       "{$clientsTable}.id as id",
                       "{$clientsTable}.secret as secret",
                       "{$endpointsTable}.redirect_uri as redirect_uri",
                       "{$clientsTable}.name as name")
                   ->join($endpointsTable, "{$clientsTable}.id", '=', "{$endpointsTable}.client_id")
                   ->where("{$clientsTable}.id", $clientId)
                   ->where("{$clientsTable}.secret", $clientSecret)
                   ->where("{$endpointsTable}.redirect_uri", $redirectUri);
        }

        if ($this->limitClientsToGrants === true && !is_null($grantType)) {

            $clientGrantsTable = $this->prefixTable('oauth_client_grants');
            $grantsTable       = $this->prefixTable('oauth_grants');

            $query = $query->join($clientGrantsTable, "{$clientsTable}.id", '=', "{$clientGrantsTable}.client_id")
                   ->join($grantsTable, "{$grantsTable}.id", '=', "{$clientGrantsTable}.grant_id")
                   ->where("{$grantsTable}.id", $grantType);
        }

        $result = $query->first();

        if (is_null($result)) {
            return null;
        }

        return $this->hydrateEntity($result);
    }

    /**
     * Get the client associated with a session.
     *
     * @param  \League\OAuth2\Server\Entity\SessionEntity $session The session
     *
     * @return null|\League\OAuth2\Server\Entity\ClientEntity
     */
    public function getBySession(SessionEntity $session)
    {
        $clientsTable  = $this->prefixTable('oauth_clients');
        $sessionsTable = $this->prefixTable('oauth_sessions');

        $result = $this->getConnection()->table($clientsTable)
                ->select(
                    "{$clientsTable}.id as id",
                    "{$clientsTable}.secret as secret",
                    "{$clientsTable}.name as name")
                ->join($sessionsTable, "{$sessionsTable}.client_id", '=', "{$clientsTable}.id")
                ->where("{$sessionsTable}.id", '=', $session->getId())
                ->first();

        if (is_null($result)) {
            return null;
        }

        return $this->hydrateEntity($result);
    }

    /**
     * Create a new client.
     *
     * @param string $name The client's unique name
     * @param string $id The client's unique id
     * @param string $secret The clients' unique secret
     *
     * @return string
     */
    public function create($name, $id, $secret)
    {
        return $this->getConnection()->table($this->prefixTable('oauth_clients'))->insertGetId([
            'id' => $id,
            'name' => $name,
            'secret' => $secret,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }

    /**
     * Hydrate the entity.
     *
     * @param $result
     *
     * @return \League\OAuth2\Server\Entity\ClientEntity
     */
    protected function hydrateEntity($result)
    {
        $client = new ClientEntity($this->getServer());
        $client->hydrate([
            'id' => $result->id,
            'name' => $result->name,
            'secret' => $result->secret,
            'redirectUri' => (isset($result->redirect_uri) ? $result->redirect_uri : null),
        ]);

        return $client;
    }
}
