<?php

namespace App\Enums\Opta\TeamStanding;

use BenSampo\Enum\Enum;

final class TeamStandingType extends Enum
{
  const ACTIVE = 'active';
  const DEFUNCT = 'defunct';
  const TOTAL = 'total';
  const HOME = 'home';
  const AWAY = 'away';
  const FORM_TOTAL = 'form-total';
  const FORM_AWAY = 'form-away';
  const HALF_TIME_TOTAL = 'half-time-total';
  const HALF_TIME_HOME = 'half-time-home';
  const HALF_TIME_AWAY = 'half-time-away';
  const ATTENDANCE = 'attendance';
  const OVER_UNDER = 'over-under';
  const RELEGATION = 'relegation';
  const CHAMPIONSHIP = 'championship';
}
