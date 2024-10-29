<?php

use App\Http\Controllers\API\v1\SimulationController;
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

Route::group(['prefix' => 'v1', 'middleware' => 'userLog'], function () {
  Route::group(['as' => 'simulation', 'prefix' => 'simulation', 'middleware' => 'auth:api'], function () {
    Route::get('applicants', [SimulationController::class, 'checkApplicant']);
    Route::post('applicants', [SimulationController::class, 'registerApplicant']);
    Route::get('lobby', [SimulationController::class, 'lobby']);
    Route::get('rank', [SimulationController::class, 'getRank']);
    Route::get('report', [SimulationController::class, 'getReport']);
    Route::get('schedule_game_list', [SimulationController::class, 'scheduleGameList']);
    Route::get('schedule_summary_season', [SimulationController::class, 'scheduleSummarySeason']);
    Route::get('schedule_summary', [SimulationController::class, 'scheduleSummary']);
    Route::get('commentary/{schedule_id}', [SimulationController::class, 'getCommentary']);
    Route::get('unchecked_game', [SimulationController::class, 'getUncheckedGame']);
    Route::get('user_rank_conclusion', [SimulationController::class, 'getUserRankConclusion']);
    Route::post('game_result_check/{schedule_id}', [SimulationController::class, 'gameResultCheck']);
    Route::post('user_rank_confirm_check/{id}', [SimulationController::class, 'userRankConfirmCheck']);
    Route::group(['as' => 'lineup', 'prefix' => 'lineup', 'middleware' => 'auth:api'], function () {
      Route::post('', [SimulationController::class, 'submitLineup']);
      Route::get('my', [SimulationController::class, 'myLineup']);
      Route::get('cards', [SimulationController::class, 'myCards']);
      Route::get('{schedule_id}', [SimulationController::class, 'gameLineups']);
    });
  });
});
