<?php

namespace App\Enums\Opta\League;

use BenSampo\Enum\Enum;

final class LeagueStatusType extends Enum
{
  const HIDE = 'hide';    // 노출시키지 않음
  const SHOW = 'show';    // 정상 상태
  const DISABLE = 'disable';  // 노출은 되지만 비활성화
}
