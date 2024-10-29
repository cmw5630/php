<?php

use App\Http\Controllers\ADMIN\v1\DraftController;
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
  Route::group(['prefix' => 'draft'], function () {
    Route::get('prices', [DraftController::class, 'getDraftPrices']);
    Route::put('prices', [DraftController::class, 'setDraftPrices']);
    Route::get('cards', [DraftController::class, 'cards']);
    Route::get('orders', [DraftController::class, 'orders']);
    Route::get('upgrades', [DraftController::class, 'upgrades']);
    Route::get('upgrades/{id}/detail', [DraftController::class, 'draftDetail']);

    Route::get('gprices', [DraftController::class, 'getGradePrices']);
    Route::post('gprices', [DraftController::class, 'setGradePrices']);
  });
  Route::group(['prefix' => 'player'], function () {
    // 선수관리 -->
    Route::get('overactive', [DraftController::class, 'playerManageOverActive']);

    Route::get('oversquad', [DraftController::class, 'playerManageOverSquad']);

    Route::get('{type}', [DraftController::class, 'playerManage'])->where('type', 'all|overcard');

    // -->> same logic
    Route::post('overactive/selectcard', [DraftController::class, 'selectCard']);
    Route::post('oversquad/makecard', [DraftController::class, 'makeCard']);
    // <<--

    Route::post('overcard/removecard', [DraftController::class, 'removeCard']);


    // Route::put('correct/overactive', [DraftController::class, 'correctOverActive']);
    // Route::put('correct/oversquad', [DraftController::class, 'correctOverSquad']);
    // Route::put('correct/overcard', [DraftController::class, 'correctOverCard']);

    // Route::get('download/overactive', [DraftController::class, 'downloadOverActive']);
    // Route::get('download/oversquad', [DraftController::class, 'downloadOverSquad']);
    // Route::get('download/overcard', [DraftController::class, 'downloadOverCard']);

    // <--
  });
});
