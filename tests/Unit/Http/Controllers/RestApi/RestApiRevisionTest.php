<?php

namespace App\Http\Controllers\RestApi;


use Tests\TestCase;

class RestApiRevisionTest extends TestCase {

	public function test_getRevision() {
		$response = $this->json( 'GET', '/api/v1/revision' );
		$response->assertStatus( 200 );
		$this->assertGreaterThan(0, json_decode( $response->getContent() ) );
	}
}
