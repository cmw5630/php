<?php

namespace App\Enums\Opta\Player;

use BenSampo\Enum\Enum;

// SquadChangeStatus 에서 상속받아 써야 하므로 final 에서 일반 클래스로 전환
class PlayerStatus extends Enum
{
  const ACTIVE = 'active';
  const RETIRED = 'retired';
  const DIED = 'died';
}
