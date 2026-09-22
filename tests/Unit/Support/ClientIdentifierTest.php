<?php

namespace Tests\Unit\Support;

use App\Support\ClientIdentifier;
use Tests\TestCase;

class ClientIdentifierTest extends TestCase
{
    public function testReturnsPassportClientIdRequestAttribute(): void
    {
        request()->attributes->set('oauth_client_id', 'client-123');

        $this->assertSame('client-123', ClientIdentifier::getClientId());
    }

    public function testReturnsNullForUnvalidatedBearerToken(): void
    {
        request()->headers->set('Authorization', 'Bearer forged-token');

        $this->assertNull(ClientIdentifier::getClientId());
    }
}