<?php

use App\Http\Controllers\API\v1\CommonController;
use App\Http\Controllers\API\v1\DraftController;
use App\Http\Controllers\API\v1\HomeController;
use App\Http\Controllers\API\v1\PreFetchController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

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
  // 406 용 test api
  Route::get('test/{code?}', function ($code = 200) {
    $code = (int) $code;
    if ($code === 200) {
      return ReturnData::send(Response::HTTP_OK);
    }
    return ReturnData::setError(null)->send($code);

  });
  Route::get('prefetch', PreFetchController::class);
  Route::get('teams', [DraftController::class, 'leagues']);

  Route::group(['prefix' => 'schedule'], function () {
    Route::get('leagues', [CommonController::class, 'leaguesWithRound']);
    Route::get('list', [CommonController::class, 'roundSchedules']);
  });

  Route::get('codes', [CommonController::class, 'codes']);

  Route::group(['middleware' => 'auth:api'], function () {
    Route::get('search', [CommonController::class, 'headerSearch'])->middleware('auth:api');
    Route::group(['prefix' => 'alarm'], function () {
      Route::get('', [CommonController::class, 'alarmList']);
      Route::post('all', [CommonController::class, 'alarmUpdate']);
      Route::post('{id}', [CommonController::class, 'alarmUpdate'])->where('id', '[0-9]+');
    });
  });
});
