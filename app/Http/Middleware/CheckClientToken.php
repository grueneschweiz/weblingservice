<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Laravel\Passport\AccessToken;
use Laravel\Passport\Contracts\ScopeAuthorizable;
use Laravel\Passport\Http\Middleware\CheckToken;

class CheckClientToken extends CheckToken
{
    protected function validateToken(Request $request): ScopeAuthorizable
    {
        $token = parent::validateToken($request);

        if ($token instanceof AccessToken) {
            $request->attributes->set('oauth_client_id', $token->oauth_client_id);
        }

        return $token;
    }
}