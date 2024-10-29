<?php

use App\Http\Controllers\ADMIN\v1\FantasyController;
use App\Http\Controllers\ADMIN\v1\PredictVoteController;
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
  Route::group(['prefix' => 'fantasy'], function () {
    Route::get('leagues', [FantasyController::class, 'leagues']);
    Route::post('leaguesUpdate', [FantasyController::class, 'leaguesUpdate']);
    Route::get('leagueTeams', [FantasyController::class, 'getLeagueWithTeams']);
    Route::get('schedules', [FantasyController::class, 'schedules']);
    Route::get('schedule', [FantasyController::class, 'scheduleDetail']);
    Route::get('gameJoins', [FantasyController::class, 'allGameJoinList']);

    // 퀘스트
    Route::group(['prefix' => 'quest'], function () {
      Route::get('codes', [FantasyController::class, 'getQuestCodes']);
      Route::get('', [FantasyController::class, 'getQuests']);
      Route::post('create', [FantasyController::class, 'saveQuest']);
      Route::get('logs', [FantasyController::class, 'getQuestLogs']);
      Route::get('logDetail', [FantasyController::class, 'getQuestLogDetail']);
    });

    Route::group(['prefix' => 'game'], function () {
      Route::get('', [FantasyController::class, 'games']);
      Route::get('{id}', [FantasyController::class, 'gameDetail'])->where('id', '[0-9]+');
      Route::get('schedules', [FantasyController::class, 'schedulesForGame']);
      // Route::get('checkGame/{id}', [FantasyController::class, 'checkGameJoin']);
      Route::get('joins/{game_id}', [FantasyController::class, 'gameJoins']);
      Route::get('joinDetail/{game_join_id}', [FantasyController::class, 'gameJoinDetail']);
      Route::post('create', [FantasyController::class, 'makeGame']);
      // TODO : route:delete
      Route::post('delete/{id}', [FantasyController::class, 'cancelGame']);
    });

    Route::group(['prefix' => 'init'], function () {
      Route::post('seed', [FantasyController::class, 'refSeedUpdate']);
    });

    Route::group(['prefix' => 'predict'], function () {
      Route::group(['prefix' => 'questions'], function () {
        Route::get('', [PredictVoteController::class, 'questionList']);
        Route::post('{id?}', [PredictVoteController::class, 'questionUpdate']);
        Route::delete('{id}', [PredictVoteController::class, 'questionDelete']);
      });
      Route::group(['prefix' => 'vote'], function () {
        Route::get('', [PredictVoteController::class, 'voteList']);
        Route::get('{id}', [PredictVoteController::class, 'voteDetail']);
        Route::post('{id?}', [PredictVoteController::class, 'voteUpdate']);
      });
    });
  });
});
