<?php

namespace App\Enums\Simulation;

use BenSampo\Enum\Enum;

final class ScheduleWinnerStatus extends Enum
{
  const HOME = 'home';
  const AWAY = 'away';
  const DRAW = 'draw';
}
