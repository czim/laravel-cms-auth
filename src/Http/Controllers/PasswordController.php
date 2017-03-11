<?php
namespace Czim\CmsAuth\Http\Controllers;

/**
 * Class PasswordController
 *
 * @todo
 * @codeCoverageIgnore
 */
class PasswordController extends Controller
{

    /**
     * Display the form to request a password reset link.
     */
    public function showLinkRequestForm()
    {
        return 'not implemented';
    }

    /**
     * Send a reset link to the given user.
     */
    public function sendResetLinkEmail()
    {
        return 'not implemented';
    }

    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @param string|null $token
     * @return mixed
     */
    public function showResetForm($token = null)
    {
        return 'not implemented';
    }

    /**
     * Reset the given user's password.
     */
    public function reset()
    {
        return 'not implemented';
    }

}
