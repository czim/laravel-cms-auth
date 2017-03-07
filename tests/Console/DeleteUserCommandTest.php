<?php
namespace Czim\CmsAuth\Test\Console;

use Artisan;
use Czim\CmsAuth\Sentinel\Users\EloquentUser;
use Czim\CmsAuth\Test\TestCase;

class DeleteUserCommandTest extends TestCase
{

    /**
     * @test
     */
    function it_deletes_an_existing_user()
    {
        EloquentUser::create([
            'email'    => 'test@test.com',
            'password' => \Hash::make('testing'),
        ]);

        $this->assertDatabaseHas($this->prefixCmsTable('users'), [ 'email' => 'test@test.com' ]);

        Artisan::call('cms:user:delete', [
            'username' => 'test@test.com',
        ]);

        $this->assertDatabaseMissing($this->prefixCmsTable('users'), [ 'email' => 'test@test.com' ]);
    }

}
