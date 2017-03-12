<?php
namespace Czim\CmsAuth\Providers\Api;

use Czim\CmsAuth\Api\OAuth\Storage\FluentAccessToken;
use Czim\CmsAuth\Api\OAuth\Storage\FluentAuthCode;
use Czim\CmsAuth\Api\OAuth\Storage\FluentClient;
use Czim\CmsAuth\Api\OAuth\Storage\FluentRefreshToken;
use Czim\CmsAuth\Api\OAuth\Storage\FluentScope;
use Czim\CmsAuth\Api\OAuth\Storage\FluentSession;
use Czim\CmsCore\Contracts\Core\CoreInterface;
use Czim\CmsCore\Support\Enums\Component;
use Illuminate\Contracts\Container\Container as Application;
use League\OAuth2\Server\Storage\AccessTokenInterface;
use League\OAuth2\Server\Storage\AuthCodeInterface;
use League\OAuth2\Server\Storage\ClientInterface;
use League\OAuth2\Server\Storage\RefreshTokenInterface;
use League\OAuth2\Server\Storage\ScopeInterface;
use League\OAuth2\Server\Storage\SessionInterface;
use Czim\OAuth2Server\Storage\FluentStorageServiceProvider as LucaDeGasperiFluentStorageServiceProvider;

class FluentStorageServiceProvider extends LucaDeGasperiFluentStorageServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerStorageBindings($this->app);
        $this->registerInterfaceBindings($this->app);
    }

    /**
     * Bind the storage implementations to the IoC container.
     *
     * @param Application $app
     */
    public function registerStorageBindings(Application $app)
    {
        $provider = $this;

        $app->singleton(FluentAccessToken::class, function () use ($provider) {
            $storage = new FluentAccessToken($provider->app['db']);
            $storage->setConnectionName($provider->getConnectionName());

            return $storage;
        });

        $app->singleton(FluentAuthCode::class, function () use ($provider) {
            $storage = new FluentAuthCode($provider->app['db']);
            $storage->setConnectionName($provider->getConnectionName());

            return $storage;
        });

        $app->singleton(FluentClient::class, function ($app) use ($provider) {
            $limitClientsToGrants = $app['config']->get('oauth2.limit_clients_to_grants');
            $storage = new FluentClient($provider->app['db'], $limitClientsToGrants);
            $storage->setConnectionName($provider->getConnectionName());

            return $storage;
        });

        $app->singleton(FluentRefreshToken::class, function () use ($provider) {
            $storage = new FluentRefreshToken($provider->app['db']);
            $storage->setConnectionName($provider->getConnectionName());

            return $storage;
        });

        $app->singleton(FluentScope::class, function ($app) use ($provider) {
            $limitClientsToScopes = $app['config']->get('oauth2.limit_clients_to_scopes');
            $limitScopesToGrants = $app['config']->get('oauth2.limit_scopes_to_grants');
            $storage = new FluentScope($provider->app['db'], $limitClientsToScopes, $limitScopesToGrants);
            $storage->setConnectionName($provider->getConnectionName());

            return $storage;
        });

        $app->singleton(FluentSession::class, function () use ($provider) {
            $storage = new FluentSession($provider->app['db']);
            $storage->setConnectionName($provider->getConnectionName());

            return $storage;
        });
    }

    /**
     * Bind the interfaces to their implementations.
     *
     * @param Application $app
     */
    public function registerInterfaceBindings(Application $app)
    {
        $app->bind(ClientInterface::class, FluentClient::class);
        $app->bind(ScopeInterface::class, FluentScope::class);
        $app->bind(SessionInterface::class, FluentSession::class);
        $app->bind(AuthCodeInterface::class, FluentAuthCode::class);
        $app->bind(AccessTokenInterface::class, FluentAccessToken::class);
        $app->bind(RefreshTokenInterface::class, FluentRefreshToken::class);
    }

    /**
     * @return string
     */
    public function getConnectionName()
    {
        /** @var CoreInterface $core */
        $core = app(Component::CORE);

        return $core->config('database.driver');
    }
}
