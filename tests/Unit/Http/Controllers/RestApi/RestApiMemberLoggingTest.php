<?php

namespace Tests\Unit\Http\Controllers\RestApi;

use App\Http\Controllers\RestApi\RestApiMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class RestApiMemberLoggingTest extends TestCase
{
    public function testExtractMemberDataDoesNotLog(): void
    {
        config()->set('app.log_member_sync_payloads', true);
        Log::spy();
        $request = Request::create('/api/v1/member/match', 'POST', [], [], [], [], '{"email1":{"value":"test@example.com"}}');

        $method = new ReflectionMethod(RestApiMember::class, 'extractMemberData');
        $arguments = [&$request];
        $memberData = $method->invokeArgs(new RestApiMember(), $arguments);

        $this->assertSame('test@example.com', $memberData['email1']['value']);
        Log::shouldNotHaveReceived('info');
    }

    public function testPayloadAndResultLogsShareCorrelationId(): void
    {
        config()->set('app.log_member_sync_payloads', true);
        request()->attributes->set('oauth_client_id', 'client-123');
        Log::spy();
        $request = Request::create('/api/v1/member', 'POST', [], [], [], [], '{"email1":{"value":"test@example.com"}}');
        $request->attributes->set('oauth_client_id', 'client-123');
        $controller = new RestApiMember();

        $logPayload = new ReflectionMethod(RestApiMember::class, 'logSyncPayload');
        $correlationId = $logPayload->invoke($controller, $request);
        $logResult = new ReflectionMethod(RestApiMember::class, 'logSyncResult');
        $logResult->invoke($controller, 42, $correlationId);

        $this->assertNotEmpty($correlationId);
        Log::shouldHaveReceived('info')->with(
            'Member sync request received',
            Mockery::on(fn (array $context) => $context['correlation_id'] === $correlationId)
        )->once();
        Log::shouldHaveReceived('info')->with(
            'Member sync request completed',
            Mockery::on(fn (array $context) => $context['correlation_id'] === $correlationId
                && $context['member_id'] === 42)
        )->once();
    }
}