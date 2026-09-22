<?php

namespace App\Exceptions\Handler;

use App\Exceptions\Handler;
use App\Exceptions\IllegalArgumentException;
use App\Exceptions\InvalidFixedValueException;
use App\Exceptions\MemberNotFoundException;
use App\Exceptions\MemberSaveException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request as Request;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
use Webling\API\ClientException;

class HandlerTest extends TestCase
{
    
    private $message = 'This is a test message';

    public function testReportUsesLaravelReportableCallbacks(): void
    {
        $reported = false;
        $handler = app(Handler::class);
        $handler->reportable(function (RuntimeException $exception) use (&$reported) {
            $reported = true;

            return false;
        });

        $handler->report(new RuntimeException($this->message));

        $this->assertTrue($reported);
    }

    public function testExceptionContextIncludesAuthenticatedClientIdWhenEnabled(): void
    {
        config()->set('app.client_logging', true);
        request()->attributes->set('oauth_client_id', 'client-123');

        $context = app(Handler::class)->contextForException(new RuntimeException($this->message));

        $this->assertSame('client-123', $context['client_id']);
    }

    public function testExceptionContextOmitsClientIdWhenDisabled(): void
    {
        config()->set('app.client_logging', false);
        request()->attributes->set('oauth_client_id', 'client-123');

        $context = app(Handler::class)->contextForException(new RuntimeException($this->message));

        $this->assertArrayNotHasKey('client_id', $context);
    }
    
    public function testHandle_ClientException()
    {
        $this->genericTestHandleException(new ClientException($this->message), 400, 'Exception: ' . $this->message);
    }
    
    /**
     * Helper function to test different Exceptions
     */
    private function genericTestHandleException(\Exception $exception, int $expectedErrorCode, ?string $message = null)
    {
        $mockInstance = new Handler($this->createStub(Container::class));
        $request = Request::create('/');
        $class = new \ReflectionClass(Handler::class);
        $method = $class->getMethod('render');
        $method->setAccessible(true);
        try {
            $method->invokeArgs($mockInstance, [$request, $exception]);
        } catch (HttpException $e) {
            $this->assertEquals($expectedErrorCode, $e->getStatusCode());
            if ($message) {
                $this->assertEquals($message, $e->getMessage());
            }
        }
    }
    
    public function testHandle_IllegalArgumentException()
    {
        $this->genericTestHandleException(new IllegalArgumentException(), 400);
    }
    
    public function testHandle_MemberNotFoundException()
    {
        $this->genericTestHandleException(new MemberNotFoundException(), 404);
    }
    
    public function testHandle_InvalidFixedValueException()
    {
        $this->genericTestHandleException(new InvalidFixedValueException($this->message), 500, 'Internal Server Error: ' . $this->message);
    }
    
    public function testHandle_MemberSaveException()
    {
        $this->genericTestHandleException(new MemberSaveException($this->message), 500, 'Could not save Member.');
    }
    
}
