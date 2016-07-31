FORMAT: 1A
HOST: auth

# Authentication

CMS Authentication API endpoints.

# Group OAuth2

OAuth2 token authorization and revocation.

# Issue Tokens [/issue]

## Issue a new Access Token [POST]

Issues a new access token and starts an API session for a user on providing valid credentials.

The only supported grant types are `password` and `refresh_token`.


+ Request Password Grant (application/json)

    + Attributes (OAuth Grant Password Request)

+ Request Refresh Token Grant (application/json)
    
    + Attributes (OAuth Grant Refresh Request)

+ Response 200 (application/json)

        {
             "access_token": "P731h8bKkq3uf100yviL9U7Lmhh31D3zJvkzIdyI",
             "token_type": "Bearer",
             "expires_in": 3600,
             "refresh_token": "2QqPvkpSOAMBDheGvf4Rva42AgCv38WEHc9aoWbc"
        }


+ Response 401 (application/json)

        {
            "message": "Client authentication failed."
        }

+ Response 401 (application/json)

        {
            "message": "The user credentials were incorrect."
        }

+ Response 401 (application/json)

        {
            "message": "The refresh token is invalid."
        }

+ Response 422 (application/json)

        {
            "message": "Unprocessable Entity",
            "data": {
                "grant_type": [
                    "validation.in"
                ]
            }
        }


# Revoke Tokens [/revoke]

## Revoke a Token [POST]

Revokes an access or refresh token. 

You must be succesfully authorized for this to be succesful, and the token to revoke must belong to the authorized user.
  
Note that once authorization succeeds, this will always respond with a `200` OK, regardless of whether the revocation could be performed.

Revocation is implemented roughly according to [RFC7009](https://tools.ietf.org/html/rfc7009).

+ Request Password Revoke (application/json)

    + Attributes (OAuth Revoke Access Token Request)

+ Request Refresh Token Revoke (application/json)
    
    + Attributes (OAuth Revoke Refresh Token Request)

+ Response 200 (application/json)

+ Response 401 (application/json)

        {
            message": "The resource owner or authorization server denied the request."
        }

+ Response 422 (application/json)

        {
            "message": "Unprocessable Entity",
            "data": {
                "token_type_hint": [
                    "validation.in"
                ]
            }
        }

# Data Structures

## OAuth Grant Password Request (object)
+ `client_id`: 4c0d94df2cf1f1aa8ae0c782ba9109e1 (string, required)
+ `client_secret`: 0f06173fbd6b85103037001aa9350f2d (string, required)
+ `grant_type`: `password` (string, required)
+ username: some@user.com (string, required) - Required when using `password` grant type
+ password: sl5Yodlk (string, required) - Required when using `password` grant type

## OAuth Grant Refresh Request (object)
+ `client_id`: 4c0d94df2cf1f1aa8ae0c782ba9109e1 (string, required)
+ `client_secret`: 0f06173fbd6b85103037001aa9350f2d (string, required)
+ `grant_type`: `refresh_token` (string, required)
+ `refresh_token`: 2QqPvkpSOAMBDheGvf4Rva42AgCv38WEHc9aoWbc (string, required) - Required when using `refresh_token` grant type

## OAuth Revoke Access Token Request (object)
+ token: P731h8bKkq3uf100yviL9U7Lmhh31D3zJvkzIdyI (string, required)
+ `token_type_hint`: `access_token` (string, required)

## OAuth Revoke Refresh Token Request (object)
+ token: 2QqPvkpSOAMBDheGvf4Rva42AgCv38WEHc9aoWbc (string, required)
+ `token_type_hint`: `refresh_token` (string, required)

## OAuth Valid Response (object)
+ `access_token`: P731h8bKkq3uf100yviL9U7Lmhh31D3zJvkzIdyI (string, required) - valid access token
+ `token_type`: Bearer (string, required)
+ `expires_in`: 3600 (number, required)
+ `refresh_token`: 2QqPvkpSOAMBDheGvf4Rva42AgCv38WEHc9aoWbc (string, required) - valid refresh token
