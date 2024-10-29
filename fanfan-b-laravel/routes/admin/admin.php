<?php

use App\Http\Controllers\ADMIN\v1\AdminUser\AccountController;
use App\Http\Controllers\ADMIN\v1\AdminUser\AuthController;
use App\Http\Controllers\ADMIN\v1\OpController;
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

  Route::post('login', [AuthController::class, 'login'])->withoutMiddleware('admin');
  Route::post('store', [AccountController::class, 'store'])->withoutMiddleware('admin');
  Route::post('destroy/{userId}', [AccountController::class, 'destroy']);
  Route::post('edit/{id}', [AccountController::class, 'edit']);
  Route::get('list', [AccountController::class, 'main']);

  Route::group(['prefix' => 'op', 'middleware' => 'adminAuth:admin'], function () {
    Route::get('dashboard', [OpController::class, 'dashboard']);
    Route::group(['prefix' => 'settings'], function () {
      Route::group(['prefix' => 'banner'], function () {
        Route::get('', [OpController::class, 'bannerList']);
        Route::post('{id?}', [OpController::class, 'bannerUpdate']);
        Route::delete('{id}', [OpController::class, 'bannerDelete']);
      });
    });
    Route::post('block_ip', [OpController::class, 'blockIp']);
    Route::delete('block_ip/{id}', [OpController::class, 'blockIpDelete']);
  });
});
