<?php

namespace App\Http\Controllers\RestApi\RestApiMember;

use Tests\TestCase;
use App\Http\Controllers\RestApi\RestApiMember;
use Illuminate\Http\Request as Request;
use App\Exceptions\MemberNotFoundException;
use App\Exceptions\IllegalArgumentException;
use Webling\API\ClientException;

class RestApiMemberTest extends TestCase {


  private function getRestApiMember() {
    return new RestApiMember();
  }


  // public function testGetMember_MemberNotFoundException() {
  //   $api = $this->getRestApiMember();
  //   $request = new Request();

  //   $this->expectException( MemberNotFoundException::class );
  //   $test = $api->getMember($request, '11');
  // }

  public function testGetMember_WeblingClientException() {
    $api = $this->getRestApiMember();
    $request = new Request();
    $request->headers->set('db-key', 'NotCorrect');

    $this->expectException( ClientException::class );
    $test = $api->getMember($request, '1');
  }

  // public function testGetMember_noWeblingClientException() {
  //   $api = $this->getRestApiMember();
  //   $request = new Request();
  //   $request->headers->set('db-key', str_repeat( 'a', 32 ));

  //   //it still does not find the member
  //   $this->expectException( MemberNotFoundException::class );
  //   $test = $api->getMember($request, 1);
  // }

}
