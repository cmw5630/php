<?php

namespace App\Enums\Opta\Player;

use BenSampo\Enum\Enum;

final class PlayerDailyPosition extends Enum
{
  const STRIKER = 'Striker';
  const ATTACKING_MIDFIELDER = 'Attacking Midfielder';
  const MIDFIELDER = 'Midfielder';
  const DEFENSIVE_MIDFIELDER = 'Defensive Midfielder';
  const DEFENDER = 'Defender';
  const WING_BACK = 'Wing Back';
  const GOALKEEPER = 'Goalkeeper';
  const SUBSTITUTE = 'Substitute';
}
