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

class DeprecatedIngameCommentarySocketEvent implements ShouldBroadcast
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
  public $queue = 'high';

  public $afterCommit = true;

  public $data;

  public $channelPrefix = SocketChannelPrefix::INGAME_LIVE;

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

  /**
   * Determine if this event should broadcast.
   *
   * @return bool
   */
  public function broadcastWhen()
  {
    return $this->data !== null;
  }

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
    // PrivateChannel로 변경 필요함. (routes/channels.php에 설정 해놓음)
    // return new Channel('FS_LIVE_STATS' . '_' . $this->data['schedule_id']);
    $channelName = env('SOCKET_PREFIX', 'FS_')
      . $this->channelPrefix . '.'
      . $this->publishType . '.';
    switch ($this->publishType) {
      case SocketChannelType::FORMATION:
        $channelName = $channelName . $this->data['schedule_id'];
        break;

      case SocketChannelType::USER_RANK:
        $channelName = $channelName . $this->data['game_id'];
        break;

      case SocketChannelType::USER_LINEUP:
        $channelName = $channelName . $this->data['game_join_id'];
        break;

      case SocketChannelType::LINEUP_DETAIL:
        $channelName = $channelName . $this->data['player_id'];
        break;
    }
    logger('channel name: ' . $channelName);
    return new PrivateChannel($channelName);
  }
}
