<?php
namespace Czim\CmsAuth\Test\Console;

use Czim\CmsAuth\Console\Commands\CreateUser;
use Czim\CmsCore\Contracts\Auth\AuthenticatorInterface;
use Illuminate\Console\Command;
use Mockery;

class CreateUserCommandTest extends ConsoleTestCase
{

    /**
     * @test
     */
    function it_creates_a_new_user()
    {
        $this->artisan('cms:user:create', [
            'username'    => 'test@test.com',
            'password'    => 'testpassword',
            '--firstName' => 'Test',
            '--lastName'  => 'User',
        ]);

        $this->assertDatabaseHas($this->prefixTable('users'), [
            'email'      => 'test@test.com',
            'first_name' => 'Test',
            'last_name'  => 'User',
        ]);
    }

    /**
     * @test
     */
    function it_creates_a_super_admin_user()
    {
        $this->artisan('cms:user:create', [
            'username'    => 'admin@test.com',
            'password'    => 'testpassword',
            '--admin'     => true,
            '--firstName' => 'Test',
            '--lastName'  => 'Admin',
        ]);

        $this->assertDatabaseHas($this->prefixTable('users'), [
            'email'         => 'admin@test.com',
            'is_superadmin' => true,
            'first_name'    => 'Test',
            'last_name'     => 'Admin',
        ]);
    }

    /**
     * @test
     */
    function it_asks_for_a_password_if_none_is_given()
    {
        /** @var Mockery\Mock|Command $command */
        $command = Mockery::mock(CreateUser::class . '[secret]');
        $command->shouldReceive('secret')->twice()->andReturn('testpassword');

        $this->getConsoleKernel()->registerCommand($command);

        $this->artisan('cms:user:create', [
            'username'    => 'admin@test.com',
            '--firstName' => 'Test',
            '--lastName'  => 'Admin',
        ]);

        $this->assertDatabaseHas($this->prefixTable('users'), [
            'email'      => 'admin@test.com',
            'first_name' => 'Test',
            'last_name'  => 'Admin',
        ]);
    }

    /**
     * @test
     */
    function it_asks_again_if_no_password_is_entered_when_asked()
    {
        /** @var Mockery\Mock|Command $command */
        $command = Mockery::mock(CreateUser::class . '[secret]');
        $command->shouldReceive('secret')->times(4)->andReturn(null, null, 'test', 'test');

        $this->getConsoleKernel()->registerCommand($command);

        $this->artisan('cms:user:create', [
            'username'    => 'admin@test.com',
            '--firstName' => 'Test',
            '--lastName'  => 'Admin',
        ]);
    }

    /**
     * @test
     */
    function it_aborts_if_password_confirmation_is_different()
    {
        /** @var Mockery\Mock|Command $command */
        $command = Mockery::mock(CreateUser::class . '[secret]');
        $command->shouldReceive('secret')->times(4)->andReturn('test', 'notthesame', 'testpassword', 'testpassword');

        $this->getConsoleKernel()->registerCommand($command);

        $this->artisan('cms:user:create', [
            'username'    => 'admin@test.com',
            '--firstName' => 'Test',
            '--lastName'  => 'Admin',
        ]);

        static::assertRegExp('#try again#i', $this->getArtisanOutput());
    }
    
    /**
     * @test
     */
    function it_warns_if_the_user_could_not_be_created()
    {
        $mockAuth = $this->getMockBuilder(AuthenticatorInterface::class)->getMock();
        $mockAuth->expects(static::once())->method('createUser')->willReturn(false);

        /** @var Mockery\Mock|Command $command */
        $command = Mockery::mock(CreateUser::class . '[getAuthenticator]')
            ->shouldAllowMockingProtectedMethods();

        $command->shouldReceive('getAuthenticator')->andReturn($mockAuth);

        $this->getConsoleKernel()->registerCommand($command);

        $this->artisan('cms:user:create', [
            'username'    => 'user@test.com',
            'password'    => 'testpassword',
            '--firstName' => 'Test',
            '--lastName'  => 'Admin',
        ]);

        static::assertRegExp('##i', $this->getArtisanOutput());
    }

}
