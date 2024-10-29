<?php
use App\Http\Controllers\API\v1\CommonController;
use App\Http\Controllers\API\v1\HomeController;
use App\Http\Controllers\API\v1\MarketController;
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
  Route::group(['as' => 'market.', 'prefix' => 'market', 'middleware' => 'auth:api'], function () {
    Route::get('', [MarketController::class, 'list']);
    Route::get('my', [MarketController::class, 'myList']);
    Route::get('card_list', [MarketController::class, 'cardList']);
    Route::get('similar/{id}', [MarketController::class, 'similar'])->where('id', '[0-9]+');
    Route::get('transaction/{id}', [MarketController::class, 'transaction'])->where('id', '[0-9]+');
    Route::get('{id}', [MarketController::class, 'bidHistory'])->where('id', '[0-9]+');

    Route::post('{id?}', [MarketController::class, 'store']);
    Route::post('{id}/cancel', [MarketController::class, 'cancel']);
    Route::post('{id}/buy_now', [MarketController::class, 'buyNow']);
    Route::post('{id}/bid', [MarketController::class, 'bid']);
    Route::post('{id}/choose', [MarketController::class, 'chooseBid']);
  });
});
