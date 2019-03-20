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
	private $secret;

	public function __construct( TestCase $testClass ) {
		$this->testClass = $testClass;
	}

	public function getToken( array $allowedGroups, string $scope ) {
		if ( $this->token ) {
			return $this->token;
		}

		$this->addClient()->addRootGroups( $allowedGroups );

		$auth = $this->testClass->post( '/oauth/token', [
				'grant_type'    => 'client_credentials',
				'client_id'     => $this->id,
				'client_secret' => $this->secret,
				'scope'         => $scope,
			]
		);

		$this->token = json_decode( $auth->getContent() )->access_token;

		return $this->token;
	}

	public function getAuthHeader( array $allowedGroups = [ 100 ], string $scope = '' ) {
		return [ 'Authorization' => 'Bearer ' . $this->getToken( $allowedGroups, $scope ) ];
	}

	public function deleteToken() {
		DB::table( 'groups_clients' )->where( 'client_id', '=', $this->id )->delete();
		DB::table( 'oauth_clients' )->where( 'id', '=', $this->id )->delete();
	}

	private function addRootGroups( array $rootGroups ) {
		foreach ( $rootGroups as $group ) {
			DB::table( 'groups_clients' )->insert( [
				'client_id'  => $this->id,
				'root_group' => $group
			] );
		}
	}

	private function addClient() {
		Artisan::call( 'passport:client', [ '--client' => true, '--name' => 'unit test token' ] );

		preg_match( "/Client ID: (\d+)\s*Client secret: (\w+)/", Artisan::output(), $matches );

		$this->id     = $matches[1];
		$this->secret = $matches[2];

		return $this;
	}
}