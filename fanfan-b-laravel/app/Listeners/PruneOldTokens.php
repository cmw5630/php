<?php

namespace App\Listeners;

use Laravel\Passport\Events\RefreshTokenCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Laravel\Passport\Passport;

class PruneOldTokens
{
  /**
   * Create the event listener.
   *
   * @return void
   */
  public function __construct()
  {
    //
  }

  /**
   * Handle the event.
   *
   * @param RefreshTokenCreated $event
   * @return void
   */
  public function handle(RefreshTokenCreated $event)
  {
    Passport::refreshToken()->where([
      ['id', '<>', $event->refreshTokenId],
      ['access_token_id', '<>', $event->accessTokenId]
    ])
      ->update(['revoked' => true]);
  }
}
