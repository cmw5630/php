<?php

namespace App\Enums\System;

use BenSampo\Enum\Enum;

final class SocketChannelPrefix extends Enum
{
  const INGAME_LIVE = 'ingame_live';
  const PUBLIC = 'public';
  const USER_ALARM = 'user_alarm';
  const PUBLIC_ALARM = 'public_alarm';
  // simulaiton ->
  const SIMULATION = 'simulation';
}
