<?php

namespace App\Http\Controllers\RestApi;


use Tests\TestCase;
use Tests\Unit\Http\Controllers\RestApi\AuthHelper;

class RestApiRevisionTest extends TestCase {
	/**
	 * @var AuthHelper
	 */
	private $auth;

	public function setUp() {
		parent::setUp();

		$this->auth = new AuthHelper($this);
	}

	public function tearDown() {
		$this->auth->deleteToken();

		parent::tearDown();
	}

	public function test_getRevision() {
		$response = $this->json( 'GET', '/api/v1/revision', [], $this->auth->getAuthHeader() );
		$response->assertStatus( 200 );
		$this->assertGreaterThan( 0, json_decode( $response->getContent() ) );
	}

	public function test_getRevision_401() {
		$response = $this->json( 'GET', '/api/v1/revision' );
		$response->assertStatus( 401 );
	}
}
