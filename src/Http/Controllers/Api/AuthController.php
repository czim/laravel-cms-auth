<?php
namespace Czim\CmsAuth\Http\Controllers\Api;

use Czim\CmsAuth\Api\OAuth\Storage\FluentRefreshToken;
use Czim\CmsAuth\Http\Controllers\Controller;
use Czim\CmsAuth\Http\Requests\Api\OAuthIssueAccessTokenRequest;
use Czim\CmsAuth\Http\Requests\Api\OAuthRevokeAccessTokenRequest;
use Czim\CmsCore\Contracts\Core\CoreInterface;
use Illuminate\Http\Request;
use Czim\OAuth2Server\Authorizer;

class AuthController extends Controller
{

    /**
     * @var CoreInterface
     */
    protected $core;

    /**
     * @var Authorizer
     */
    protected $authorizer;

    /**
     * @param CoreInterface $core
     * @param Authorizer    $authorizer
     */
    public function __construct(CoreInterface $core, Authorizer $authorizer)
    {
        $this->core       = $core;
        $this->authorizer = $authorizer;
    }

    /**
     * Provides an access token based on a request with login/refresh credentials.
     *
     * @param OAuthIssueAccessTokenRequest $request
     * @return mixed
     */
    public function issueAccessToken(OAuthIssueAccessTokenRequest $request)
    {
        return $this->core->api()->response(
            $this->authorizer->issueAccessToken()
        );
    }

    /**
     * Revokes an access or refresh token.
     *
     * @param OAuthRevokeAccessTokenRequest|Request $request
     * @return mixed
     */
    public function revokeAccessToken(OAuthRevokeAccessTokenRequest $request)
    {
        switch (strtolower($request->get('token_type_hint', ''))) {

            case 'access_token':
                $this->expireAccessToken($request->get('token'));
                break;

            case 'refresh_token':
                $this->expireRefreshToken($request->get('token'));
                break;

            // Default omitted on purpose
        }

        return $this->core->api()->response('OK');
    }

    /**
     * Expires a given access token, if allowed.
     *
     * @param string $token
     */
    protected function expireAccessToken($token)
    {
        // Check if the given token matches the current user's
        $access = $this->authorizer->getAccessToken();
        if ( ! $access) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        if ($access->getId() !== $token) {
            return;
        }

        $this->authorizer->getChecker()->getAccessToken()->expire();
    }

    /**
     * Expires a given refresh token, if allowed.
     *
     * @param string $token
     */
    protected function expireRefreshToken($token)
    {
        /** @var FluentRefreshToken $storage */
        $storage = app(FluentRefreshToken::class);

        $refresh = $storage->get($token);
        if ( ! $refresh) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        // Check if the related access token matches the current user's token
        $access = $this->authorizer->getAccessToken();

        if ( ! $access) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        if ($access->getId() !== $refresh->getAccessToken()->getId()) {
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        $refresh->expire();
    }

}
