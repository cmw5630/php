<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class CommunityStatus extends Enum
{
  const NORMAL = 'normal';
  const HIDE = 'hide';
  const DELETE = 'delete';
}
