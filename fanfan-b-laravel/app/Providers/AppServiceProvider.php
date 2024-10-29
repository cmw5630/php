<?php

namespace App\Providers;

use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\FantasyCalculator\FantasyPolicyType;
use App\Enums\SimulationCalculator\SimulationCalculatorType;
use App\Libraries\Classes\Alarm;
use App\Libraries\Classes\FantasyCalculator;
use App\Libraries\Classes\MySqlConnection;
use App\Libraries\Classes\SimulationCalculator;
use App\Services\User\SocialUserResolver;
use Coderello\SocialGrant\Resolvers\SocialUserResolverInterface;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Builder;
use Arr;
use Str;

class AppServiceProvider extends ServiceProvider
{
  /**
   * All the container bindings that should be registered.
   *
   * @var array
   */
  public $bindings = [
    SocialUserResolverInterface::class => SocialUserResolver::class,
  ];

  /**
   * Register any application services.
   *
   * @return void
   */
  public function register()
  {
    // foreach(FantasyCalculatorType::getValues ... ) 사용하지 말 것. CalculatorType 별 $version이 다를 수 있음.
    $versionMap = [
      FantasyCalculatorType::FANTASY_POINT => 0,
      FantasyCalculatorType::FANTASY_RATING => 0,
      FantasyCalculatorType::FANTASY_POINT_C => 0,
      FantasyCalculatorType::FANTASY_RATING_C => 0,
      FantasyCalculatorType::FANTASY_CARD_GRADE => 0,
      FantasyCalculatorType::FANTASY_DRAFT => 0,
      FantasyCalculatorType::FANTASY_DRAFT_EXTRA => 0,
      FantasyCalculatorType::FANTASY_POWER_RANKING => 0,
      FantasyCalculatorType::FANTASY_INGAME_POINT => 0,
      FantasyCalculatorType::FANTASY_POINT_REWARD => 0,
      FantasyCalculatorType::FANTASY_MOMENTUM => 0,
      FantasyCalculatorType::FANTASY_PROJECTION => 0,
      FantasyCalculatorType::FANTASY_BURN => 0,
      FantasyCalculatorType::FANTASY_FREE_GAME => 0,
      // FantasyCalculatorType::FANTASY_PLATE_CARD_PRICE => 0, // Fantasy Calulator 사용안함.
    ];

    foreach (FantasyCalculatorType::getValues() as $calType) {
      $versionNum = $versionMap[$calType] ?? 0;
      $this->app->singleton($calType, function ($app, $version) use ($versionNum, $calType): FantasyCalculator {
        return new FantasyCalculator($calType, $version[$versionNum]);
      });
    }

    $this->app->singleton(SimulationCalculatorType::SIMULATION, function ($app) {
      return new SimulationCalculator();
    });

    $this->app->singleton('alarm', function ($app, $args): Alarm {
      return new Alarm($args['id'] ?? null);
    });

    if ($this->app->environment('local') || $this->app->environment('develop')) {
      $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
      $this->app->register(TelescopeServiceProvider::class);
    }

    // 각 서비스에 사용자 의존성 추가
    // $path = app_path('Services');
    // $directories = glob($path . '/*', GLOB_ONLYDIR);
    // $services = [];
    // foreach ($directories as $directory) {
    //   $files = glob($directory . '/*Service.php');
    //   foreach ($files as $file) {
    //     $services[Str::afterLast($directory, '/')][] = Str::before(Str::afterLast($file, '/'), '.php');
    //   }
    // }
    //
    // foreach ($services as $dir => $files) {
    //   foreach ($files as $file) {
    //     $interface = sprintf("App\\Services\\%s\\%sInterface", $dir, $file);
    //     $class = sprintf("App\\Services\\%s\\%s", $dir, $file);
    //
    //     // if (method_exists($class, '__construct')) {
    //       $this->app->bind($interface, function ($app) use ($class) {
    //         return new $class(Auth::user() ?? null);
    //       });
    //     // }
    //   }
    // }
  }

  /**
   * Bootstrap any application services.
   *
   * @return void
   */
  public function boot()
  {
    // DB Connection 의존성 변경
    Connection::resolverFor('mysql', function ($connection, $database, $prefix, $config) {
      return new MySqlConnection($connection, $database, $prefix, $config);
    });

    // 마이그레이션 디렉토리별 분리
    $mainPath = database_path('migrations');
    $directories = glob($mainPath . '/*', GLOB_ONLYDIR);
    // $data = glob($mainPath . '/SPORTSDATA/*', GLOB_ONLYDIR);
    // $paths = array_merge([$mainPath], $directories, $data);
    $paths = array_merge([$mainPath], $directories);

    $this->loadMigrationsFrom($paths);

    // 엘로퀀트 whereLike함수 임의 생성
    Builder::macro('whereLike', function ($attributes, string $searchTerm, $direction = 'both') {
      $this->where(function (Builder $query) use ($attributes, $searchTerm, $direction) {
        $conditionValue = match (Str::lower($direction)) {
          'right' => $searchTerm . '%',
          default => '%' . $searchTerm . '%',
        };
        foreach (Arr::wrap($attributes) as $attribute) {
          $query->orWhere($attribute, 'LIKE', $conditionValue);
        }
      });

      return $this;
    });

    Collection::macro('paginate', function ($perPage, $total = null, $page = null, $pageName = 'page') {
      $page = $page ?: LengthAwarePaginator::resolveCurrentPage($pageName);
      return new LengthAwarePaginator(
        $this->forPage($page, $perPage)->values(),
        $total ?: $this->count(),
        $perPage,
        $page,
        [
          'path' => LengthAwarePaginator::resolveCurrentPath(),
          'pageName' => $pageName,
        ]
      );
    });

    Request::macro('getClientIp', function () {
      return Str::before($this->header('X_FORWARDED_FOR'), ',') ?? $this->ip();
    });
  }
}
