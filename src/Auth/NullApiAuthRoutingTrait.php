<?php
namespace Czim\CmsAuth\Auth;

trait NullApiAuthRoutingTrait
{

    /**
     * Returns router action for the CMS API authentication.
     *
     * @return string|array
     */
    public function getApiRouteLoginAction()
    {
        return [];
    }

    /**
     * Returns router action for logging out of the CMS for the API.
     *
     * @return string|array
     */
    public function getApiRouteLogoutAction()
    {
        return [];
    }

}
