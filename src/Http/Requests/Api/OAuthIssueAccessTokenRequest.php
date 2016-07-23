<?php
namespace Czim\CmsAuth\Http\Requests\Api;

use Czim\CmsAuth\Http\Requests\Request;

class OAuthIssueAccessTokenRequest extends Request
{

    /**
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'client_id'     => 'required|string',
            'client_secret' => 'required|string',
            'grant_type'    => 'in:password,refresh_token',
            'username'      => 'string|required_if:grant_type,password',
            'password'      => 'string|required_if:grant_type,password',
            'refresh_token' => 'string|required_if:grant_type,refresh_token',
        ];
    }

}
