<?php
namespace Czim\CmsAuth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Czim\CmsCore\Contracts\Core\CoreInterface;
use Czim\CmsCore\Support\Enums\NamedRoute;

class AuthController extends Controller
{

    /**
     * @var CoreInterface
     */
    protected $core;

    /**
     * @param CoreInterface $core
     */
    public function __construct(CoreInterface $core)
    {
        $this->core = $core;
    }

    /**
     * Show the application login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm()
    {
        $this->storeIntendedUrl();

        return view($this->core->config('views.login'));
    }

    /**
     * Handle a login request to the application.
     *
     * @todo enable throttling
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function login(Request $request)
    {
        $this->validate($request, [
            'email'    => 'required',
            'password' => 'required',
        ]);

        if (
            $this->core->auth()
                ->login($request->input('email'), $request->input('password'), $request->has('remember'))
        ) {
            return redirect()->intended(
                $this->core->route(NamedRoute::HOME)
            );
        }

        return redirect()->back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors([
                'email' => Lang::has('cms::auth.failed')
                    ?   Lang::get('cms::auth.failed')
                    :   'These credentials do not match our records.',
            ]);
    }

    /**
     * Log the user out of the application.
     *
     * @return mixed
     */
    public function logout()
    {
        $this->core->auth()->logout();

        return redirect()->route(
            $this->core->prefixRoute(NamedRoute::AUTH_LOGIN)
        );
    }

    /**
     * Stores the intended URL to redirect to after succesful login.
     */
    protected function storeIntendedUrl()
    {
        // Do not store the URL if it was the login itself
        $previousUrl = url()->previous();
        $loginUrl    = url()->route($this->core->prefixRoute(NamedRoute::AUTH_LOGIN));

        if ($previousUrl == $loginUrl) return;

        session()->put('url.intended', $previousUrl);
    }

}
