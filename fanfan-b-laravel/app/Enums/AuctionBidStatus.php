<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AuctionBidStatus extends Enum
{
  const SUCCESS = 'success';
  const FAILED = 'failed';
  const PURCHASED = 'purchased';
}
