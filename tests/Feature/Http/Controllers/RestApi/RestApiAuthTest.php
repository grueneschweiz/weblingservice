<?php

namespace App\Http\Controllers\RestApi\RestApiAuth;

use Tests\Feature\Http\Controllers\RestApi\AuthHelper;
use Tests\TestCase;

class RestApiAuthTest extends TestCase {
	/**
	 * @var AuthHelper
	 */
	private $auth;
    
    public function setUp(): void
    {
		parent::setUp();

		$this->auth = new AuthHelper( $this );
	}
    
    public function tearDown(): void
    {
		$this->auth->deleteToken();

		parent::tearDown();
	}

	public function test_get_200() {
		$response = $this->json( 'GET', '/api/v1/auth', [], $this->auth->getAuthHeader() );
		$response->assertStatus( 200 );
	}

	public function test_get_401_no_token() {
		$response = $this->json( 'GET', '/api/v1/auth' );
		$response->assertStatus( 401 );
	}

	public function test_get_401_invalid_token() {
		$token                  = $this->auth->getAuthHeader();
		$token['Authorization'] .= '_invalid';

		$response = $this->json( 'GET', '/api/v1/auth', [], $token );
		$response->assertStatus( 401 );
	}
}
