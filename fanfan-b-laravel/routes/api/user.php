<?php

use App\Http\Controllers\API\v1\User\AccessTokenController;
use App\Http\Controllers\API\v1\User\SocialController;
use App\Http\Controllers\API\v1\User\UserController;
use Illuminate\Support\Facades\Route;
// mail send test
use App\Http\Controllers\API\v1\User\MailController;

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
  // 인증
  Route::group(['as' => 'oauth.', 'prefix' => 'oauth'], function () {
    // login - token generate
    Route::post('token', [AccessTokenController::class, 'login'])->name('login');
    // logout - token revoke
    Route::middleware('auth:api')->post('revoke', [AccessTokenController::class, 'revoke']);

    // social
    Route::post('{provider}/authcode', [SocialController::class, 'socialAuthCode']);
    // social 필수 입력
    Route::post('socialConfirm', [SocialController::class, 'socialConfirm']);

    // ses mail send test
    Route::post('mailtest', [MailController::class, 'mailtest']);
  });

  // 사용자 데이터
  Route::group(['prefix' => 'user'], function () {
    Route::middleware('auth:api')->group(function () {
      Route::get('me', [UserController::class, 'me']);
      Route::get('welcome_pack', [UserController::class, 'welcomePack']);
      Route::post('welcome_pack_open_check', [UserController::class, 'welcomePackOpenCheck']);
      Route::post('changePassword', [UserController::class, 'changePassword']);
      Route::post('checkPassword', [UserController::class, 'checkPassword']);
      Route::post('withdraw', [UserController::class, 'withdraw']);
      Route::post('modify', [UserController::class, 'modifyUserInfo']);
      Route::post('redeem', [UserController::class, 'redeemRegister']);
    });

    Route::post('validation', [UserController::class, 'validation']);
    Route::post('signup', [UserController::class, 'signup']);
  });
});
