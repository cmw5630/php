<?php

namespace App\Events;

use App\Enums\System\SocketChannelPrefix;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlarmPublicSocketEvent implements ShouldBroadcast
{
  use Dispatchable, InteractsWithSockets, SerializesModels;

  public $data;

  public $afterCommit = true;
  public $channelPrefix = SocketChannelPrefix::PUBLIC_ALARM;

  /**
   * Create a new event instance.
   *
   * @return void
   */
  public function __construct($data)
  {
    $this->data = $data;
  }

  /**
   * Get the channels the event should broadcast on.
   *
   * @return \Illuminate\Broadcasting\Channel|array
   */
  public function broadcastOn()
  {
    // $channelName = env('REDIS_PREFIX', 'FS_');
    $channelName = env(
      'SOCKET_PREFIX',
      'FS_'
    ) . $this->channelPrefix;
    logger('channel name: ' . $channelName);
    logger($this->data);
    return new Channel($channelName);
  }

  public function broadcastAs()
  {
    logger('event name: ' . $this->channelPrefix);
    return $this->channelPrefix;
  }


  public function broadcastQueue()
  {
    return 'high';
  }
}
