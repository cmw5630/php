<?php

namespace App\Events;

use App\Enums\System\SocketChannelPrefix;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlarmPrivateSocketEvent implements ShouldBroadcast
{
  use Dispatchable, InteractsWithSockets, SerializesModels;

  public $data;
  public $user;

  public $afterCommit = true;
  public $channelPrefix = SocketChannelPrefix::USER_ALARM;

  /**
   * Create a new event instance.
   *
   * @return void
   */
  public function __construct($data, $user)
  {
    $this->data = $data;
    $this->user = $user;
  }

  /**
   * Get the channels the event should broadcast on.
   *
   * @return \Illuminate\Broadcasting\Channel|array
   */
  public function broadcastOn()
  {
    $channelName = env(
      'SOCKET_PREFIX',
      'FS_'
    ) . $this->channelPrefix . '.' . $this->user->id;
    // return new PrivateChannel($channelName);
    return new Channel($channelName); // private -> public 임시 변경
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
