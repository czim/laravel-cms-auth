# CMS for Laravel - Auth Component

Authentication component for the CMS.


## API Documentation

The documentation for auth component API endpoints: 
https://czim.github.io/laravel-cms-auth


## Authenticating with the CMS API

This package uses [Luca Degasperi's OAuth2 Server package](https://github.com/lucadegasperi/oauth2-server-laravel)
for API authentication, slightly modified to allow it to be used inobtrusively with the CMS.

### Issueing tokens

Logging in, or getting issued an access token may be done using either the `password` or `refresh_token` grant.
Signing in a user by their credentials is done by sending a `POST` request to `/cms-api/auth/issue` with the following data:

```json
{
    "client_id":     "<the OAuth2 client id here>",
    "client_secret": "<the OAuth2 client secret here>",
    "grant_type":    "password",
    "username":      "<your username here>",
    "password":      "<your password here>"
}
```

If you have a refresh token, you can attempt to use it with:

```json
{
    "client_id":     "<the OAuth2 client id here>",
    "client_secret": "<the OAuth2 client secret here>",
    "grant_type":    "refresh_token",
    "refresh_token": "<your refresh token>"
}
```

The server may respond with `422` validation errors for these requests.

### Revoking tokens

Logging out, or revoking tokens, is implemented roughly according to [RFC7009](https://tools.ietf.org/html/rfc7009).

Send a `POST` request to `/cms-api/auth/revoke`, with a valid Authorization header, with the following data, 
to revoke your access token:

```json
{
    "token": "<your access token here>",
    "token_type_hint": "access_token"
}
```

If you want to stay logged in, but only revoke your *refresh* token:

```json
{
    "token": "<your refresh token here>",
    "token_type_hint": "refresh_token"
}
```

Note that, in compliance with the RFC, invalid tokens will be silently ignored.
The server will always respond with a `200 OK` (unless the bearer token fails to authorize).


## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.


## Credits

- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[link-contributors]: ../../contributors
