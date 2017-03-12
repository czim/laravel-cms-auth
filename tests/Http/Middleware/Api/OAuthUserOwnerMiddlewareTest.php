<?php
namespace Czim\CmsAuth\Test\Http\Middleware\Api;

use Czim\CmsAuth\Http\Middleware\Api\OAuthUserOwnerMiddleware;
use Czim\CmsAuth\Sentinel\Users\EloquentUser;
use Czim\CmsAuth\Test\TestCase;
use Czim\CmsCore\Contracts\Auth\AuthenticatorInterface;
use Czim\CmsCore\Contracts\Core\CoreInterface;
use Czim\CmsCore\Support\Enums\Component;
use Illuminate\Http\Request;
use Czim\OAuth2Server\Authorizer;
use Mockery;

/**
 * Class OAuthUserOwnerMiddlewareTest
 *
 * @group api
 */
class OAuthUserOwnerMiddlewareTest extends TestCase
{
    protected $oauthDisabled = false;

    /**
     * @test
     */
    function it_passes_through_if_oauth_is_configured_to_be_disabled()
    {
        $this->oauthDisabled = true;

        $coreMock = $this->getMockCore();
        $this->app->instance(Component::CORE, $coreMock);

        /** @var Request|Mockery\Mock $requestMock */
        /** @var AuthenticatorInterface|Mockery\Mock $authMock */
        /** @var Authorizer|Mockery\Mock $authorizerMock */
        $requestMock    = Mockery::mock(Request::class);
        $authMock       = Mockery::mock(AuthenticatorInterface::class);
        $authorizerMock = Mockery::mock(Authorizer::class);

        $requestMock->shouldReceive('header')->once()->with('debug-user')->andReturn(null);

        $middleware = new OAuthUserOwnerMiddleware($authorizerMock, $coreMock, $authMock);

        $next = function ($request) { return $request; };

        static::assertSame($requestMock, $middleware->handle($requestMock, $next));
    }

    /**
     * @test
     */
    function it_logs_in_a_header_defined_debug_user_if_oauth_is_disabled()
    {
        $this->oauthDisabled = true;

        $coreMock = $this->getMockCore();
        $this->app->instance(Component::CORE, $coreMock);

        /** @var Request|Mockery\Mock $requestMock */
        /** @var AuthenticatorInterface|Mockery\Mock $authMock */
        /** @var Authorizer|Mockery\Mock $authorizerMock */
        /** @var EloquentUser|Mockery\Mock $userMock */
        $requestMock    = Mockery::mock(Request::class);
        $authMock       = Mockery::mock(AuthenticatorInterface::class);
        $authorizerMock = Mockery::mock(Authorizer::class);
        $userMock       = Mockery::mock(EloquentUser::class);

        $requestMock->shouldReceive('header')->once()->with('debug-user')->andReturn(3);

        $authMock->shouldReceive('getUserById')->with(3)->andReturn($userMock);
        $authMock->shouldReceive('forceUserStateless')->with($userMock)->andReturn(true);

        $middleware = new OAuthUserOwnerMiddleware($authorizerMock, $coreMock, $authMock);

        $next = function ($request) { return $request; };

        static::assertSame($requestMock, $middleware->handle($requestMock, $next));
    }

    /**
     * @test
     * @expectedException \League\OAuth2\Server\Exception\AccessDeniedException
     */
    function it_throws_an_exception_if_the_user_could_not_be_logged_in()
    {
        $this->oauthDisabled = true;

        $coreMock = $this->getMockCore();
        $this->app->instance(Component::CORE, $coreMock);

        /** @var Request|Mockery\Mock $requestMock */
        /** @var AuthenticatorInterface|Mockery\Mock $authMock */
        /** @var Authorizer|Mockery\Mock $authorizerMock */
        /** @var EloquentUser|Mockery\Mock $userMock */
        $requestMock    = Mockery::mock(Request::class);
        $authMock       = Mockery::mock(AuthenticatorInterface::class);
        $authorizerMock = Mockery::mock(Authorizer::class);
        $userMock       = Mockery::mock(EloquentUser::class);

        $requestMock->shouldReceive('header')->once()->with('debug-user')->andReturn(3);

        $authMock->shouldReceive('getUserById')->with(3)->andReturn($userMock);
        $authMock->shouldReceive('forceUserStateless')->with($userMock)->andReturn(false);

        $middleware = new OAuthUserOwnerMiddleware($authorizerMock, $coreMock, $authMock);

        $next = function ($request) { return $request; };

        $middleware->handle($requestMock, $next);
    }

    /**
     * @test
     */
    function it_logs_in_the_user_owner_of_an_oath_token()
    {
        $coreMock = $this->getMockCore();
        $this->app->instance(Component::CORE, $coreMock);

        /** @var Request|Mockery\Mock $requestMock */
        /** @var AuthenticatorInterface|Mockery\Mock $authMock */
        /** @var Authorizer|Mockery\Mock $authorizerMock */
        /** @var EloquentUser|Mockery\Mock $userMock */
        $requestMock    = Mockery::mock(Request::class);
        $authMock       = Mockery::mock(AuthenticatorInterface::class);
        $authorizerMock = Mockery::mock(Authorizer::class);
        $userMock       = Mockery::mock(EloquentUser::class);

        $authorizerMock->shouldReceive('setRequest')->once()->with($requestMock);
        $authorizerMock->shouldReceive('getResourceOwnerType')->once()->andReturn('user');
        $authorizerMock->shouldReceive('getResourceOwnerId')->once()->andReturn(3);

        $authMock->shouldReceive('getUserById')->with(3)->andReturn($userMock);
        $authMock->shouldReceive('forceUserStateless')->with($userMock)->andReturn(true);

        $middleware = new OAuthUserOwnerMiddleware($authorizerMock, $coreMock, $authMock);

        $next = function ($request) { return $request; };

        static::assertSame($requestMock, $middleware->handle($requestMock, $next));
    }

    /**
     * @test
     * @expectedException \League\OAuth2\Server\Exception\AccessDeniedException
     */
    function it_throws_an_exception_for_owner_types_other_than_user()
    {
        $coreMock = $this->getMockCore();
        $this->app->instance(Component::CORE, $coreMock);

        /** @var Request|Mockery\Mock $requestMock */
        /** @var AuthenticatorInterface|Mockery\Mock $authMock */
        /** @var Authorizer|Mockery\Mock $authorizerMock */
        $requestMock    = Mockery::mock(Request::class);
        $authMock       = Mockery::mock(AuthenticatorInterface::class);
        $authorizerMock = Mockery::mock(Authorizer::class);

        $authorizerMock->shouldReceive('setRequest')->once()->with($requestMock);
        $authorizerMock->shouldReceive('getResourceOwnerType')->once()->andReturn('client');
        $authorizerMock->shouldNotReceive('getResourceOwnerId');

        $middleware = new OAuthUserOwnerMiddleware($authorizerMock, $coreMock, $authMock);

        $next = function ($request) { return $request; };

        $middleware->handle($requestMock, $next);
    }


    /**
     * @return CoreInterface|Mockery\Mock
     */
    protected function getMockCore()
    {
        /** @var Mockery\Mock $mock */
        $mock = Mockery::mock(CoreInterface::class);

        $mock->shouldReceive('apiConfig')->with('debug.disable-auth')->andReturn($this->oauthDisabled);
        $mock->shouldReceive('apiConfig')->with('debug.debug-user-header')->andReturn($this->oauthDisabled);

        return $mock;
    }

}
