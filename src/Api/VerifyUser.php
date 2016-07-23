<?php
namespace Czim\CmsAuth\Api;

use Czim\CmsAuth\Sentinel\Users\EloquentUser;
use Czim\CmsCore\Contracts\Auth\AuthenticatorInterface;
use Czim\CmsCore\Support\Enums\Component;

/**
 * Class VerifyUser
 *
 * Verifies a user by attempting a stateless login on the authenticator.
 * Used by the League OAuth access token issue process.
 */
class VerifyUser
{

    /**
     * Returns user ID if it could be verified.
     *
     * @param string $email
     * @param string $password
     * @return bool|int
     */
    public function verify($email, $password)
    {
        $auth = $this->getAuthenticator();

        if ( ! $auth->stateless($email, $password)) {
            return false;
        }

        /** @var EloquentUser $user */
        $user = $auth->user();

        if ( ! $user) {
            return false;
        }

        return $user->getUserId();
    }

    /**
     * @return AuthenticatorInterface
     */
    protected function getAuthenticator()
    {
        return app(Component::AUTH);
    }

}
