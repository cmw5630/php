<?php

use App\Http\Controllers\API\v1\DevController;
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
  Route::group(['as' => 'dev.', 'prefix' => 'dev', 'middleware' => 'auth:api'], function () {
    Route::get('randomLineup/{gameId}', [DevController::class, 'randomLineup']);
    Route::get('makeGame/{date}', [DevController::class, 'makeGame']);
    Route::get('randomDraft/{scheduleId}', [DevController::class, 'randomDraft']);
    Route::post('deleteJoin/{gameId}', [DevController::class, 'deleteJoinRecord']);
    Route::post('ref/seed', [DevController::class, 'refSeedUpdate']);
  });
});
