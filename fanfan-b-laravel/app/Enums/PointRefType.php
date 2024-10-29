<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PointRefType extends Enum
{
  const ORDER = 'order';
  const UPGRADE = 'upgrade';
  const MARKET = 'market';
  const REWARD = 'reward';
  const BURN = 'burn';
  const ETC = 'etc';
}
