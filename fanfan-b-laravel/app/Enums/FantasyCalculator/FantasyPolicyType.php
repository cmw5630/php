<?php

namespace App\Enums\FantasyCalculator;

use BenSampo\Enum\Enum;

final class FantasyPolicyType extends Enum
{
  const QUANTILE = 'quantile'; // 계산방식: 분위수에 대해 -> 절대값.
  const QUANTILE_MIN_VALUE = 'quantileMinValue'; // 계산방식: (stat의 최소 기준값 적용) 분위수에 대해 valueReference의에 따른 -> 절대값 적용
  const QUANTILE_QUANTILE_CONDITIONS = 'quantileQuantileConditions'; // 계산방식: stat의 분위수에 대해 combination의 quantile weight를 구하여 stat에 곱한다.
}
