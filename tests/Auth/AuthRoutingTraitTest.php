<?php
namespace Czim\CmsAuth\Test\Auth;

use Cartalyst\Sentinel\Roles\RoleRepositoryInterface;
use Cartalyst\Sentinel\Users\UserRepositoryInterface;
use Czim\CmsAuth\Auth\Authenticator;
use Czim\CmsAuth\Http\Controllers\AuthController;
use Czim\CmsAuth\Http\Controllers\PasswordController;
use Czim\CmsAuth\Sentinel\Roles\EloquentRole;
use Czim\CmsAuth\Sentinel\Sentinel;
use Czim\CmsAuth\Sentinel\Users\EloquentUser;
use Czim\CmsAuth\Test\TestCase;
use Czim\CmsCore\Contracts\Auth\AuthRepositoryInterface;
use Mockery;

class AuthRoutingTraitTest extends TestCase
{

    /**
     * @test
     */
    function it_returns_route_definitions()
    {
        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $auth = new Authenticator($this->getMockAuthRepository());

        $route = $auth->getRouteLoginAction();
        static::assertInternalType('array', $route);
        static::assertEquals(AuthController::class . '@showLoginForm', $route['uses']);

        $route = $auth->getRouteLoginPostAction();
        static::assertInternalType('array', $route);
        static::assertEquals(AuthController::class . '@login', $route['uses']);

        $route = $auth->getRouteLogoutAction();
        static::assertInternalType('array', $route);
        static::assertEquals(AuthController::class . '@logout', $route['uses']);

        $route = $auth->getRoutePasswordEmailGetAction();
        static::assertInternalType('array', $route);
        static::assertEquals(PasswordController::class . '@showLinkRequestForm', $route['uses']);

        $route = $auth->getRoutePasswordEmailPostAction();
        static::assertInternalType('array', $route);
        static::assertEquals(PasswordController::class . '@sendResetLinkEmail', $route['uses']);

        $route = $auth->getRoutePasswordResetGetAction();
        static::assertInternalType('array', $route);
        static::assertEquals(PasswordController::class . '@showResetForm', $route['uses']);

        $route = $auth->getRoutePasswordResetPostAction();
        static::assertInternalType('array', $route);
        static::assertEquals(PasswordController::class . '@reset', $route['uses']);
    }


    // ------------------------------------------------------------------------------
    //      Helpers
    // ------------------------------------------------------------------------------

    /**
     * @param null $users
     * @param null $roles
     * @return Sentinel|\Mockery\MockInterface
     */
    protected function getMockSentinel($users = null, $roles = null)
    {
        if (null === $users) {
            $users = $this->getMockUserRepository();
            $users->shouldReceive('getModel')->andReturn(EloquentUser::class);
        }

        if (null === $roles) {
            $roles = $this->getMockRoleRepository();
            $roles->shouldReceive('getModel')->andReturn(EloquentRole::class);
        }

        /** @var Mockery\Mock $mock */
        $mock = Mockery::mock(Sentinel::class);
        $mock->shouldReceive('getUserRepository')->andReturn($users);
        $mock->shouldReceive('getRoleRepository')->andReturn($roles);

        return $mock;
    }

    /**
     * @return UserRepositoryInterface|\Mockery\MockInterface|\Mockery\Mock
     */
    protected function getMockUserRepository()
    {
        return Mockery::mock(UserRepositoryInterface::class);
    }

    /**
     * @return UserRepositoryInterface|\Mockery\MockInterface|\Mockery\Mock
     */
    protected function getMockRoleRepository()
    {
        return Mockery::mock(RoleRepositoryInterface::class);
    }

    /**
     * @return AuthRepositoryInterface|Mockery\MockInterface|\Mockery\Mock
     */
    protected function getMockAuthRepository()
    {
        return Mockery::mock(AuthRepositoryInterface::class);
    }

}
