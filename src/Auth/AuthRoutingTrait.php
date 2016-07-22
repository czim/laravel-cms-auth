<?php
namespace Czim\CmsAuth\Auth;

use Czim\CmsAuth\Http\Controllers\AuthController;
use Czim\CmsAuth\Http\Controllers\PasswordController;
use Czim\CmsCore\Support\Enums\CmsMiddleware;
use Czim\CmsCore\Support\Enums\NamedRoute;

trait AuthRoutingTrait
{

    /**
     * Returns router action for the CMS login form.
     *
     * @return string|array
     */
    public function getRouteLoginAction()
    {
        return [
            'middleware' => [ CmsMiddleware::GUEST ],
            'as'         => NamedRoute::AUTH_LOGIN,
            'uses'       => AuthController::class . '@showLoginForm',
        ];
    }

    /**
     * Returns router action for the CMS login, posting login credentials.
     *
     * @return string|array
     */
    public function getRouteLoginPostAction()
    {
        return [
            'middleware' => [ CmsMiddleware::GUEST ],
            'uses'       => AuthController::class . '@login',
        ];
    }

    /**
     * Returns router action for logging out of the CMS.
     *
     * @return string|array
     */
    public function getRouteLogoutAction()
    {
        return [
            'as'   => NamedRoute::AUTH_LOGOUT,
            'uses' => AuthController::class . '@logout',
        ];
    }

    /**
     * Returns router action for password email request form.
     *
     * @return string|array
     */
    public function getRoutePasswordEmailGetAction()
    {
        return [
            'as'   => NamedRoute::AUTH_PASSWORD_EMAIL,
            'uses' => PasswordController::class . '@showLinkRequestForm',
        ];
    }

    /**
     * Returns router action for posting request for password email.
     *
     * @return string|array
     */
    public function getRoutePasswordEmailPostAction()
    {
        return PasswordController::class . '@sendResetLinkEmail';
    }

    /**
     * Returns router action for posting request for the password reset form.
     *
     * @return string|array
     */
    public function getRoutePasswordResetGetAction()
    {
        return [
            'as'   => NamedRoute::AUTH_PASSWORD_RESET,
            'uses' => PasswordController::class . '@showResetForm',
        ];
    }

    /**
     * Returns router action for posting request posting the password reset form.
     *
     * @return string|array
     */
    public function getRoutePasswordResetPostAction()
    {
        return PasswordController::class . '@reset';
    }

}
