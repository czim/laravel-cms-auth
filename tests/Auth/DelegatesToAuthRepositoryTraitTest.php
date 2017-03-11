<?php
namespace Czim\CmsAuth\Test\Auth;

use Czim\CmsAuth\Auth\Authenticator;
use Czim\CmsAuth\Test\TestCase;
use Czim\CmsCore\Contracts\Auth\AuthRepositoryInterface;
use Mockery;

class DelegatesToAuthRepositoryTraitTest extends TestCase
{

    /**
     * @test
     */
    function it_delegates_get_user_by_id()
    {
        $authMock = $this->getMockAuthRepository();
        $authMock->shouldReceive('getUserById')->once()->with(9)->andReturn('test');

        $auth = new Authenticator($authMock);

        static::assertEquals('test', $auth->getUserById(9));
    }

    /**
     * @test
     */
    function it_delegates_get_user_by_user_name()
    {
        $authMock = $this->getMockAuthRepository();
        $authMock->shouldReceive('getUserByUserName')->once()->with('name')->andReturn('test');

        $auth = new Authenticator($authMock);

        static::assertEquals('test', $auth->getUserByUserName('name'));
    }

    /**
     * @test
     */
    function it_delegates_get_all_users()
    {
        $authMock = $this->getMockAuthRepository();
        $authMock->shouldReceive('getAllUsers')->once()->andReturn('test');

        $auth = new Authenticator($authMock);

        static::assertEquals('test', $auth->getAllUsers());
    }

    /**
     * @test
     */
    function it_delegates_get_users_for_role()
    {
        $authMock = $this->getMockAuthRepository();
        $authMock->shouldReceive('getUsersForRole')->once()->with('test', true)->andReturn('test');

        $auth = new Authenticator($authMock);

        static::assertEquals('test', $auth->getUsersForRole('test', true));
    }

    /**
     * @test
     */
    function it_delegates_get_all_roles()
    {
        $authMock = $this->getMockAuthRepository();
        $authMock->shouldReceive('getAllRoles')->once()->andReturn('test');

        $auth = new Authenticator($authMock);

        static::assertEquals('test', $auth->getAllRoles());
    }

    /**
     * @test
     */
    function it_delegates_get_all_permissions()
    {
        $authMock = $this->getMockAuthRepository();
        $authMock->shouldReceive('getAllPermissions')->once()->andReturn('test');

        $auth = new Authenticator($authMock);

        static::assertEquals('test', $auth->getAllPermissions());
    }

    /**
     * @test
     */
    function it_delegates_get_all_permissions_for_role()
    {
        $authMock = $this->getMockAuthRepository();
        $authMock->shouldReceive('getAllPermissionsForRole')->once()->with('test')->andReturn('test');

        $auth = new Authenticator($authMock);

        static::assertEquals('test', $auth->getAllPermissionsForRole('test'));
    }

    /**
     * @test
     */
    function it_delegates_get_all_permissions_for_user()
    {
        $authMock = $this->getMockAuthRepository();
        $authMock->shouldReceive('getAllPermissionsForUser')->once()->with('test')->andReturn('test');

        $auth = new Authenticator($authMock);

        static::assertEquals('test', $auth->getAllPermissionsForUser('test'));
    }

    /**
     * @test
     */
    function it_delegates_get_role()
    {
        $authMock = $this->getMockAuthRepository();
        $authMock->shouldReceive('getRole')->once()->with('test')->andReturn('test');

        $auth = new Authenticator($authMock);

        static::assertEquals('test', $auth->getRole('test'));
    }

    /**
     * @test
     */
    function it_delegates_role_exists()
    {
        $authMock = $this->getMockAuthRepository();
        $authMock->shouldReceive('roleExists')->once()->with('test')->andReturn(true);

        $auth = new Authenticator($authMock);

        static::assertTrue($auth->roleExists('test'));
    }

    /**
     * @test
     */
    function it_delegates_role_in_use()
    {
        $authMock = $this->getMockAuthRepository();
        $authMock->shouldReceive('roleInUse')->once()->with('test')->andReturn(true);

        $auth = new Authenticator($authMock);

        static::assertTrue($auth->roleInUse('test'));
    }

    /**
     * @test
     */
    function it_delegates_permission_in_use()
    {
        $authMock = $this->getMockAuthRepository();
        $authMock->shouldReceive('permissionInUse')->once()->with('test')->andReturn(true);

        $auth = new Authenticator($authMock);

        static::assertTrue($auth->permissionInUse('test'));
    }


    /**
     * @return AuthRepositoryInterface|Mockery\MockInterface
     */
    protected function getMockAuthRepository()
    {
        return Mockery::mock(AuthRepositoryInterface::class);
    }

}
