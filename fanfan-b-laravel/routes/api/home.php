<?php

use App\Http\Controllers\API\v1\CommonController;
use App\Http\Controllers\API\v1\HomeController;
use App\Http\Controllers\API\v1\PreFetchController;
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

Route::group(['prefix' => 'v1'], function () {
  Route::get('main', [HomeController::class, 'main']);
  Route::get('bestLineup', [HomeController::class, 'bestLineup']);
});
