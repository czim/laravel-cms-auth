<?php
namespace Czim\CmsAuth\Test\Http\Middleware\Api;

use Czim\CmsAuth\Http\Middleware\Api\OAuthMiddleware;
use Czim\CmsAuth\Test\TestCase;
use Czim\CmsCore\Contracts\Core\CoreInterface;
use Czim\CmsCore\Support\Enums\Component;
use Illuminate\Http\Request;
use Czim\OAuth2Server\Authorizer;
use Mockery;

/**
 * Class OAuthMiddlewareTest
 *
 * @group api
 */
class OAuthMiddlewareTest extends TestCase
{
    protected $oauthDisabled = false;

    /**
     * @test
     */
    function it_passes_through_if_oauth_is_configured_to_be_disabled()
    {
        $this->oauthDisabled = true;

        $this->app->instance(Component::CORE, $this->getMockCore());

        /** @var Request|Mockery\Mock $requestMock */
        /** @var Authorizer|Mockery\Mock $authorizerMock */
        $requestMock    = Mockery::mock(Request::class);
        $authorizerMock = Mockery::mock(Authorizer::class);

        $authorizerMock->shouldNotReceive('setRequest');

        $middleware = new OAuthMiddleware($authorizerMock);

        $next = function ($request) { return $request; };

        static::assertSame($requestMock, $middleware->handle($requestMock, $next));
    }

    /**
     * @return CoreInterface|Mockery\Mock
     */
    protected function getMockCore()
    {
        /** @var Mockery\Mock $mock */
        $mock = Mockery::mock(CoreInterface::class);

        $mock->shouldReceive('apiConfig')->with('debug.disable-auth')->andReturn($this->oauthDisabled);

        return $mock;
    }

}
