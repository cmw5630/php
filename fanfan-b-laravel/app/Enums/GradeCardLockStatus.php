<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class GradeCardLockStatus extends Enum
{
  const MARKET = 'market';
  const INGAME = 'ingame';
  const SIMULATION = 'simulation';
  const INGAME_SIMULATION = 'ingame_simulation';
}
