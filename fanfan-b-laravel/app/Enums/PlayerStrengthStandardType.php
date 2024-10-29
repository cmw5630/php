<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PlayerStrengthStandardType extends Enum
{
  const AVERAGE = 'average';  // 경기당 평균
  const PER = 'per';  // per90
  const SUM = 'sum';  // 시즌 합계
}
