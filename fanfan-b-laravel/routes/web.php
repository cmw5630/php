<?php

use App\Http\Controllers\API\v1\DevController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//   return ReturnData::setError(['NOT_FOUND_API', 'API를 찾을수 없습니다'])->send(Response::HTTP_NOT_FOUND);
// });

Route::get('card-preview', [DevController::class, 'cardPreview']);

Route::get('/email/verify', function () {
  return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
  $request->fulfill();
})->middleware(['auth', 'signed'])->name('verification.verify');
