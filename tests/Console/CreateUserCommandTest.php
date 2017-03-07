<?php
namespace Czim\CmsAuth\Test\Console;

use Artisan;
use Czim\CmsAuth\Test\TestCase;

class CreateUserCommandTest extends TestCase
{

    /**
     * @test
     */
    function it_creates_a_new_user()
    {
        Artisan::call('cms:user:create', [
            'username'    => 'test@test.com',
            'password'    => 'testpassword',
            '--firstName' => 'Test',
            '--lastName'  => 'User',
        ]);

        $this->assertDatabaseHas($this->prefixCmsTable('users'), [
            'email'      => 'test@test.com',
            'first_name' => 'Test',
            'last_name'  => 'User',
        ]);
    }

}
