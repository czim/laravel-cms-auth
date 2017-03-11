<?php
namespace Czim\CmsAuth\Test\Sentinel\Roles;

use Czim\CmsAuth\Sentinel\Roles\EloquentRole;
use Czim\CmsAuth\Test\TestCase;

class EloquentRoleTest extends TestCase
{

    /**
     * @test
     */
    function it_returns_the_name()
    {
        $role = new EloquentRole([
            'name' => 'Test',
        ]);

        static::assertEquals('Test', $role->getName());
    }

    /**
     * @test
     */
    function it_returns_the_slug()
    {
        $role = new EloquentRole([
            'slug' => 'Test',
        ]);

        static::assertEquals('Test', $role->getSlug());
    }

}
