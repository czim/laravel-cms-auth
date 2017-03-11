<?php
namespace Czim\CmsAuth\Test\Auth;

use Cartalyst\Sentinel\Roles\RoleRepositoryInterface;
use Cartalyst\Sentinel\Users\UserRepositoryInterface;
use Czim\CmsAuth\Auth\Authenticator;
use Czim\CmsAuth\Sentinel\Roles\EloquentRole;
use Czim\CmsAuth\Sentinel\Sentinel;
use Czim\CmsAuth\Sentinel\Users\EloquentUser;
use Czim\CmsAuth\Test\TestCase;
use Czim\CmsCore\Contracts\Auth\AuthRepositoryInterface;
use Czim\CmsCore\Events\Auth\CmsRolesChanged;
use Czim\CmsCore\Events\Auth\CmsUserLoggedIn;
use Czim\CmsCore\Events\Auth\CmsUserLoggedOut;
use Czim\CmsCore\Events\Auth\CmsUserPermissionsChanged;
use Hash;
use Mockery;

class AuthenticatorTest extends TestCase
{

    /**
     * @test
     */
    function it_returns_its_version()
    {
        $auth = new Authenticator($this->getMockAuthRepository());

        static::assertEquals(Authenticator::VERSION, $auth->version());
    }

    /**
     * @test
     */
    function it_returns_whether_a_user_is_logged_in()
    {
        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $auth = new Authenticator($this->getMockAuthRepository());

        $sentinelMock->shouldReceive('check')->twice()->andReturn(false, true);

        static::assertFalse($auth->check());
        static::assertTrue($auth->check());
    }

    /**
     * @test
     */
    function it_returns_a_user_if_logged_in()
    {
        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $auth = new Authenticator($this->getMockAuthRepository());

        $sentinelMock->shouldReceive('check')->twice()->andReturn(false, true);
        $sentinelMock->shouldReceive('getUser')->once()->andReturn('test');

        static::assertFalse($auth->user());
        static::assertEquals('test', $auth->user());
    }

    /**
     * @test
     */
    function it_whether_an_admin_is_logged_in()
    {
        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $user = $this->getMockUser();

        $auth = new Authenticator($this->getMockAuthRepository());

        $sentinelMock->shouldReceive('check')->twice()->andReturn(false, true);
        $sentinelMock->shouldReceive('getUser')->once()->andReturn($user);
        $user->shouldReceive('isAdmin')->once()->andReturn(true);

        static::assertFalse($auth->admin());
        static::assertTrue($auth->admin());
    }

    /**
     * @test
     */
    function it_logs_in_a_user_with_valid_credentials()
    {
        $this->expectsEvents(CmsUserLoggedIn::class);

        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $user = $this->getMockUser();

        $auth = new Authenticator($this->getMockAuthRepository());

        $sentinelMock->shouldReceive('authenticate')
            ->with(['email' => 'test@test.nl', 'password' => 'testing'], true)
            ->andReturn($user);

        static::assertTrue($auth->login('test@test.nl', 'testing', true));
    }

    /**
     * @test
     */
    function it_does_not_log_in_a_user_with_invalid_credentials()
    {
        $this->doesntExpectEvents(CmsUserLoggedIn::class);

        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $auth = new Authenticator($this->getMockAuthRepository());

        $sentinelMock->shouldReceive('authenticate')
            ->with(['email' => 'test@test.nl', 'password' => 'testing'], true)
            ->andReturn(false);

        static::assertFalse($auth->login('test@test.nl', 'testing', true));
    }

    /**
     * @test
     */
    function it_performs_a_stateless_loging_for_a_user_with_valid_credentials()
    {
        $this->expectsEvents(CmsUserLoggedIn::class);

        $users = $this->getMockUserRepository();
        $users->shouldReceive('getModel')->andReturn(EloquentUser::class);
        $users->shouldReceive('recordLogin')->once();

        $sentinelMock = $this->getMockSentinel($users);
        $this->app->instance('sentinel', $sentinelMock);

        $user = $this->getMockUser();

        $auth = new Authenticator($this->getMockAuthRepository());

        $sentinelMock->shouldReceive('stateless')
            ->with(['email' => 'test@test.nl', 'password' => 'testing'])
            ->andReturn($user);

        static::assertTrue($auth->stateless('test@test.nl', 'testing'));
    }

    /**
     * @test
     */
    function it_does_allow_stateless_login_for_a_user_with_invalid_credentials()
    {
        $this->doesntExpectEvents(CmsUserLoggedIn::class);

        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $auth = new Authenticator($this->getMockAuthRepository());

        $sentinelMock->shouldReceive('stateless')
            ->with(['email' => 'test@test.nl', 'password' => 'testing'])
            ->andReturn(false);

        static::assertFalse($auth->stateless('test@test.nl', 'testing'));
    }

    /**
     * @test
     */
    function it_forces_a_user_login_without_verification()
    {
        $this->expectsEvents(CmsUserLoggedIn::class);

        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $user = $this->getMockUser();

        $auth = new Authenticator($this->getMockAuthRepository());

        $sentinelMock->shouldReceive('authenticate')
            ->twice()
            ->with($user, true)
            ->andReturn($user, false);

        static::assertTrue($auth->forceUser($user, true));
        static::assertFalse($auth->forceUser($user));
    }
    
    /**
     * @test
     */
    function it_forces_a_stateless_user_loging_without_verification()
    {
        $this->expectsEvents(CmsUserLoggedIn::class);

        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $user = $this->getMockUser();

        $auth = new Authenticator($this->getMockAuthRepository());

        $sentinelMock->shouldReceive('authenticate')
            ->twice()
            ->with($user, false, false)
            ->andReturn($user, false);

        static::assertTrue($auth->forceUserStateless($user));
        static::assertFalse($auth->forceUserStateless($user));
    }

    /**
     * @test
     */
    function it_logs_out_a_user_if_logged_in()
    {
        $this->expectsEvents(CmsUserLoggedOut::class);

        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $user = $this->getMockUser();

        $auth = new Authenticator($this->getMockAuthRepository());

        $sentinelMock->shouldReceive('getUser')->twice()->andReturn($user, false);
        $sentinelMock->shouldReceive('logout')->once()->andReturn(true);

        static::assertTrue($auth->logout());
        static::assertFalse($auth->logout());
    }

    /**
     * @test
     */
    function it_returns_whether_current_user_has_a_role()
    {
        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $user = $this->getMockUser();

        $auth = new Authenticator($this->getMockAuthRepository());

        $sentinelMock->shouldReceive('check')->twice()->andReturn($user, false);
        $sentinelMock->shouldReceive('getUser')->once()->andReturn($user);
        $user->shouldReceive('hasRole')->with('testing.role')->once()->andReturn(true);

        static::assertTrue($auth->hasRole('testing.role'));
        static::assertFalse($auth->hasRole('testing.role'));
    }

    /**
     * @test
     */
    function it_returns_whether_user_has_permission()
    {
        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $user = $this->getMockUser();

        $auth = new Authenticator($this->getMockAuthRepository());

        $sentinelMock->shouldReceive('check')->twice()->andReturn($user, false);
        $sentinelMock->shouldReceive('getUser')->once()->andReturn($user);
        $user->shouldReceive('isAdmin')->once()->andReturn(false);
        $user->shouldReceive('can')->with('testing.permission', false)->once()->andReturn(true);

        static::assertTrue($auth->can('testing.permission'));
        static::assertFalse($auth->can('testing.permission'));
    }

    /**
     * @test
     */
    function it_always_reports_that_a_user_has_permission_if_they_are_admin()
    {
        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $user = $this->getMockUser();

        $auth = new Authenticator($this->getMockAuthRepository());

        $sentinelMock->shouldReceive('check')->once()->andReturn($user);
        $sentinelMock->shouldReceive('getUser')->once()->andReturn($user);
        $user->shouldReceive('isAdmin')->once()->andReturn(true);
        $user->shouldNotReceive('can');

        static::assertTrue($auth->can('testing.permission'));
    }

    /**
     * @test
     */
    function it_returns_whether_user_has_any_of_permissions()
    {
        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $user = $this->getMockUser();

        $auth = new Authenticator($this->getMockAuthRepository());

        $sentinelMock->shouldReceive('check')->twice()->andReturn($user, false);
        $sentinelMock->shouldReceive('getUser')->once()->andReturn($user);
        $user->shouldReceive('isAdmin')->once()->andReturn(false);
        $user->shouldReceive('can')->with(['testing.permission', 'testing.two'], true)->once()->andReturn(true);

        static::assertTrue($auth->canAnyOf(['testing.permission', 'testing.two']));
        static::assertFalse($auth->canAnyOf(['testing.permission', 'testing.two']));
    }


    // ------------------------------------------------------------------------------
    //      Assigning Roles
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_assigns_a_role_to_a_user()
    {
        $this->expectsEvents(CmsUserPermissionsChanged::class);

        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        /** @var EloquentUser $user */
        $user = EloquentUser::create([
            'email'      => 'user@test1.com',
            'password'   => Hash::make('test'),
            'last_name'  => 'User',
            'first_name' => '1',
        ]);
        $role = $this->createRole('Test Role');

        $auth = new Authenticator($this->getMockAuthRepository());

        $sentinelMock->shouldReceive('findRoleBySlug')->once()->with('test_role')->andReturn($role);

        static::assertTrue($auth->assign('test_role', $user));
        static::assertEquals(1, $user->fresh()->roles()->count());

        // Considers it a success if role is already assigned
        static::assertTrue($auth->assign('test_role', $user));
    }
    /**
     * @test
     */
    function it_assigns_multiple_roles_to_a_user_at_once()
    {
        $this->expectsEvents(CmsUserPermissionsChanged::class);

        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        /** @var EloquentUser $user */
        $user = EloquentUser::create([
            'email'      => 'user@test1.com',
            'password'   => Hash::make('test'),
            'last_name'  => 'User',
            'first_name' => '1',
        ]);
        $roleA = $this->createRole('Test Role');
        $roleB = $this->createRole('Test Role2');

        $auth = new Authenticator($this->getMockAuthRepository());

        $sentinelMock->shouldReceive('findRoleBySlug')->with('test_role')->andReturn($roleA);
        $sentinelMock->shouldReceive('findRoleBySlug')->once()->with('test_role2')->andReturn($roleB);
        $sentinelMock->shouldReceive('findRoleBySlug')->once()->with('test_role_x')->andReturn(false);

        static::assertTrue($auth->assign(['test_role', 'test_role2'], $user));
        static::assertEquals(2, $user->fresh()->roles()->count());

        // Should return false if one of the roles does not exist
        static::assertFalse($auth->assign(['test_role', 'test_role_x'], $user));
    }

    /**
     * @test
     */
    function it_unassigns_a_role_from_a_user()
    {
        $this->expectsEvents(CmsUserPermissionsChanged::class);

        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        /** @var EloquentUser $user */
        $user = EloquentUser::create([
            'email'      => 'user@test1.com',
            'password'   => Hash::make('test'),
            'last_name'  => 'User',
            'first_name' => '1',
        ]);
        $role = $this->createRole('Test Role');
        $user->roles()->attach($role['id']);

        $auth = new Authenticator($this->getMockAuthRepository());

        $sentinelMock->shouldReceive('findRoleBySlug')->once()->with('test_role')->andReturn($role);

        static::assertTrue($auth->unassign('test_role', $user));
        static::assertEquals(0, $user->fresh()->roles()->count());

        // Considers it a success if role is not assigned
        static::assertTrue($auth->unassign('test_role', $user));
    }

    /**
     * @test
     */
    function it_unassigns_multiple_roles_from_a_user_at_once()
    {
        $this->expectsEvents(CmsUserPermissionsChanged::class);

        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        /** @var EloquentUser $user */
        $user = EloquentUser::create([
            'email'      => 'user@test1.com',
            'password'   => Hash::make('test'),
            'last_name'  => 'User',
            'first_name' => '1',
        ]);
        $roleA = $this->createRole('Test Role');
        $roleB = $this->createRole('Test Role2');

        $user->roles()->sync([$roleA['id'], $roleB['id']]);

        $auth = new Authenticator($this->getMockAuthRepository());

        $sentinelMock->shouldReceive('findRoleBySlug')->with('test_role')->andReturn($roleA);
        $sentinelMock->shouldReceive('findRoleBySlug')->once()->with('test_role2')->andReturn($roleB);
        $sentinelMock->shouldReceive('findRoleBySlug')->with('test_role_x')->andReturn(false);

        static::assertTrue($auth->unassign(['test_role', 'test_role2'], $user));
        static::assertEquals(0, $user->fresh()->roles()->count());


        $user->roles()->sync([$roleA['id'], $roleB['id']]);

        // Should return true if one of the roles does not exist
        static::assertTrue($auth->unassign(['test_role', 'test_role_x'], $user));
        static::assertEquals(1, $user->fresh()->roles()->count());
    }

    /**
     * @test
     */
    function it_creates_a_role()
    {
        $this->expectsEvents(CmsRolesChanged::class);

        $rolesMock = $this->getMockRoleRepository();
        $rolesMock->shouldReceive('getModel')->andReturn(EloquentRole::class);
        $rolesMock->shouldReceive('createModel')->andReturn( new EloquentRole);

        $sentinelMock = $this->getMockSentinel(null, $rolesMock);
        $this->app->instance('sentinel', $sentinelMock);

        $auth = new Authenticator($this->getMockAuthRepository());

        $sentinelMock->shouldReceive('findRoleBySlug')->with('test_role')->andReturn(false, true);

        static::assertTrue($auth->createRole('test_role'));
        static::assertEquals(1, EloquentRole::where('slug', 'test_role')->count());

        // Returns false if already exists
        static::assertFalse($auth->createRole('test_role', 'Test Role'));
    }

    /**
     * @test
     */
    function it_removes_a_role()
    {
        $this->expectsEvents(CmsRolesChanged::class);

        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $role = $this->createRole('Test Role');

        $auth = new Authenticator($this->getMockAuthRepository());

        $sentinelMock->shouldReceive('findRoleBySlug')->with('test_role')->andReturn($role, false);

        static::assertTrue($auth->removeRole('test_role'));
        static::assertEquals(0, EloquentRole::where('slug', 'test_role')->count());

        // Returns false if it does not exist
        static::assertFalse($auth->removeRole('test_role'));
    }

    
    // ------------------------------------------------------------------------------
    //      Permissions
    // ------------------------------------------------------------------------------
    
    /**
     * @test
     */
    function it_grants_a_permission_to_a_user()
    {
        $this->expectsEvents(CmsUserPermissionsChanged::class);

        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        /** @var EloquentUser $user */
        $user = EloquentUser::create([
            'email'       => 'user@test1.com',
            'password'    => Hash::make('test'),
            'last_name'   => 'User',
            'first_name'  => '1',
            'permissions' => [
                'test.present' => true,
            ],
        ]);

        $auth = new Authenticator($this->getMockAuthRepository());

        static::assertTrue($auth->grant('test.permission', $user));
        static::assertTrue($user->can('test.present', 'test.permission'));

        // Should return false if user could not be saved
        $user = $this->getMockUser();
        $user->shouldReceive('addPermission')->with('test.permission')->once()->andReturn(false);
        $user->shouldReceive('save')->once()->andReturn(false);

        static::assertFalse($auth->grant('test.permission', $user));
    }

    /**
     * @test
     */
    function it_grants_many_permissions_to_a_user()
    {
        $this->expectsEvents(CmsUserPermissionsChanged::class);

        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        /** @var EloquentUser $user */
        $user = EloquentUser::create([
            'email'       => 'user@test1.com',
            'password'    => Hash::make('test'),
            'last_name'   => 'User',
            'first_name'  => '1',
            'permissions' => [
                'test.present' => true,
            ],
        ]);

        $auth = new Authenticator($this->getMockAuthRepository());

        static::assertTrue($auth->grantMany(['test.permission', 'test.another'], $user));
        static::assertTrue($user->can(['test.present', 'test.permission', 'test.another']));

        // Should return false if user could not be saved
        $user = $this->getMockUser();
        $user->shouldReceive('addPermission')->with('test.permission')->once();
        $user->shouldReceive('addPermission')->with('test.another')->once();
        $user->shouldReceive('save')->once()->andReturn(false);

        static::assertFalse($auth->grantMany(['test.permission', 'test.another'], $user));
    }

    /**
     * @test
     */
    function it_revokes_a_permission_to_a_user()
    {
        $this->expectsEvents(CmsUserPermissionsChanged::class);

        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        /** @var EloquentUser $user */
        $user = EloquentUser::create([
            'email'       => 'user@test1.com',
            'password'    => Hash::make('test'),
            'last_name'   => 'User',
            'first_name'  => '1',
            'permissions' => [
                'test.present'    => true,
                'test.permission' => true,
            ],
        ]);

        $auth = new Authenticator($this->getMockAuthRepository());

        static::assertTrue($auth->revoke('test.permission', $user));
        static::assertFalse($user->can('test.permissions'));
        static::assertTrue($user->can('test.present'));

        // Should return false if user could not be saved
        $user = $this->getMockUser();
        $user->shouldReceive('removePermission')->with('test.permission')->once();
        $user->shouldReceive('save')->once()->andReturn(false);

        static::assertFalse($auth->revoke('test.permission', $user));
    }

    /**
     * @test
     */
    function it_revokes_many_permissions_to_a_user()
    {
        $this->expectsEvents(CmsUserPermissionsChanged::class);

        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        /** @var EloquentUser $user */
        $user = EloquentUser::create([
            'email'       => 'user@test1.com',
            'password'    => Hash::make('test'),
            'last_name'   => 'User',
            'first_name'  => '1',
            'permissions' => [
                'test.present'    => true,
                'test.permission' => true,
            ],
        ]);

        $auth = new Authenticator($this->getMockAuthRepository());

        static::assertTrue($auth->revokeMany(['test.permission', 'test.another'], $user));
        static::assertFalse($user->can('test.permission'));
        static::assertTrue($user->can('test.present'));

        // Should return false if user could not be saved
        $user = $this->getMockUser();
        $user->shouldReceive('removePermission')->with('test.permission')->once();
        $user->shouldReceive('removePermission')->with('test.another')->once();
        $user->shouldReceive('save')->once()->andReturn(false);

        static::assertFalse($auth->revokeMany(['test.permission', 'test.another'], $user));
    }

    /**
     * @test
     */
    function it_grants_a_permission_to_a_role()
    {
        $this->expectsEvents(CmsRolesChanged::class);

        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $role = $this->createRole('Test Role');

        $sentinelMock->shouldReceive('findRoleBySlug')->with('test_role')->andReturn($role, false);
        $sentinelMock->shouldReceive('findRoleBySlug')->with('role_does_not_exist')->andReturn(false);

        $auth = new Authenticator($this->getMockAuthRepository());

        static::assertTrue($auth->grantToRole('test.permission', 'test_role'));
        static::assertEquals(['test.permission'], $role->fresh()->getAllPermissions());

        // Should return false if role could not be found
        static::assertFalse($auth->grantToRole('test.permission', 'role_does_not_exist'));
    }

    /**
     * @test
     */
    function it_grants_multiple_permissions_to_a_role()
    {
        $this->expectsEvents(CmsRolesChanged::class);

        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $role = $this->createRole('Test Role');

        $sentinelMock->shouldReceive('findRoleBySlug')->with('test_role')->andReturn($role);

        $auth = new Authenticator($this->getMockAuthRepository());

        static::assertTrue($auth->grantToRole(['test.permission', 'test.another'], 'test_role'));
        static::assertEquals(['test.permission', 'test.another'], $role->fresh()->getAllPermissions());
    }

    /**
     * @test
     */
    function it_revokes_a_permission_to_a_role()
    {
        $this->expectsEvents(CmsRolesChanged::class);

        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $role = $this->createRole('Test Role', null, ['test.permission' => true]);

        $sentinelMock->shouldReceive('findRoleBySlug')->with('test_role')->andReturn($role, false);
        $sentinelMock->shouldReceive('findRoleBySlug')->with('role_does_not_exist')->andReturn(false);

        $auth = new Authenticator($this->getMockAuthRepository());

        static::assertTrue($auth->revokeFromRole('test.permission', 'test_role'));
        static::assertEquals([], $role->fresh()->getAllPermissions());

        // Should return false if role could not be found
        static::assertFalse($auth->revokeFromRole('test.permission', 'role_does_not_exist'));
    }

    /**
     * @test
     */
    function it_revokes_multiple_permissions_to_a_role()
    {
        $this->expectsEvents(CmsRolesChanged::class);

        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $role = $this->createRole('Test Role', null, ['test.permission' => true, 'test.another' => true]);

        $sentinelMock->shouldReceive('findRoleBySlug')->with('test_role')->andReturn($role);

        $auth = new Authenticator($this->getMockAuthRepository());

        static::assertTrue($auth->revokeFromRole(['test.permission', 'test.another'], 'test_role'));
        static::assertEquals([], $role->fresh()->getAllPermissions());
    }


    // ------------------------------------------------------------------------------
    //      User
    // ------------------------------------------------------------------------------

    /**
     * @test
     */
    function it_creates_a_user()
    {
        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $user = new EloquentUser([
            'email'    => 'test@test.nl',
            'password' => Hash::make('testing'),
        ]);

        $sentinelMock->shouldReceive('registerAndActivate')
            ->with(['email' => 'test@test.nl', 'password' => 'testing'])
            ->andReturnUsing(function () use ($user) { $user->save(); return $user; });

        $auth = new Authenticator($this->getMockAuthRepository());

        static::assertSame($user, $auth->createUser('test@test.nl', 'testing', [ 'first_name' => 'Test']));
        static::assertTrue($user->exists, 'User was not persisted');
        static::assertEquals('Test', $user->fresh()->first_name, 'Update was not performed');
    }

    /**
     * @test
     */
    function it_deletes_a_user()
    {
        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $user = EloquentUser::create([
            'email'    => 'test@test.nl',
            'password' => Hash::make('testing'),
        ]);

        $sentinelMock->shouldReceive('findByCredentials')->with(['email' => 'test@test.nl'])->andReturn($user);
        $sentinelMock->shouldReceive('findByCredentials')->with(['email' => 'does@not.exist'])->andReturn(false);

        $auth = new Authenticator($this->getMockAuthRepository());

        static::assertTrue($auth->deleteUser('test@test.nl'));
        static::assertEquals(0, EloquentUser::where('email', 'test@test.nl')->count());

        static::assertFalse($auth->deleteUser('does@not.exist'));
    }

    /**
     * @test
     */
    function it_does_not_delete_an_admin()
    {
        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $user = new EloquentUser([
            'email'    => 'test@test.nl',
            'password' => Hash::make('testing'),
        ]);
        $user->is_superadmin = true;
        $user->save();

        $sentinelMock->shouldReceive('findByCredentials')->with(['email' => 'test@test.nl'])->andReturn($user);

        $auth = new Authenticator($this->getMockAuthRepository());

        static::assertFalse($auth->deleteUser('test@test.nl'));
    }

    /**
     * @test
     */
    function it_updates_a_users_password()
    {
        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        $user = EloquentUser::create([
            'email'    => 'test@test.nl',
            'password' => Hash::make('testing'),
        ]);

        $sentinelMock->shouldReceive('findByCredentials')->with(['email' => 'test@test.nl'])->andReturn($user);
        $sentinelMock->shouldReceive('findByCredentials')->with(['email' => 'does@not.exist'])->andReturn(false);
        $sentinelMock->shouldReceive('update')->once()->with($user, ['password' => 'new password'])->andReturn($user);

        $auth = new Authenticator($this->getMockAuthRepository());

        static::assertTrue($auth->updatePassword('test@test.nl', 'new password'));

        static::assertFalse($auth->updatePassword('does@not.exist', 'new password'));
    }

    /**
     * @test
     */
    function it_updates_a_users_extra_data()
    {
        $sentinelMock = $this->getMockSentinel();
        $this->app->instance('sentinel', $sentinelMock);

        /** @var EloquentUser $user */
        $user = EloquentUser::create([
            'email'      => 'test@test.nl',
            'password'   => Hash::make('testing'),
            'first_name' => 'Old',
        ]);

        $sentinelMock->shouldReceive('findByCredentials')->with(['email' => 'test@test.nl'])->andReturn($user);
        $sentinelMock->shouldReceive('findByCredentials')->with(['email' => 'does@not.exist'])->andReturn(false);

        $auth = new Authenticator($this->getMockAuthRepository());

        static::assertTrue($auth->updateUser('test@test.nl', ['first_name' => 'New']));
        static::assertEquals('New', $user->fresh()->first_name, 'Data was not updated');

        static::assertFalse($auth->updateUser('does@not.exist', ['first_name' => 'New']));
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
     * @return AuthRepositoryInterface|Mockery\MockInterface|\Mockery\Mock
     */
    protected function getMockAuthRepository()
    {
        return Mockery::mock(AuthRepositoryInterface::class);
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
     * @return EloquentUser|\Mockery\MockInterface|\Mockery\Mock
     */
    protected function getMockUser()
    {
        return Mockery::mock(EloquentUser::class);
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
