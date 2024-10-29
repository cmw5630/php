<?php

namespace App\Enums\FantasyCalculator;

use BenSampo\Enum\Enum;

final class FantasyPointCategoryType extends Enum
{
  const GENERAL = 'general';
  const OFFENSIVE = 'offensive';
  const PASSING = 'passing';
  const DEFENSIVE = 'defensive';
  const DUEL = 'duel';
  const GOALKEEPING = 'goalkeeping';

  public static function getStatValues(): array
  {
    // GENERAL 제거
    $consts = static::getConstants();
    unset($consts['GENERAL']);

    return array_values($consts);
  }
}
