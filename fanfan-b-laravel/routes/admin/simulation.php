<?php

use App\Http\Controllers\ADMIN\v1\SimulationController;
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
  Route::group(['as' => 'simulation.', 'prefix' => 'simulation', 'middleware' => 'adminAuth:admin'],
    function () {
      Route::post('load_sequence', [SimulationController::class, 'uploadRefSequence']);
    });
});
