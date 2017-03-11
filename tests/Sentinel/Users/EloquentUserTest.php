<?php
namespace Czim\CmsAuth\Test\Sentinel\Users;

use Czim\CmsAuth\Sentinel\Roles\EloquentRole;
use Czim\CmsAuth\Sentinel\Users\EloquentUser;
use Czim\CmsAuth\Test\TestCase;
use Hash;

class EloquentUserTest extends TestCase
{

    /**
     * @test
     */
    function it_returns_the_user_name()
    {
        $user = new EloquentUser([
            'email' => 'Test',
        ]);

        static::assertEquals('Test', $user->getUsername());
    }

    /**
     * @test
     */
    function it_returns_whether_the_user_can_any_of()
    {
        $user = EloquentUser::create([
            'email'    => 'Test',
            'password' => Hash::make('testing'),
            'permissions' => [
                'test' => true,
            ],
        ]);

        static::assertTrue($user->canAnyOf(['test']));
    }
    
    /**
     * @test
     */
    function it_returns_get_all_roles()
    {
        /** @var EloquentUser $user */
        $user = EloquentUser::create([
            'email'    => 'Test',
            'password' => Hash::make('testing'),
        ]);

        $role = EloquentRole::create([
            'slug' => 'test-slug',
            'name' => 'Test',
        ]);

        $user->roles()->attach($role->id);

        static::assertEquals(['test-slug'], $user->getAllRoles());
        static::assertEquals(['test-slug'], $user->getAllRolesAttribute());
    }

    /**
     * @test
     */
    function it_returns_get_all_permissions()
    {
        /** @var EloquentUser $user */
        $user = EloquentUser::create([
            'email'    => 'Test',
            'password' => Hash::make('testing'),
            'permissions' => [
                'test'    => true,
                'another' => true,
                'not'     => false,
            ],
        ]);

        static::assertEquals(['test', 'another'], $user->getAllPermissions());
        static::assertEquals(['test', 'another'], $user->getAllPermissionsAttribute());
    }

}
