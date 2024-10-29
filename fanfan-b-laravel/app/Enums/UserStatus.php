<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class UserStatus extends Enum
{
  const NORMAL = 'normal';
  const OUT = 'out';
  const DORMANT = 'dormant';
}
