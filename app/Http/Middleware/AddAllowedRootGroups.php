<?php

namespace App\Http\Middleware;

use Closure;
use Laravel\Passport\ClientRepository;
use Lcobucci\JWT\Parser;

/**
 * Add the allowed root groups to the request.
 *
 * @package App\Http\Middleware
 */
class AddAllowedRootGroups {
	protected $clientRepository = null;
	protected $tokenRepository = null;

	public function __construct( ClientRepository $clientRepository ) {
		$this->clientRepository = $clientRepository;
	}

	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @param  \Closure $next
	 *
	 * @return mixed
	 */
	public function handle( $request, Closure $next ) {
		/**
		 * get client from token
		 *
		 * @see https://github.com/laravel/passport/issues/143
		 */
		$jwt       = ( new Parser() )->parse( $request->bearerToken() );
		$client_id = $jwt->getClaim( 'aud' );

		// add root groups to header
		$rootGroups = \App\ClientGroup::where('client_id', $client_id);

		$groupIds = [];
		foreach ( $rootGroups as $group ) {
			$groupIds[] = $group->root_group;
		}

		$request->merge( [ 'allowed_groups' => $groupIds ] );

		return $next( $request );
	}
}
