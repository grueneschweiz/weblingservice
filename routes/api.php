<?php

use App\Http\Controllers\RestApi\RestApiGroup as RestApiGroup;
use App\Http\Controllers\RestApi\RestApiMember as RestApiMember;
use App\Http\Controllers\RestApi\RestApiRevision;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where we can register API routes for our application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

Route::group( [ 'prefix' => 'v1', 'middleware' => [ 'api' ] ], function () {
	/*
	|--------------------------------------------------------------------------
	| Member Resources
	|--------------------------------------------------------------------------
	*/
	Route::group( [ 'prefix' => 'member' ], function () {

		Route::get( '{id}', function ( Request $request, $id ) {
			$controller = new RestApiMember();

			return response( $controller->getMember( $request, $id, $is_admin = false ) )
				->header( 'Content-Type', 'application/json' )
				->setStatusCode( 200 );
		} );

		/**
		 * The request body must have the data on the 'member' key
		 */
		Route::put( '{id}', function ( Request $request, $id ) {
			$controller = new RestApiMember();
			$id = $controller->updateMember( $request, $id );

			return response( $id )
				->header( 'Content-Type', 'application/json' )
				->setStatusCode( 201 );
		} );

		/**
		 * The request body must have the data on the 'member' key
		 */
		Route::post( '', function ( Request $request ) {
			$controller = new RestApiMember();
			$id = $controller->upsertMember( $request );

			return response( $id )
				->header( 'Content-Type', 'application/json' )
				->setStatusCode( 201 );
		} );

		Route::get( '{id}/main/{groups}', function ( Request $request, $memberId, $groupIds ) {
			$controller = new RestApiMember();

			return response( $controller->getMainMember( $request, $memberId, $groupIds, $is_admin = false ) )
				->header( 'Content-Type', 'application/json' )
				->setStatusCode( 200 );
		} );

		Route::get( 'changed/{revisionId}', function ( Request $request, $revisionId ) {
			$controller = new RestApiMember();

			return response( $controller->getChanged( $request, $revisionId ) )
				->header( 'Content-Type', 'application/json' )
				->setStatusCode( 200 );
		} );

	} );

	/*
	|--------------------------------------------------------------------------
	| Admin Resources
	|--------------------------------------------------------------------------
	*/
	Route::group( [ 'prefix' => 'admin/member' ], function () {

		Route::get( '{id}', function ( Request $request, $id ) {
			$controller = new RestApiMember();

			return response( $controller->getMember( $request, $id, $is_admin = true ) )
				->header( 'Content-Type', 'application/json' )
				->setStatusCode( 200 );
		} );

		Route::get( '{id}/main/{groups}', function ( Request $request, $memberId, $groupIds ) {
			$controller = new RestApiMember();

			return response( $controller->getMainMember( $request, $memberId, $groupIds, $is_admin = true ) )
				->header( 'Content-Type', 'application/json' )
				->setStatusCode( 200 );
		} );

		Route::get( 'changed/{revisionId}', function ( Request $request, $revisionId ) {
			$controller = new RestApiMember();

			return response( $controller->getChanged( $request, $revisionId, $is_admin = true ) )
				->header( 'Content-Type', 'application/json' )
				->setStatusCode( 200 );
		} );
	} );

	/*
	|--------------------------------------------------------------------------
	| Groups Resources
	|--------------------------------------------------------------------------
	*/
	Route::group( [ 'prefix' => 'group' ], function () {

		Route::get( '{id}', function ( Request $request, $id ) {
			$controller = new RestApiGroup();

			return response( $controller->getGroup( $request, $id ) )
				->header( 'Content-Type', 'application/json' )
				->setStatusCode( 200 );
		} );

	} );

	/*
	|--------------------------------------------------------------------------
	| Revision Resources
	|--------------------------------------------------------------------------
	*/
	Route::group( [ 'prefix' => 'revision' ], function () {

		Route::get( '', function () {
			$controller = new RestApiRevision();

			return response( $controller->getRevision() )
				->header( 'Content-Type', 'application/json' )
				->setStatusCode( 200 );
		} );

	} );

	/*
	|--------------------------------------------------------------------------
	| Access Token
	|--------------------------------------------------------------------------
	*/
	Route::group( [ 'prefix' => 'auth' ], function () {

		Route::get( '', function () {
			// if we can reach this point, we do have a valid access token
			return response( '' )
				->header( 'Content-Type', 'application/json' )
				->setStatusCode( 200 );
		} );

	} );
} );