<?php

namespace App\Enums\Opta\Card;

use BenSampo\Enum\Enum;

// 강화시도 후, 카드 상태
class DraftCardStatus extends Enum
{
  const COMPLETE = 'complete';      // 강화 완료
  const UPGRADING = 'upgrading';    // 강화중
  const CANCEL = 'cancel';   // 경기 취소

}
