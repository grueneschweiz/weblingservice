<?php

namespace Tests\Unit\Repository\Member;

use App\Repository\Member\MemberMatch;
use ReflectionMethod;
use Tests\TestCase;

class MemberMatchLoggingTest extends TestCase
{
    public function testLogContextOmitsClientIdWhenDisabled(): void
    {
        config()->set('app.client_logging', false);
        request()->attributes->set('oauth_client_id', 'client-123');

        $context = $this->getLogContext();

        $this->assertArrayNotHasKey('client_id', $context);
    }

    public function testLogContextIncludesAuthenticatedClientIdWhenEnabled(): void
    {
        config()->set('app.client_logging', true);
        request()->attributes->set('oauth_client_id', 'client-123');

        $context = $this->getLogContext();

        $this->assertSame('client-123', $context['client_id']);
    }

    private function getLogContext(): array
    {
        $method = new ReflectionMethod(MemberMatch::class, 'getLogContext');

        return $method->invoke(null, 'email = test@example.com', []);
    }
}