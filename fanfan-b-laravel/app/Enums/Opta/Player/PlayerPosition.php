<?php

namespace App\Enums\Opta\Player;

use BenSampo\Enum\Enum;

final class PlayerPosition extends Enum
{
  const GOALKEEPER = 'Goalkeeper';
  const DEFENDER = 'Defender';
  const MIDFIELDER = 'Midfielder';
  const ATTACKER = 'Attacker';
  const UNKNOWN = 'Unknown';

  public static function getAllPositions()
  {
    $result = self::getValues();
    unset($result[array_search(self::UNKNOWN, $result)]);
    return array_values($result);
  }
}
