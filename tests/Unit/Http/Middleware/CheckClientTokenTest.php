<?php

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\CheckClientToken;
use Illuminate\Http\Request;
use League\OAuth2\Server\ResourceServer;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CheckClientTokenTest extends TestCase
{
    public function testAddsValidatedClientIdToRequestAttributes(): void
    {
        $server = Mockery::mock(ResourceServer::class);
        $server->shouldReceive('validateAuthenticatedRequest')->once()->andReturnUsing(
            fn ($request) => $request
                ->withAttribute('oauth_client_id', 'client-123')
                ->withAttribute('oauth_scopes', [])
        );
        $request = Request::create('/api/v1/member', 'GET');
        $request->headers->set('Authorization', 'Bearer valid-token');
        $middleware = new CheckClientToken($server);

        $response = $middleware->handle($request, function (Request $request) {
            $this->assertSame('client-123', $request->attributes->get('oauth_client_id'));

            return new Response();
        });

        $this->assertSame(200, $response->getStatusCode());
    }
}