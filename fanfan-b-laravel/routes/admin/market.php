<?php

use App\Http\Controllers\ADMIN\v1\MarketController;
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

Route::group(['prefix' => 'v1', 'middleware' => 'adminAuth:admin'], function () {
  Route::group(['prefix' => 'market'], function () {
    Route::middleware('adminAuth:admin')->group(function () {
      Route::get('', [MarketController::class, 'list']);
      Route::get('auction/{id}', [MarketController::class, 'auctionDetail']);
      Route::get('auction/{id}/history', [MarketController::class, 'auctionDetailHistory']);
    });
  });
});
