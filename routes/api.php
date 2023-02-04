<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\PreferencesController;
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

Route::post('/register', [AuthController::class, 'createUser']);
Route::post('/login', [AuthController::class, 'loginUser']);
Route::post('/logout', [AuthController::class, 'logOut']);

Route::group(['middleware' => 'auth.if.has.token'], function () {

    Route::get('/getArticles', [ArticleController::class, 'getArticles']);
    Route::get('/filterableFields', [ArticleController::class, 'filterableFields']);

});


Route::group(['middleware' => 'auth:sanctum'], function () {

    Route::get('/preferences', [PreferencesController::class, 'getPreferencesPageResources']);
    Route::post('/preferences', [PreferencesController::class, 'savePreferences']);


    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});

