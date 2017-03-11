<?php
namespace Czim\CmsAuth\Test\Sentinel\Roles;

use Czim\CmsAuth\Api\VerifyUser;
use Czim\CmsAuth\Sentinel\Users\EloquentUser;
use Czim\CmsAuth\Test\TestCase;
use Czim\CmsCore\Contracts\Auth\AuthenticatorInterface;
use Czim\CmsCore\Support\Enums\Component;
use Hash;

/**
 * Class VerifyUserTest
 *
 * @group api
 */
class VerifyUserTest extends TestCase
{

    /**
     * @test
     */
    function it_returns_false_for_incorrect_credentials_on_verification()
    {
        /** @var AuthenticatorInterface $authMock */
        $authMock = \Mockery::mock(AuthenticatorInterface::class);
        $authMock->shouldReceive('stateless')->once()->with('test@test.nl', 'testing')->andReturn(false);

        $this->app->instance(Component::AUTH, $authMock);

        $verify = new VerifyUser;

        static::assertFalse($verify->verify('test@test.nl', 'testing'));
    }

    /**
     * @test
     */
    function it_statelessly_logs_in_a_user_on_verification()
    {
        $user = new EloquentUser([
            'email'    => 'test@test.nl',
            'password' => Hash::make('testing'),
        ]);

        $user->id = 1;

        /** @var AuthenticatorInterface $authMock */
        $authMock = \Mockery::mock(AuthenticatorInterface::class);
        $authMock->shouldReceive('stateless')->once()->with('test@test.nl', 'testing')->andReturn(true);
        $authMock->shouldReceive('user')->once()->andReturn($user);

        $this->app->instance(Component::AUTH, $authMock);

        $verify = new VerifyUser;

        static::assertEquals(1, $verify->verify('test@test.nl', 'testing'));
    }

    /**
     * @test
     */
    function it_returns_false_if_user_could_not_be_found_after_successful_login()
    {
        $user = new EloquentUser([
            'email'    => 'test@test.nl',
            'password' => Hash::make('testing'),
        ]);

        $user->id = 1;

        /** @var AuthenticatorInterface $authMock */
        $authMock = \Mockery::mock(AuthenticatorInterface::class);
        $authMock->shouldReceive('stateless')->once()->with('test@test.nl', 'testing')->andReturn(true);
        $authMock->shouldReceive('user')->once()->andReturn(false);

        $this->app->instance(Component::AUTH, $authMock);

        $verify = new VerifyUser;

        static::assertSame(false, $verify->verify('test@test.nl', 'testing'));
    }

}
