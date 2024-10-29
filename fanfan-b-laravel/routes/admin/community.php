<?php

use App\Http\Controllers\ADMIN\v1\CommunityController;
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
  Route::group(['as' => 'community.', 'prefix' => 'community', 'middleware' => 'adminAuth:admin'],
    function () {
      Route::customResource('', CommunityController::class, 'Admin');
      Route::post('{post_id}/restrict', [CommunityController::class, 'restrict']);
      Route::get('{post_id}/comment', [CommunityController::class, 'commentListAdmin']);
      Route::get('{post_id}/comment/{comment_id}/replies',
        [CommunityController::class, 'commentReplyListAdmin']);
      Route::post('upload', [CommunityController::class, 'imageUpload']);
    });
});
