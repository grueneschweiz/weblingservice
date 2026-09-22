<?php

namespace App\Support;

use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Throwable;

/**
 * Helper to extract the OAuth client id from the current request's bearer
 * token, so log entries can be traced back to the client (form/site) that
 * caused them.
 */
class ClientIdentifier
{
    /**
     * Get the OAuth client id from the current request's bearer token.
     *
     * @return string|null null if there is no request, no bearer token or
     *                      the token cannot be parsed.
     */
    public static function getClientId(): ?string
    {
        if (!request() || !request()->bearerToken()) {
            return null;
        }

        try {
            $parser = new Parser(new JoseEncoder());
            $token = $parser->parse(request()->bearerToken());

            return $token->claims()->get('aud')[0] ?? null;
        } catch (Throwable $e) {
            return null;
        }
    }
}
