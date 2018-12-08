<?php

namespace App\Exceptions\Handler;

use Tests\TestCase;
use App\Exceptions\Handler;
use Illuminate\Http\Request as Request;
use Illuminate\Contracts\Container\Container;

use Symfony\Component\HttpKernel\Exception\HttpException;
use Webling\API\ClientException;
use App\Exceptions\IllegalArgumentException;
use App\Exceptions\InvalidFixedValueException;
use App\Exceptions\MemberNotFoundException;
use App\Exceptions\MemberSaveException;

class HandlerTest extends TestCase {

  private $message = 'This is a test message';

  /**
  * Helper function to test different Exceptions
  */
  private function genericTestHandleException(\Exception $exception, int $expectedErrorCode, String $message = null) {
    $mockInstance = new Handler($this->createMock(Container::class));
    $request = $this->createMock(Request::class);
    $class   = new \ReflectionClass(Handler::class);
    $method  = $class->getMethod('render');
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

  public function testHandle_ClientException() {
    $this->genericTestHandleException(new ClientException($this->message), 400, 'Exception: ' . $this->message);
  }

  public function testHandle_IllegalArgumentException() {
    $this->genericTestHandleException(new IllegalArgumentException(), 400);
  }

  public function testHandle_MemberNotFoundException() {
    $this->genericTestHandleException(new MemberNotFoundException(), 404);
  }

  public function testHandle_InvalidFixedValueException() {
    $this->genericTestHandleException(new InvalidFixedValueException($this->message), 500, 'Internal Server Error: ' . $this->message);
  }

  public function testHandle_MemberSaveException() {
    $this->genericTestHandleException(new MemberSaveException($this->message), 500, 'Could not save Member.');
  }

}
