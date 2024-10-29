<?php

namespace App\Providers;

use App\Enums\ErrorDefine;
use File;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use ReturnData;
use Str;

class RouteServiceProvider extends ServiceProvider
{
  /**
   * The path to the "home" route for your application.
   *
   * Typically, users are redirected here after authentication.
   *
   * @var string
   */
  public const HOME = '/home';

  protected $namespace = 'App\\Http\\Controllers';

  /**
   * Define your route model bindings, pattern filters, and other route configuration.
   *
   * @return void
   */
  public function boot()
  {
    parent::boot();
    $this->configureRateLimiting();

    // $routeFiles = File::files(base_path('routes'));
    // foreach ($routeFiles as $file) {
    //   $prefix = Str::before(pathinfo($file->getFilename())['filename'], '_');
    //   Route::middleware($prefix)
    //     ->prefix($prefix)
    //     ->group($file->getPathname());
    // }
  }

  public function map()
  {
    $this->mapRoutes();
  }

  protected function mapRoutes()
  {
    foreach (scandir(base_path('routes')) as $list) {
      if (Str::startsWith($list, '.')) {
        continue;
      }
      $middleware = [$list];
      if ($list === 'api') {
        // access를 최우선으로
        $middleware = ['access', ...$middleware];
      }
      if (File::isDirectory(base_path('routes') . '/' . $list)) {
        $routeFiles = File::files(base_path('routes/' . $list));

        foreach ($routeFiles as $file) {
          Route::middleware($middleware)
            ->prefix($list)
            ->group($file->getPathname());
        }
      }
    }

    Route::middleware('web')
      ->namespace($this->namespace)
      ->group(base_path('routes/web.php'));
  }

  /**
   * Configure the rate limiters for the application.
   *
   * @return void
   */
  protected function configureRateLimiting()
  {
    RateLimiter::for('api', function (Request $request) {
      return Limit::perMinute(180)->by($request->user()?->id ?: $request->ip());
    });
    // RateLimiter::for('login', function (Request $request) {
    //   return Limit::perMinute(5)->by($request->input('email'))->response(function ($request, $limit) {
    //     $msg = __('auth.login.throttle', ['seconds' => $limit['Retry-After']]);
    //     return ReturnData::setError([ErrorDefine::TOO_MANY_REQUEST, $msg])->send(429);
    //   });
    // });
  }
}
