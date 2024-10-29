<?php

use App\Http\Controllers\API\v1\CommunityController;
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
  Route::group(['as' => 'community.', 'prefix' => 'community', 'middleware' => 'auth:api'], function () {
    Route::customResource('', CommunityController::class);
    Route::get('{post_id}/comment', [CommunityController::class, 'commentList']);
    Route::post('{post_id}/comment', [CommunityController::class, 'commentStore']);
    Route::put('comment/{comment_id}', [CommunityController::class, 'commentStore']);
    Route::delete('comment/{comment_id}', [CommunityController::class, 'commentDelete']);
    Route::get('{post_id}/comment/{comment_id}/replies', [CommunityController::class, 'commentReplyList']);
    Route::post('upload', [CommunityController::class, 'imageUpload']);
    Route::get('', [CommunityController::class, 'categoryList']);
  });
});
