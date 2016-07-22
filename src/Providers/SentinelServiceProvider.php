<?php
namespace Czim\CmsAuth\Providers;

use Cartalyst\Sentinel\Laravel\SentinelServiceProvider as CartalystSentinelServiceProvider;
use Czim\CmsAuth\Sentinel\Repositories\IlluminateUserRepository;
use Czim\CmsAuth\Sentinel\Sentinel;

class SentinelServiceProvider extends CartalystSentinelServiceProvider
{

    /**
     * Override to exclude all the resource we don't need.
     * We only need a config inclusion.
     *
     * {@inheritdoc}
     */
    protected function prepareResources()
    {
        $sentinelConfig = realpath(dirname(__DIR__) . '/../config/cartalyst.sentinel.php');
        $this->app['config']['cartalyst.sentinel'] = require $sentinelConfig;
    }

    /**
     * Overrides parent so as not to use the alias.
     *
     * {@inheritdoc}
     */
    protected function registerSentinel()
    {
        $this->app->singleton('sentinel', function ($app) {
            $sentinel = new Sentinel(
                $app['sentinel.persistence'],
                $app['sentinel.users'],
                $app['sentinel.roles'],
                $app['sentinel.activations'],
                $app['events']
            );

            if (isset($app['sentinel.checkpoints'])) {
                foreach ($app['sentinel.checkpoints'] as $key => $checkpoint) {
                    $sentinel->addCheckpoint($key, $checkpoint);
                }
            }

            $sentinel->setActivationRepository($app['sentinel.activations']);
            $sentinel->setReminderRepository($app['sentinel.reminders']);

            $sentinel->setRequestCredentials(function () use ($app) {
                $request = $app['request'];

                $login    = $request->getUser();
                $password = $request->getPassword();

                if ($login === null && $password === null) {
                    return null;
                }

                return compact('login', 'password');
            });

            $sentinel->creatingBasicResponse(function () {
                $headers = ['WWW-Authenticate' => 'Basic'];

                return response('Invalid credentials.', 401, $headers);
            });

            return $sentinel;
        });

        // no alias
    }

    /**
     * Overrides the parent so we can bind our own UserRepository
     *
     * {@inheritdoc}
     */
    protected function registerUsers()
    {
        $this->registerHasher();

        $this->app->singleton('sentinel.users', function ($app) {
            $config = $app['config']->get('cartalyst.sentinel');

            $users = array_get($config, 'users.model');
            $roles = array_get($config, 'roles.model');
            $persistences = array_get($config, 'persistences.model');
            $permissions = array_get($config, 'permissions.class');

            if (class_exists($roles) && method_exists($roles, 'setUsersModel')) {
                forward_static_call_array([$roles, 'setUsersModel'], [$users]);
            }

            if (class_exists($persistences) && method_exists($persistences, 'setUsersModel')) {
                forward_static_call_array([$persistences, 'setUsersModel'], [$users]);
            }

            if (class_exists($users) && method_exists($users, 'setPermissionsClass')) {
                forward_static_call_array([$users, 'setPermissionsClass'], [$permissions]);
            }

            return app(IlluminateUserRepository::class, [ $app['sentinel.hasher'], $app['events'], $users ]);
        });
    }
}
