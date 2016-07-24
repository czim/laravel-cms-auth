<?php
namespace Czim\CmsAuth\Http\Middleware\Api;

use Closure;
use Czim\CmsCore\Contracts\Auth\AuthenticatorInterface;
use Czim\CmsCore\Contracts\Core\CoreInterface;
use Illuminate\Http\Request;
use League\OAuth2\Server\Exception\AccessDeniedException;
use LucaDegasperi\OAuth2Server\Authorizer;

class OAuthUserOwnerMiddleware
{

    /**
     * @var Authorizer
     */
    protected $authorizer;

    /**
     * @var CoreInterface
     */
    protected $core;

    /**
     * @var AuthenticatorInterface
     */
    protected $auth;

    /**
     * Create a new oauth user middleware instance.
     *
     * @param Authorizer             $authorizer
     * @param CoreInterface          $core
     * @param AuthenticatorInterface $auth
     */
    public function __construct(Authorizer $authorizer, CoreInterface $core, AuthenticatorInterface $auth)
    {
        $this->authorizer = $authorizer;
        $this->core       = $core;
        $this->auth       = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @throws AccessDeniedException
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->core->apiConfig('debug.disable-local-auth') && app()->isLocal()) {

            $this->loginDebugUser($request);

            return $next($request);
        }


        $this->authorizer->setRequest($request);

        if ($this->authorizer->getResourceOwnerType() !== 'user') {
            throw new AccessDeniedException();
        }

        $this->loginUserById(
            $this->authorizer->getResourceOwnerId()
        );

        return $next($request);
    }

    /**
     * Attempts to log in a user
     *
     * @param Request $request
     * @throws AccessDeniedException
     */
    protected function loginDebugUser($request)
    {
        // Allow faking users through the debug-user header
        $debugUserHeader = $this->core->apiConfig('debug.debug-user-header');

        if ( ! $debugUserHeader || ! ($userId = $request->header($debugUserHeader))) {
            return;
        }

        $this->loginUserById($userId);
    }

    /**
     * Forces login of a user, without persistence.
     *
     * @param $id
     * @throws AccessDeniedException
     */
    protected function loginUserById($id)
    {
        if ( ! $id) {
            return;
        }

        $user = $this->auth->getUserById( (int) $id );

        if (    ! $user
            ||  ! $this->auth->forceUserStateless($user)
        ) {
            throw new AccessDeniedException();
        }
    }

}
