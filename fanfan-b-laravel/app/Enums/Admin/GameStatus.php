<?php

namespace App\Enums\Admin;

use BenSampo\Enum\Enum;

final class GameStatus extends Enum
{
  const FIXTURE = 'Fixture';
  const PLAYING = 'Playing';
  const CANCELLED = 'Cancelled';
  const PLAYED = 'Played';
}
