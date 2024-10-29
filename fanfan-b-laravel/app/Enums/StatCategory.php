<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class StatCategory extends Enum
{
  const SUMMARY = 'summary';
  const ATTACKING = 'attacking';
  const PASSING = 'passing';
  const DEFENSIVE = 'defensive';
  const DUELS = 'duels';
  const GOALKEEPING = 'goalkeeping';
}
