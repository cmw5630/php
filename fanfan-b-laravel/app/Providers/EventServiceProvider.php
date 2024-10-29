<?php

namespace App\Providers;

use App\Listeners\PruneOldTokens;
use App\Listeners\RevokeOldTokens;
use App\Models\data\Commentary;
use App\Models\data\Schedule;
use App\Observers\CommentaryObserver;
use App\Observers\ScheduleObserver;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Events\RefreshTokenCreated;
use SocialiteProviders\Kakao\KakaoExtendSocialite;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Naver\NaverExtendSocialite;

class EventServiceProvider extends ServiceProvider
{
  /**
   * The event to listener mappings for the application.
   *
   * @var array<class-string, array<int, class-string>>
   */
  protected $listen = [
    Registered::class => [
      SendEmailVerificationNotification::class,
    ],
    AccessTokenCreated::class => [
      // 중복 로그인 방지할 때 다시 추가
      // RevokeOldTokens::class,
    ],
    RefreshTokenCreated::class => [
      PruneOldTokens::class,
    ],
    SocialiteWasCalled::class => [
      NaverExtendSocialite::class,
      KakaoExtendSocialite::class,
    ],
  ];

  protected $observers = [
    Schedule::class => [ScheduleObserver::class],
    Commentary::class => [CommentaryObserver::class],
  ];

  /**
   * Register any events for your application.
   *
   * @return void
   */
  public function boot()
  {
    //
  }

  /**
   * Determine if events and listeners should be automatically discovered.
   *
   * @return bool
   */
  public function shouldDiscoverEvents()
  {
    return false;
  }
}
