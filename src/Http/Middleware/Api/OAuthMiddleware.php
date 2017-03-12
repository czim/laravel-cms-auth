<?php
namespace Czim\CmsAuth\Http\Middleware\Api;

use Closure;
use Czim\CmsCore\Contracts\Core\CoreInterface;
use Czim\CmsCore\Support\Enums\Component;
use Czim\OAuth2Server\Middleware\OAuthMiddleware as LucaDegasperiOAuthMiddleware;

/**
 * Class OAuthMiddleware
 *
 * Extended so we can disable oauth access token checks for development environments
 * if configured to.
 */
class OAuthMiddleware extends LucaDegasperiOAuthMiddleware
{

    /**
     * @param \Illuminate\Http\Request $request
     * @param Closure                  $next
     * @param null                     $scopesString
     * @return mixed
     * @throws \League\OAuth2\Server\Exception\InvalidScopeException
     */
    public function handle($request, Closure $next, $scopesString = null)
    {
        if ($this->getCmsCore()->apiConfig('debug.disable-auth')) {
            return $next($request);
        }

        return parent::handle($request, $next, $scopesString);
    }

    /**
     * @return CoreInterface
     */
    protected function getCmsCore()
    {
        return app(Component::CORE);
    }

}
