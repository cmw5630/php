<?php

namespace App\Events;

// use BeyondCode\LaravelWebSockets\WebSockets\Channels\PrivateChannel;

use App\Enums\System\SocketChannelPrefix;
use App\Enums\System\SocketChannelType;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SimulationSocketEvent implements ShouldBroadcast
{
  use Dispatchable, InteractsWithSockets, SerializesModels;

  /**
   * The name of the queue connection to use when broadcasting the event.
   *
   * @var string
   */
  // public $connection = 'redis';

  /**
   * The name of the queue on which to place the broadcasting job.
   *
   * @var string
   */
  // public $queue = 'high';

  // public $afterCommit = true;

  public $data;

  public $channelPrefix = SocketChannelPrefix::SIMULATION;

  public $publishType;

  /**
   * Create a new event instance.
   *
   * @return void
   */
  public function __construct($data)
  {
    $this->data = $data;
    $this->publishType = $data ? $data['type'] : null;
  }

  public function broadcastQueue()
  {
    return $this->data['target_queue'] ?? 'high';
  }

  /**
   * Determine if this event should broadcast.
   *
   * @return bool
   */
  // public function broadcastWhen()
  // {
  //   return $this->data !== null;
  // }

  /**
   * Get the data to broadcast.
   *
   * @return array
   */
  public function broadcastWith()
  {
    return $this->data;
  }

  public function broadcastAs()
  {
    logger('event name: ' . $this->channelPrefix . '.' . $this->publishType);
    return $this->channelPrefix . '.' . $this->publishType;
  }

  /**
   * Get the channels the event should broadcast on.
   *
   * @return Channel|array
   */
  public function broadcastOn()
  {
    logger('target_queue:' . $this->data['target_queue']);
    $channelName = env('SOCKET_PREFIX', 'FS_')
      . $this->channelPrefix . '.'
      . $this->publishType . '.';
    switch ($this->publishType) {
      case SocketChannelType::SEQUENCE:
        $channelName = $channelName . $this->data['schedule_id'];
        break;
      case SocketChannelType::STATUS:
        $channelName = $channelName . $this->data['schedule_id'];
        break;
    }
    logger('channel name: ' . $channelName);
    // return new PrivateChannel($channelName); // 
    return new Channel($channelName); // private -> public 임시변경
  }
}
