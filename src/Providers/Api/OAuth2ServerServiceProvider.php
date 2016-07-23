<?php
namespace Czim\CmsAuth\Providers\Api;

use Illuminate\Contracts\Container\Container as Application;
use LucaDegasperi\OAuth2Server\OAuth2ServerServiceProvider as LucaDegasperiOAuth2ServerServiceProvider;

class OAuth2ServerServiceProvider extends LucaDegasperiOAuth2ServerServiceProvider
{
    /**
     * Boot the service provider.
     */
    public function boot()
    {
        $this->setupConfig($this->app);
    }

    /**
     * Setup the config.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     */
    protected function setupConfig(Application $app)
    {
        $this->mergeConfigFrom(realpath(dirname(__DIR__) . '/../../config/oauth2.php'), 'oauth2');
    }

}
