<?php

namespace App\Enums\Opta\TeamStanding;

use BenSampo\Enum\Enum;

final class TeamStandingRankStatus extends Enum
{
  const PROMOTION = 'Promotion';
  const PROMOTION_PLAY_OFF = 'Promotion Play-off';
  const RELEGATION = 'Relegation';
  const RELEGATION_ROUND = 'Relegation Round';
  const RELEGATION_PLAY_OFF = 'Relegation Play-off';
  const UEFA_CHAMPIONS_LEAGUE = 'UEFA Champions League';
  const UEFA_EUROPA_LEAGUE = 'UEFA Europa League';
  const UEFA_CONFERENCE_LEAGUE_QUALIFIERS = 'UEFA Conference League Qualifiers';
}
