<?php

use Illuminate\Http\Request;
use App\Http\Controllers\RestApi\RestApiMember as RestApiMember;
use \App\Http\Controllers\RestApi\RestApiGroup as RestApiGroup;

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

/*
|--------------------------------------------------------------------------
| Member Resources
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'v1/member'], function() {

  Route::get('{id}', function (Request $request, $id) {
      $controller = new RestApiMember();
      return $controller->getMember($request, $id, $is_admin = false);
  });

  Route::get('changed/{revisionId}', function (Request $request, $revisionId) {
      $controller = new RestApiMember();
      return $controller->getChanged($request, $revisionId);
  });

});

/*
|--------------------------------------------------------------------------
| Admin Resources
|--------------------------------------------------------------------------
*/
Route::group(['prefix' => 'v1/admin'], function() {

  Route::get('member/{id}', function (Request $request, $id) {
      $controller = new RestApiMember();
      return $controller->getMember($request, $id, $is_admin = true);
  });

});

Route::get($version . '/group/{id}', function (Request $request, $id) {
   $controller = new RestApiGroup();
   return $controller->getGroup($id);
});
