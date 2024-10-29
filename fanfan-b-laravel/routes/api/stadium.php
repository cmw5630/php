<?php

use App\Http\Controllers\API\v1\LiveController;
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
  Route::group(['as' => 'game', 'prefix' => 'game', 'middleware' => 'auth:api'], function () {
    Route::group(['prefix' => 'free'], function () {
      Route::get('lineup', [StadiumController::class, 'getFreeLineup']);
      Route::put('lineup', [StadiumController::class, 'addFreeLineup']);
      Route::post('lineup', [StadiumController::class, 'submitFreeLineup']);
      Route::put('shuffle', [StadiumController::class, 'shuffleFreeCard']);
      Route::get('shuffle', [StadiumController::class, 'getShuffleFreeCard']);
      Route::post('shuffle', [StadiumController::class, 'makeShuffleFreeCard']);
      Route::put('shuffle/open', [StadiumController::class, 'openShuffleCard']);
    });

    // lineup group
    Route::group(['prefix' => 'lineup'], function () {
      Route::get('{id}/cards', [StadiumController::class, 'ingameCardList']);
      Route::get('{id}/cards2', [StadiumController::class, 'ingameCardList2']);
      Route::post('{id}/join', [StadiumController::class, 'joinGame']);
      Route::post('{id}/join2', [StadiumController::class, 'joinGame2']);
      Route::get('{id}', [StadiumController::class, 'lineups']);
      // 사용 X
      // Route::get('schedules/{date}', [StadiumController::class, 'scheduleList']);
    });

    Route::get('seasons', [StadiumController::class, 'seasonWithGames']);
    Route::group(['prefix' => 'main'], function () {
      Route::get('', [StadiumController::class, 'main']);
      Route::get('games', [StadiumController::class, 'getGames']);
      Route::get('{id}/detail', [StadiumController::class, 'getGameDetail'])->where('id', '[0-9]+');
      // Route::get('main/logs', [StadiumController::class, 'getGameLogs']);
      // Route::get('main/{id}/top3', [StadiumController::class, 'getGameTopUser']);
      Route::post('predict_vote/{id}', [StadiumController::class, 'predictVote']);
    });
    Route::get('{id}', [StadiumController::class, 'getGameInfo'])->where('id', '[0-9]+');
    Route::post('quest/{id}', [StadiumController::class, 'giveQuestReward']);
    Route::get('ranking', [StadiumController::class, 'userRanking']);

    // live group
    Route::group(['prefix' => 'live'], function () {
      Route::get('headtohead/main', [LiveController::class, 'showHeadToHeadMain']);
      Route::get('headtohead/main/stats', [LiveController::class, 'matchPreviewLast5']);
      Route::get('headtohead/side', [LiveController::class, 'showHeadToHeadSide']);
      Route::get('headtohead/side/stats', [LiveController::class, 'showHeadToHeadSideStats']);
      Route::get('formation', [LiveController::class, 'showLiveLineup']);
      Route::get('timeline', [LiveController::class, 'getTimeline']);
      Route::get('user_rank', [LiveController::class, 'getUserRanking']);
      Route::get('my_rank', [LiveController::class, 'getMyRanking']);
      Route::get('user_lineup', [LiveController::class, 'getUserLineup']);
      Route::get('lineup_detail', [LiveController::class, 'getLineupDetail']);
      Route::get('momentum', [LiveController::class, 'getMomentum']);
      Route::get('commentary', [LiveController::class, 'getCommentary']);
    });

    Route::group(['prefix' => 'log'], function () {
      Route::get('player', [StadiumController::class, 'gameLogPlayer']);
      Route::get('list', [StadiumController::class, 'gameLogList']);
    });
  });
});
