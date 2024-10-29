<?php

namespace App\Listeners;

use Laravel\Passport\Events\AccessTokenCreated;
use Laravel\Passport\Passport;

class RevokeOldTokens
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
   * @param AccessTokenCreated $event
   * @return void
   */
  public function handle(AccessTokenCreated $event)
  {
    Passport::token()->where([
      ['client_id', $event->clientId],
      ['user_id', $event->userId],
      ['id', '<>', $event->tokenId]
    ])
      ->update(['revoked' => true]);
  }
}
