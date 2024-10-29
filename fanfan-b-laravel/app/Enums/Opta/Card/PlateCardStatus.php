<?php

namespace App\Enums\Opta\Card;

use BenSampo\Enum\Enum;

// SquadChangeStatus 에서 상속받아 써야 하므로 final 에서 일반 클래스로 전환
class PlateCardStatus extends Enum
{
  const PLATE = 'plate';            // 기본 카드 상태
  const UPGRADING = 'upgrading';    // 강화중
  const COMPLETE = 'complete';      // 강화 완료
  // const ALLOCATION = 'allocation';  // 배당용
  // const NOT_USED = 'not_used';      // 더이상 사용되지 않음.
}
