<?php
namespace Czim\CmsAuth\Auth;

use Czim\CmsAuth\Http\Controllers\Api\AuthController;
use Czim\CmsCore\Support\Enums\CmsMiddleware;
use Czim\CmsCore\Support\Enums\NamedRoute;

trait AuthApiRoutingTrait
{

    /**
     * Returns router action for the CMS API authentication.
     *
     * @return string|array
     */
    public function getApiRouteLoginAction()
    {
        return [
            'as'   => NamedRoute::AUTH_LOGIN,
            'uses' => AuthController::class . '@issueAccessToken',
        ];
    }

    /**
     * Returns router action for logging out of the CMS for the API.
     *
     * @return string|array
     */
    public function getApiRouteLogoutAction()
    {
        return [
            'as'         => NamedRoute::AUTH_LOGOUT,
            'middleware' => [
                CmsMiddleware::API_AUTHENTICATED,
                CmsMiddleware::API_AUTH_OWNER,
            ],
            'uses'       => AuthController::class . '@revokeAccessToken',
        ];
    }

}
