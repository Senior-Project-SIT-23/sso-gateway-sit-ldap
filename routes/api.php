<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token,Authorization');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');

Route::post('/login', 'LDAPController@attemptLogin');
Route::get('/oauth/token', 'AuthController@getTokenByAuthCode');
Route::get('/applications/{app_id}', 'AuthController@getClient');

Route::group(['middleware' => ['checkAuth']], function () {
    Route::get('/login/auth-code', 'AuthController@getAuthCodeByUserId');
    Route::get('/logout', 'AuthController@logout');
    Route::get('/me', 'AuthController@getUserByToken');
});
