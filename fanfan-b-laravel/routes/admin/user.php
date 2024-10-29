<?php

use App\Enums\Opta\Card\PlateCardStatus;
use App\Http\Controllers\ADMIN\v1\UserController;
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
  Route::group(['prefix' => 'user'], function () {
    Route::middleware('adminAuth:admin')->group(function () {
      Route::get('list', [UserController::class, 'list']);
      Route::get('{id}', [UserController::class, 'detail'])->where('id', '[0-9]+');
      Route::get('me', [UserController::class, 'me']);
      Route::post('{id}/restrict', [UserController::class, 'restrictStore']);
      Route::delete('{id}/restrict', [UserController::class, 'restrictDelete']);
      Route::group(['prefix' => 'restrict'], function () {
        Route::get('', [UserController::class, 'restrictList']);
        Route::get('search', [UserController::class, 'searchForRestrict']);
      });
      Route::get('login_history', [UserController::class, 'loginHistory']);
      Route::get('login_history/detail', [UserController::class, 'loginHistoryIpDetail']);
      Route::group(['prefix' => 'cards'], function () {
        Route::get('grade/detail', [UserController::class, 'userCardGradeDetail']);
        Route::get('{status}', [UserController::class, 'userCards'])->whereIn(
          'status',
          [PlateCardStatus::PLATE, PlateCardStatus::COMPLETE]
        );
      });
    });
  });
});
