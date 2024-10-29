<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AuctionStatus extends Enum
{
  const BIDDING = 'bidding';
  const EXPIRED = 'expired';
  const SOLD = 'sold';
  const CANCELED = 'canceled';
  const DISABLED = 'disabled';  // 같은 카드의 재등록 건이 존재한다면, 기존 상태를 Disabled
}
