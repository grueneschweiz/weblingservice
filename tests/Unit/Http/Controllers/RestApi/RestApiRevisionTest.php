<?php

namespace App\Http\Controllers\RestApi;


use Tests\TestCase;

class RestApiRevisionTest extends TestCase {

	public function test_getRevision() {
		$auth = $this->post( '/oauth/token', [
				'grant_type'    => 'client_credentials',
				'client_id'     => '1',
				'client_secret' => 'A5SLmXcXyzfpLmbGyX7LvB6pnbkVwTbECiFwtCpR',
				'scope'         => '',
			]
		);

		$token = json_decode( $auth->getContent() )->access_token;

		$response = $this->json( 'GET', '/api/v1/revision', [], [ 'Authorization' => 'Bearer ' . $token ] );
		$response->assertStatus( 200 );
		$this->assertGreaterThan( 0, json_decode( $response->getContent() ) );
	}
}
