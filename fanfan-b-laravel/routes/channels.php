<?php

use App\Enums\System\SocketChannelPrefix;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

//ingame live
Broadcast::channel(
  env(
    'SOCKET_PREFIX',
    'FS_'
  ) . SocketChannelPrefix::INGAME_LIVE . '.' . '{liveType}' . '.' . '{dyanmicId}',
  function ($user, $lliveType, $dyanmicId) {
    logger('user_id:' . $user->id . '/' . $lliveType . '.' . $dyanmicId);

    return (bool) $user?->id;
  }
);

Broadcast::channel(
  env('SOCKET_PREFIX', 'FS_') . SocketChannelPrefix::PUBLIC . '.' . '{type}',
  function ($type) {
    return true;
  }
);

Broadcast::channel(
  env('SOCKET_PREFIX', 'FS_') . SocketChannelPrefix::USER_ALARM . '.' . '{userId}',
  function ($user) {
    return true;
  }
);

Broadcast::channel(
  env('SOCKET_PREFIX', 'FS_') . SocketChannelPrefix::SIMULATION . '.' . '{channelType}' . '.' . '{scheduleId}',
  function ($user) {
    return true;
  }
);

// Broadcast::channel('App.Models.user.User.{id}', function ($user, $id) {
//   return (int) $user->id === (int) $id;
// });
