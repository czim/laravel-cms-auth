<?php
namespace Czim\CmsAuth\Test\Auth;

use Cartalyst\Sentinel\Roles\RoleRepositoryInterface;
use Cartalyst\Sentinel\Users\UserRepositoryInterface;
use Czim\CmsAuth\Auth\Authenticator;
use Czim\CmsAuth\Http\Controllers\Api\AuthController;
use Czim\CmsAuth\Sentinel\Roles\EloquentRole;
use Czim\CmsAuth\Sentinel\Sentinel;
use Czim\CmsAuth\Sentinel\Users\EloquentUser;
use Czim\CmsAuth\Test\TestCase;
use Czim\CmsCore\Contracts\Auth\AuthRepositoryInterface;
use Mockery;

class AuthApiRoutingTraitTest extends TestCase
{

    /**
     * @test
     */
    function it_returns_route_definitions()
    {
        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $auth = new Authenticator($this->getMockAuthRepository());

        $route = $auth->getApiRouteLoginAction();
        static::assertInternalType('array', $route);
        static::assertEquals(AuthController::class . '@issueAccessToken', $route['uses']);

        $route = $auth->getApiRouteLogoutAction();
        static::assertInternalType('array', $route);
        static::assertEquals(AuthController::class . '@revokeAccessToken', $route['uses']);
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
