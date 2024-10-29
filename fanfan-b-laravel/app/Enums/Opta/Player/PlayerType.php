<?php

namespace App\Enums\Opta\Player;

use BenSampo\Enum\Enum;

final class PlayerType extends Enum
{
  const PLAYER = 'player';
  const REFEREE = 'referee';
  const COACH = 'coach';
  const STAFF = 'staff';
  const ASSISTANT_COACH = 'assistant coach';
}
