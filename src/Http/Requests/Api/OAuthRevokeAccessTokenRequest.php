<?php
namespace Czim\CmsAuth\Http\Requests\Api;

use Czim\CmsAuth\Http\Requests\Request;

class OAuthRevokeAccessTokenRequest extends Request
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
            'token'           => 'required|string',
            'token_type_hint' => 'required|in:access_token,refresh_token',
        ];
    }

}
