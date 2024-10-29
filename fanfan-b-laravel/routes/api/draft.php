<?php

use App\Http\Controllers\API\v1\DraftController;
use App\Http\Controllers\API\v1\StadiumController;
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
  Route::group(['as' => 'draft.', 'prefix' => 'draft', 'middleware' => 'auth:api'], function () {
    Route::get('rules', [DraftController::class, 'draftRules']);
    Route::post('selections', [DraftController::class, 'storeSelections']);
  });

  Route::group(['as' => 'card.', 'prefix' => 'card', 'middleware' => 'auth:api'], function () {
    Route::get('plate_cards', [DraftController::class, 'plateCards']);
    Route::get('leagues', [DraftController::class, 'leagues']);
    Route::get('matchStatDetails/{player_id}', [DraftController::class, 'matchStatDetails']);
    Route::get('{plate_card_id}', [DraftController::class, 'plateCardInfos'])->where('plate_card_id', '[0-9]+');
    Route::get('playerInfo/{player_id}', [DraftController::class, 'playerInfos']);
    Route::get('schedules/{id}', [DraftController::class, 'fixtureSchedules']);
    Route::get('skill/{user_plate_card_id}', [DraftController::class, 'userCardSkill']);

    Route::group(['as' => 'user.', 'prefix' => 'user', 'middleware' => 'auth:api'], function () {
      Route::get('cards', [DraftController::class, 'userCardsByLeague']);
      Route::get('cardsCount', [DraftController::class, 'userCardsCount']);
      Route::get('cardsHistory', [DraftController::class, 'userCardsHistory']);
      Route::get('cardsHistoryLeagues', [DraftController::class, 'userCardHistoryLeagues']);
      Route::get('gradeCard', [DraftController::class, 'userGradeCardDetail']);
      Route::post('openCard', [DraftController::class, 'myCardOpen'])->where('id', '[0-9]+');
    });

    Route::group(['as' => 'detail.', 'prefix' => 'detail'], function () {
      Route::get('{player_id}/stats', [DraftController::class, 'getPlayerSeasonStats']);
      Route::get('{player_id}/overall', [DraftController::class, 'getPlayerOverall']);
      Route::get('{player_id}/overallAvg', [DraftController::class, 'getPlayerOverallAvg']);
      Route::get('{player_id}/info', [DraftController::class, 'getPlayerDetailInfo']);
    });

    Route::group(['as' => 'burn.', 'prefix' => 'burn'], function () {
      Route::get('{id}/info', [DraftController::class, 'burnInfo']);
      Route::post('{id}', [DraftController::class, 'burnExec']);
    });
  });

  Route::group(['as' => 'order.', 'prefix' => 'order', 'middleware' => 'auth:api'], function () {
    Route::post('plateCards', [DraftController::class, 'orderPlateCards']);

    Route::group(['as' => 'cart.', 'prefix' => 'cart'], function () {
      Route::get('', [DraftController::class, 'carts']);
      Route::post('', [DraftController::class, 'addCart']);
      Route::put('{id}', [DraftController::class, 'updateCart']);
      Route::delete('{id}', [DraftController::class, 'deleteCart']);
    });
  });
});
