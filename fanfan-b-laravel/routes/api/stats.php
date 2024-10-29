<?php

use App\Http\Controllers\API\v1\DraftController;
use App\Http\Controllers\API\v1\StatController;
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
  Route::group(['as' => 'stats.', 'prefix' => 'stats', /*'middleware' => 'auth:api'*/], function () {
    Route::get('', [StatController::class, 'list']);
    Route::get('leagues', [DraftController::class, 'leagues']);
    Route::get('/{league}/clubs', [DraftController::class, 'clubs']);
    Route::get('userCardsByLeague', [DraftController::class, 'userCardsByLeague'])->middleware('auth:api');
    Route::get('userCardsCount', [DraftController::class, 'userCardsCount'])->middleware('auth:api');

    Route::group(['as' => 'detail.', 'prefix' => 'detail',], function () {
      Route::get('player/{player_id}', [DraftController::class, 'playerDetail']);
      Route::post('player/{player_id}/plateCardUserLike', [DraftController::class, 'plateCardUserLike']);
      Route::get('player/{player_id}/records', [DraftController::class, 'playerDetailStats']);
      Route::get('team/{team_id}', [StatController::class, 'teamDetailTop']);
      Route::get('team/{team_id}/info', [StatController::class, 'teamDetail']);
      Route::get('team/{team_id}/stats', [StatController::class, 'teamDetailStats']);
      Route::get('team/{team_id}/schedules', [StatController::class, 'teamDetailSchedules']);
      Route::get('schedules/{schedule_id}', [StatController::class, 'teamDetailView']);
      Route::get('team/{team_id}/squad', [StatController::class, 'teamDetailSquad']);
      Route::get('team/{team_id}/transfer', [StatController::class, 'teamDetailTransfer']);
      Route::post('team/vote', [StatController::class, 'teamVote']);
    });
  });
});
