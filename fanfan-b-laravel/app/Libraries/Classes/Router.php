<?php

namespace App\Libraries\Classes;

use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Router as OriginalRouter;

class Router extends OriginalRouter
{
  public function customResource($route, $controllerPath, $suffix = '')
  {
    $routeName = $route ? $route . '.' : '';

    Route::get('{board_id}/'.$route, [$controllerPath, 'list' . $suffix])->name("{$routeName}list");
    Route::get($route . '/detail/{id}', [$controllerPath, 'detail' . $suffix])->name("{$routeName}show");
    Route::post($route, [$controllerPath, 'store' . $suffix])->name("{$routeName}store");
    Route::put($route . '/{id}', [$controllerPath, 'update' . $suffix])->name("{$routeName}update");
    Route::delete($route . '/{id}', [$controllerPath, 'delete' . $suffix])->name("{$routeName}delete");
  }
}
