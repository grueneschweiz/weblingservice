<?php

namespace App\Support;

/**
 * Helper to retrieve the OAuth client id validated by Passport.
 */
class ClientIdentifier
{
    /**
     * Get the OAuth client id from the current authenticated request.
     *
     * @return string|null null if Passport has not authenticated the request.
     */
    public static function getClientId(): ?string
    {
        if (!app()->bound('request')) {
            return null;
        }

        $oauthClientId = request()->attributes->get('oauth_client_id');

        return $oauthClientId === null ? null : (string) $oauthClientId;
    }
}
