<?php
namespace Czim\CmsAuth\Test\Console;

use Czim\CmsAuth\Sentinel\Users\EloquentUser;

class DeleteUserCommandTest extends ConsoleTestCase
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

        $this->seeInDatabase($this->prefixTable('users'), [ 'email' => 'test@test.com' ]);

        $this->artisan('cms:user:delete', [
            'username' => 'test@test.com',
        ]);

        $this->notSeeInDatabase($this->prefixTable('users'), [ 'email' => 'test@test.com' ]);
    }

    /**
     * @test
     */
    function it_shows_an_error_if_the_user_could_not_be_found()
    {
        $this->artisan('cms:user:delete', [
            'username' => 'does-not-exist-test@test.com',
        ]);

        static::assertRegExp('#failed#i', $this->getArtisanOutput());
    }

}
