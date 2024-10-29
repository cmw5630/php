<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class RedeemStatus extends Enum
{
  const ACTIVE = 'active';
  const INACTIVE = 'inactive';
  const EXPIRED = 'expired';
}
