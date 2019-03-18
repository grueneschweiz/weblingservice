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

			return $controller->getMember( $request, $id, $is_admin = false );
		} );

		Route::get( '{id}/main/{groups}', function ( Request $request, $memberId, $groupIds ) {
			$controller = new RestApiMember();

			return $controller->getMainMember( $request, $memberId, $groupIds, $is_admin = false );
		} );

		Route::get( 'changed/{revisionId}', function ( Request $request, $revisionId ) {
			$controller = new RestApiMember();

			return $controller->getChanged( $request, $revisionId );
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

			return $controller->getMember( $request, $id, $is_admin = true );
		} );

		Route::get( '{id}/main/{groups}', function ( Request $request, $memberId, $groupIds ) {
			$controller = new RestApiMember();

			return $controller->getMainMember( $request, $memberId, $groupIds, $is_admin = true );
		} );

		Route::get( 'changed/{revisionId}', function ( Request $request, $revisionId ) {
			$controller = new RestApiMember();

			return $controller->getChanged( $request, $revisionId, $is_admin = true );
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

			return $controller->getGroup( $id );
		} );

	} );

	/*
	|--------------------------------------------------------------------------
	| Revision Resources
	|--------------------------------------------------------------------------
	*/
	Route::group( [ 'prefix' => 'revision' ], function () {

		Route::get( '', function ( Request $request ) {
			$controller = new RestApiRevision();

			return $controller->getRevision();
		} );

	} );
} );