<?php

use Illuminate\Http\Request;
use App\Http\Controllers\RestApi\RestApiMember as RestApiMember;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
$version = 'v1';

Route::get($version . '/member/{id}', function (Request $request, $id) {
    $controller = new RestApiMember();
    return $controller->getMember($id);
});
