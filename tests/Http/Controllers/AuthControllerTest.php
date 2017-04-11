<?php
namespace Czim\CmsAuth\Test\Http\Controllers;

use Czim\CmsAuth\Http\Controllers\AuthController;
use Czim\CmsAuth\Test\WebTestCase;
use Czim\CmsCore\Contracts\Core\CoreInterface;
use Czim\CmsCore\Providers\CmsCoreServiceProvider;
use Czim\CmsCore\Support\Enums\Component;
use Czim\CmsCore\Support\Enums\NamedRoute;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\View\Factory;
use Mockery;

class AuthControllerTest extends WebTestCase
{

    /**
     * {@inheritdoc}
     */
    public function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app->register(CmsCoreServiceProvider::class);
    }

    /**
     * @test
     */
    function it_returns_a_login_form_view()
    {
        $core = $this->getCore();

        $controller = new AuthController($core);

        /** @var Mockery\Mock $viewMock */
        $viewMock = Mockery::mock(Factory::class);
        $viewMock->shouldReceive('make')
            ->with('cms::auth.login', Mockery::any(), Mockery::any())
            ->andReturn('testing');

        $this->app->instance(Factory::class, $viewMock);

        /** @var Mockery\Mock $urlMock */
        $urlMock = Mockery::mock(UrlGenerator::class);
        $urlMock->shouldReceive('previous')->once()->andReturn('cms/intended/url');
        $urlMock->shouldReceive('route')
            ->with($core->prefixRoute(NamedRoute::AUTH_LOGIN))
            ->andReturn('cms/auth/login');

        $this->app->instance(UrlGenerator::class, $urlMock);

        static::assertEquals('testing', $controller->showLoginForm());
        static::assertTrue(session()->has('url.intended'));
        static::assertEquals('cms/intended/url', session('url.intended'));
    }

    /**
     * @test
     */
    function it_does_not_store_intended_url_if_it_is_the_login_route()
    {
        $core = $this->getCore();

        $controller = new AuthController($core);

        $this->prepareViewMock();

        /** @var Mockery\Mock $urlMock */
        $urlMock = Mockery::mock(UrlGenerator::class);
        $urlMock->shouldReceive('previous')->once()->andReturn('cms/auth/login');
        $urlMock->shouldReceive('route')->andReturn('cms/auth/login');

        $this->app->instance(UrlGenerator::class, $urlMock);

        static::assertEquals('testing', $controller->showLoginForm());
        static::assertFalse(session()->has('url.intended'));
    }

    /**
     * @test
     */
    function it_logs_a_user_in()
    {
        $auth = $this->getCore()->auth();

        $user = $auth->createUser('test@test.nl', 'testing');

        $this->post('cms/auth/login', [
            'email'    => 'test@test.nl',
            'password' => 'testing',
        ]);

        static::assertEquals(302, $this->response->getStatusCode());
        static::assertTrue($auth->check());
        static::assertEquals($user->getUsername(), $auth->user()->getUsername());
    }

    /**
     * @test
     */
    function it_does_not_log_a_user_in_with_incorrect_credentials()
    {
        $auth = $this->getCore()->auth();

        $auth->createUser('test@test.nl', 'testing');

        // Set translation key
        $this->app->setLocale('en');
        $this->app['translator']->addLines(['auth.failed' => 'translated'], 'en', 'cms');

        $this->post('cms/auth/login', [
            'email'    => 'test@test.nl',
            'password' => 'incorrect',
        ]);

        static::assertEquals(302, $this->response->getStatusCode());
        static::assertFalse($auth->check());


    }

    /**
     * @test
     */
    function it_logs_a_user_out()
    {
        $auth = $this->getCore()->auth();

        $auth->createUser('test@test.nl', 'testing');
        static::assertTrue($auth->login('test@test.nl', 'testing'), 'Setup failed');

        $this->get('cms/auth/logout');

        static::assertEquals(302, $this->response->getStatusCode());
        static::assertFalse($auth->check());
    }


    /**
     * @return CoreInterface
     */
    protected function getCore()
    {
        return $this->app[Component::CORE];
    }

    /**
     * Prepares view mocking to prevent needing cms:: view namespace.
     */
    protected function prepareViewMock()
    {
        /** @var Mockery\Mock $viewMock */
        $viewMock = Mockery::mock(Factory::class);
        $viewMock->shouldReceive('make')->andReturn('testing');
        $viewMock->shouldReceive('share')->andReturnSelf();

        $this->app->instance(Factory::class, $viewMock);
    }

}
