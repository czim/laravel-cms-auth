<?php
namespace Czim\CmsAuth\Test\Repositories;

use Cartalyst\Sentinel\Roles\RoleRepositoryInterface;
use Cartalyst\Sentinel\Users\UserRepositoryInterface;
use Czim\CmsAuth\Repositories\AuthRepository;
use Czim\CmsAuth\Sentinel\Roles\EloquentRole;
use Czim\CmsAuth\Sentinel\Sentinel;
use Czim\CmsAuth\Sentinel\Users\EloquentUser;
use Czim\CmsAuth\Test\TestCase;
use Hash;
use Mockery;

class AuthRepositoryTest extends TestCase
{

    /**
     * @test
     */
    function it_returns_a_user_by_id()
    {
        $usersMock = $this->getMockUserRepository();
        $usersMock->shouldReceive('getModel')->once()->andReturn(EloquentUser::class);
        $usersMock->shouldReceive('findById')->once()->with(1)->andReturn('test');

        $sentinelMock = $this->getMockSentinel($usersMock);

        $this->app->instance('sentinel', $sentinelMock);

        $repository = $this->makeAuthRepository();

        static::assertEquals('test', $repository->getUserById(1));
    }

    /**
     * @test
     */
    function it_returns_a_user_by_name()
    {
        $usersMock = $this->getMockUserRepository();
        $usersMock->shouldReceive('getModel')->once()->andReturn(EloquentUser::class);
        $usersMock->shouldReceive('findByCredentials')->once()->with(['email' => 'test'])->andReturn('test');

        $sentinelMock = $this->getMockSentinel($usersMock);

        $this->app->instance('sentinel', $sentinelMock);

        $repository = $this->makeAuthRepository();

        static::assertEquals('test', $repository->getUserByUserName('test'));
    }

    /**
     * @test
     */
    function it_returns_all_users_without_admins_by_default()
    {
        $usersMock = $this->getMockUserRepository();
        $usersMock->shouldReceive('getModel')->once()->andReturn(EloquentUser::class);

        $sentinelMock = $this->getMockSentinel($usersMock);

        $this->app->instance('sentinel', $sentinelMock);

        EloquentUser::create([
            'email'      => 'user@test.com',
            'password'   => Hash::make('test'),
            'last_name'  => 'User',
            'first_name' => 'Test',
        ]);

        $repository = $this->makeAuthRepository();

        $users = $repository->getAllUsers();

        static::assertCount(1, $users);
    }

    /**
     * @test
     */
    function it_returns_all_users_with_admins()
    {
        $usersMock = $this->getMockUserRepository();
        $usersMock->shouldReceive('getModel')->once()->andReturn(EloquentUser::class);

        $sentinelMock = $this->getMockSentinel($usersMock);

        $this->app->instance('sentinel', $sentinelMock);

        EloquentUser::create([
            'email'      => 'user@test.com',
            'password'   => Hash::make('test'),
            'last_name'  => 'User',
            'first_name' => 'Test',
        ]);

        $repository = $this->makeAuthRepository();

        $users = $repository->getAllUsers(true);

        static::assertCount(2, $users);
    }

    /**
     * @test
     */
    function it_returns_all_users_with_a_given_role()
    {
        $usersMock = $this->getMockUserRepository();
        $usersMock->shouldReceive('getModel')->once()->andReturn(EloquentUser::class);

        $sentinelMock = $this->getMockSentinel($usersMock);

        $this->app->instance('sentinel', $sentinelMock);

        $role = $this->createRole('Test Role');

        EloquentUser::create([
            'email'      => 'user@test1.com',
            'password'   => Hash::make('test'),
            'last_name'  => 'User',
            'first_name' => '1',
        ])->roles()->attach($role->getKey());

        EloquentUser::create([
            'email'      => 'user@test2.com',
            'password'   => Hash::make('test'),
            'last_name'  => 'User',
            'first_name' => '2',
        ]);

        EloquentUser::where('is_superadmin', true)->first()
            ->roles()->attach($role->getKey());

        $repository = $this->makeAuthRepository();

        static::assertEquals(3, EloquentUser::count(), 'Setup failed');
        static::assertCount(2, $repository->getUsersForRole('test_role', true));
        static::assertCount(1, $repository->getUsersForRole('test_role'));
    }

    /**
     * @test
     */
    function it_returns_all_roles()
    {
        $this->app->instance('sentinel', $this->getMockSentinel());

        $this->createRole('Test Role');
        $this->createRole('Test Role 2');
        $this->createRole('Test Role 3');

        $repository = $this->makeAuthRepository();

        static::assertCount(3, $repository->getAllRoles());
    }

    /**
     * @test
     */
    function it_returns_all_permissions_currently_assigned()
    {
        $this->app->instance('sentinel', $this->getMockSentinel());

        $this->createRole('Test Role', null, ['test.f' => true, 'test.b' => false, 'test.c' => true]);
        $this->createRole('Test Role 2', null, ['test.d' => true]);

        EloquentUser::create([
            'email'       => 'user@test1.com',
            'password'    => Hash::make('test'),
            'last_name'   => 'User',
            'first_name'  => '1',
            'permissions' => [
                'test.e' => true,
                'test.a' => true,
            ],
        ]);

        $repository  = $this->makeAuthRepository();
        $permissions = $repository->getAllPermissions();

        static::assertCount(5, $permissions);
        static::assertEquals('test.a', head($permissions), 'Incorrect order');
        static::assertEquals('test.f', array_values($permissions)[4], 'Incorrect order');
    }

    /**
     * @test
     */
    function it_returns_all_permissions_assigned_to_a_role()
    {
        $sentinelMock = $this->getMockSentinel();

        $role = $this->createRole('Test Role', null, ['test.f' => true, 'test.b' => false, 'test.c' => true]);
        $this->createRole('Test Role 2', null, ['test.a' => true]);

        $sentinelMock->shouldReceive('findRoleBySlug')->with('test_role')->andReturn($role);

        $this->app->instance('sentinel', $sentinelMock);

        $repository = $this->makeAuthRepository();

        static::assertEquals(['test.c', 'test.f'], $repository->getAllPermissionsForRole('test_role'));
    }

    /**
     * @test
     */
    function it_returns_all_permissions_assigned_to_a_user_and_related_roles()
    {
        $role = $this->createRole('Test Role', null, ['test.f' => true, 'test.b' => false, 'test.c' => true]);
        $this->createRole('Test Role 2', null, ['test.d' => true]);

        /** @var EloquentUser $user */
        $user = EloquentUser::create([
            'email'       => 'user@test1.com',
            'password'    => Hash::make('test'),
            'last_name'   => 'User',
            'first_name'  => '1',
            'permissions' => [
                'test.e' => true,
                'test.a' => true,
            ],
        ]);

        $user->roles()->attach($role->getKey());

        $sentinelMock = $this->getMockSentinel();
        $sentinelMock->shouldReceive('findById')->with($user->getKey())->andReturn($user);
        $sentinelMock->shouldReceive('findByCredentials')->with(['email' => $user->email])->andReturn($user);

        $this->app->instance('sentinel', $sentinelMock);

        $repository  = $this->makeAuthRepository();
        $permissions = ['test.a', 'test.c', 'test.e', 'test.f'];

        static::assertEquals($permissions, $repository->getAllPermissionsForUser($user));
        static::assertEquals($permissions, $repository->getAllPermissionsForUser($user->getKey()), 'User not resolved by id');
        static::assertEquals($permissions, $repository->getAllPermissionsForUser($user->email), 'User not resolved by email');
    }

    /**
     * @test
     */
    function it_returns_empty_array_for_all_permissions_for_role_that_could_not_be_found()
    {
        $sentinelMock = $this->getMockSentinel();
        $sentinelMock->shouldReceive('findRoleBySlug')->with('test_role')->andReturn(null);
        $this->app->instance('sentinel', $sentinelMock);

        $repository = $this->makeAuthRepository();

        static::assertEquals([], $repository->getAllPermissionsForRole('test_role'));
    }

    /**
     * @test
     */
    function it_returns_empty_array_for_all_permissions_for_user_that_could_not_be_found()
    {
        $sentinelMock = $this->getMockSentinel();
        $sentinelMock->shouldReceive('findById')->andReturn(null);
        $sentinelMock->shouldReceive('findByCredentials')->andReturn(null);

        $this->app->instance('sentinel', $sentinelMock);

        $repository = $this->makeAuthRepository();

        static::assertEquals([], $repository->getAllPermissionsForUser(34));
    }

    /**
     * @test
     */
    function it_returns_whether_a_role_exists()
    {
        $role = $this->createRole('Test Role');

        $sentinelMock = $this->getMockSentinel();
        $sentinelMock->shouldReceive('findRoleBySlug')->with('test_role')->andReturn($role);
        $sentinelMock->shouldReceive('findRoleBySlug')->with('test_role_2')->andReturn(true);
        $this->app->instance('sentinel', $sentinelMock);

        $repository = $this->makeAuthRepository();

        static::assertTrue($repository->roleExists('test_role'));
        static::assertFalse($repository->roleExists('test_role_2'));
    }

    /**
     * @test
     */
    function it_returns_a_role_by_key()
    {
        $role = $this->createRole('Test Role');

        $sentinelMock = $this->getMockSentinel();
        $sentinelMock->shouldReceive('findRoleBySlug')->with('test_role')->andReturn($role);
        $sentinelMock->shouldReceive('findRoleBySlug')->with('does_not_exist')->andReturn(null);
        $this->app->instance('sentinel', $sentinelMock);

        $repository = $this->makeAuthRepository();

        static::assertSame($role, $repository->getRole('test_role'));
        static::assertNull($repository->getRole('does_not_exist'));
    }

    /**
     * @test
     */
    function it_returns_whether_a_role_is_in_use()
    {
        $usersMock = $this->getMockUserRepository();
        $usersMock->shouldReceive('getModel')->once()->andReturn(EloquentUser::class);

        $sentinelMock = $this->getMockSentinel($usersMock);

        $this->app->instance('sentinel', $sentinelMock);

        $role = $this->createRole('Test Role');
        $this->createRole('Test Role 2');

        EloquentUser::create([
            'email'      => 'user@test1.com',
            'password'   => Hash::make('test'),
            'last_name'  => 'User',
            'first_name' => '1',
        ])->roles()->attach($role->getKey());

        EloquentUser::create([
            'email'      => 'user@test2.com',
            'password'   => Hash::make('test'),
            'last_name'  => 'User',
            'first_name' => '2',
        ]);

        $repository = $this->makeAuthRepository();

        static::assertTrue($repository->roleInUse('test_role'));
        static::assertFalse($repository->roleInUse('test_role_2'));
    }

    /**
     * @test
     */
    function it_returns_whether_a_permission_is_in_use()
    {
        $this->app->instance('sentinel', $this->getMockSentinel());

        $this->createRole('Test Role', null, ['test.f' => true, 'test.b' => false, 'test.c' => true]);

        $repository = $this->makeAuthRepository();

        static::assertTrue($repository->permissionInUse('test.f'));
        static::assertFalse($repository->permissionInUse('test.does.not.exist'));
        static::assertFalse($repository->permissionInUse('test.b'));
    }


    // ------------------------------------------------------------------------------
    //      Helpers
    // ------------------------------------------------------------------------------

    /**
     * @param null $users
     * @param null $roles
     * @return Sentinel|\Mockery\MockInterface|\Mockery\Mock
     */
    protected function getMockSentinel($users = null, $roles = null)
    {
        if (null === $users) {
            $users = $this->getMockUserRepository();
            $users->shouldReceive('getModel')->once()->andReturn(EloquentUser::class);
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
     * @return AuthRepository
     */
    protected function makeAuthRepository()
    {
        return new AuthRepository();
    }

    /**
     * @param string      $name
     * @param null|string $slug
     * @param array       $permissions
     * @return EloquentRole
     */
    protected function createRole($name, $slug = null, $permissions = [])
    {
        return EloquentRole::create([
            'name'        => $name,
            'slug'        => $slug ?: snake_case($name),
            'permissions' => $permissions,
        ]);
    }
}
