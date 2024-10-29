<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use NotificationChannels\Telegram\TelegramMessage;
use Illuminate\Notifications\Notification;
use Str;

class TelegramDebugNotification extends Notification
{
  use Queueable;


  protected $subject = '';
  protected $message = '';

  /**
   * Create a new notification instance.
   *
   * @return void
   */
  public function __construct($_subject, $_message)
  {
    // logger('message->>');
    // logger($_message);
    // logger('<--message');
    $this->subject = Str::replace('_', '-', $_subject);;
    if (gettype($_message) === 'array') {
      $_message = json_encode($_message, JSON_PRETTY_PRINT);
    }

    $this->message = Str::replace('_', '-', $_message);
    //
  }

  /**
   * Get the notification's delivery channels.
   *
   * @param  mixed  $notifiable
   * @return array
   */
  public function via($notifiable)
  {
    return ['telegram'];
  }

  public function toTelegram($notifiable)
  {
    return TelegramMessage::create()
      ->to(env('TELEGRAM_DEVELOP_BOT_CHAT_ID'))
      ->content(sprintf("%s\n%s", $this->subject, $this->message));
  }

  /**
   * Get the array representation of the notification.
   *
   * @param  mixed  $notifiable
   * @return array
   */
  public function toArray($notifiable)
  {
    return [
      //
    ];
  }
}
