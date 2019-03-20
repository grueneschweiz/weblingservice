<?php

namespace App\Http\Controllers\RestApi\RestApiGroup;

use Tests\Feature\Http\Controllers\RestApi\AuthHelper;
use Tests\TestCase;

class RestApiGroupTest extends TestCase {
	const GROUP_ID = 1081;
	/**
	 * @var AuthHelper
	 */
	private $auth;

	public function setUp() {
		parent::setUp();

		$this->auth = new AuthHelper( $this );
	}

	public function tearDown() {
		$this->auth->deleteToken();

		parent::tearDown();
	}

	public function test_getGroup() {
		$response = $this->json( 'GET', '/api/v1/group/' . self::GROUP_ID, [], $this->auth->getAuthHeader() );
		$response->assertStatus( 200 );

		$group = json_decode( $response->getContent() );

		$this->assertEquals( self::GROUP_ID, $group->id );
	}

	public function test_getGroup_403() {
		$response = $this->json( 'GET', '/api/v1/group/' . self::GROUP_ID, [], $this->auth->getAuthHeader( [ 1084 ] ) );
		$response->assertStatus( 403 );
	}

	public function test_getGroup_401() {
		$response = $this->json( 'GET', '/api/v1/group/' . self::GROUP_ID );
		$response->assertStatus( 401 );
	}

	public function test_getGroupNotFound() {
		$response = $this->json( 'GET', '/api/v1/group/1', [], $this->auth->getAuthHeader() );
		$response->assertStatus( 404 );
	}
}
