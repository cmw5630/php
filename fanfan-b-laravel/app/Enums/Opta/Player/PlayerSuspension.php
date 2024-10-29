<?php

namespace App\Enums\Opta\Player;

use BenSampo\Enum\Enum;

final class PlayerSuspension extends Enum
{
  const DOPING = 'Doping violation';
  const YELLOW_CARD = 'Yellow card';
  const RED_CARD = 'Red card';
  const ETC = 'ETC';
}
