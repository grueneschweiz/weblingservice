<?php
/**
 * Created by PhpStorm.
 * User: cyrill.bolliger
 * Date: 2019-03-06
 * Time: 11:12
 */

namespace Tests\Feature\Http\Controllers\RestApi;


use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthHelper {
	private $token;
	private $id;
	private $testClass;

	public function __construct( TestCase $testClass ) {
		$this->testClass = $testClass;
	}

	public function getToken( string $scope ) {
		if ( $this->token ) {
			return $this->token;
		}

		Artisan::call( 'passport:client', [ '--client' => true, '--name' => 'unit test token' ] );

		preg_match("/Client ID: (\d+)\s*Client secret: (\w+)/", Artisan::output(), $matches);

		$this->id = $matches[1];
		$secret = $matches[2];

		$auth = $this->testClass->post( '/oauth/token', [
				'grant_type'    => 'client_credentials',
				'client_id'     => $this->id,
				'client_secret' => $secret,
				'scope'         => $scope,
			]
		);

		$this->token = json_decode( $auth->getContent() )->access_token;

		return $this->token;
	}

	public function getAuthHeader( string $scope = '' ) {
		return [ 'Authorization' => 'Bearer ' . $this->getToken( $scope ) ];
	}

	public function deleteToken() {
		DB::table( 'oauth_clients' )->where( 'id', '=', $this->id )->delete();
	}
}