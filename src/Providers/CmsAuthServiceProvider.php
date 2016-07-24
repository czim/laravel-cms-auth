<?php
namespace Czim\CmsAuth\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Czim\CmsAuth\Console\Commands\CreateUser;
use Czim\CmsAuth\Console\Commands\DeleteUser;
use Czim\CmsAuth\Repositories\AuthRepository;
use Czim\CmsCore\Contracts\Auth\AuthRepositoryInterface;
use Czim\CmsCore\Contracts\Core\CoreInterface;
use Czim\CmsCore\Support\Enums\Component;

class CmsAuthServiceProvider extends ServiceProvider
{

    /**
     * @var CoreInterface
     */
    protected $core;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->core = app(Component::CORE);

        $this->registerSentinel()
             ->registerAuthRepository()
             ->registerCommands()
             ->publishMigrations();
    }

    /**
     * Registers authentication repository
     *
     * @return $this
     */
    protected function registerAuthRepository()
    {
        $this->app->singleton(AuthRepositoryInterface::class, function (Application $app) {
            return $app->make(AuthRepository::class);
        });

        return $this;
    }

    /**
     * Register Authorization CMS commands
     *
     * @return $this
     */
    protected function registerCommands()
    {
        $this->app->singleton('cms.commands.user-create', CreateUser::class);
        $this->app->singleton('cms.commands.user-delete', DeleteUser::class);

        $this->commands([
            'cms.commands.user-create',
            'cms.commands.user-delete',
        ]);

        return $this;
    }

    /**
     * Register Sentinel service
     *
     * @return $this
     */
    protected function registerSentinel()
    {
        $this->app->register(SentinelServiceProvider::class);

        return $this;
    }

    /**
     * @return $this
     */
    protected function publishMigrations()
    {
        $this->publishes([
            realpath(dirname(__DIR__) . '/../migrations/sentinel') => $this->getMigrationPath(),
        ], 'migrations');

        return $this;
    }

    /**
     * @return string
     */
    protected function getMigrationPath()
    {
        return database_path($this->core->config('database.migrations.path'));
    }

}
