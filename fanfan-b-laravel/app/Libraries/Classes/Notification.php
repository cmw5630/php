<?php

namespace App\Libraries\Classes;

use Illuminate\Notifications\AnonymousNotifiable;
use Notification as BaseNotification;

class Notification extends BaseNotification
{
  /**
   * Begin sending a notification to an anonymous notifiable.
   *
   * @param  string  $channel
   * @param  mixed  $route
   * @return \Illuminate\Notifications\AnonymousNotifiable
   */
  public static function route($channel = 'telegram', $route = '')
  {
    if (empty($route)) {
      $route = config('services.telegram-bot-api')['chat_id'];
    }

    return (new AnonymousNotifiable)->route($channel, $route);
  }
}