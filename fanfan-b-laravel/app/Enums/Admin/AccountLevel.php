<?php

namespace App\Enums\Admin;

use BenSampo\Enum\Enum;

final class AccountLevel extends Enum
{
  const LEVEL_SUPER = 1;
  const LEVEL_1 = 2;
  const LEVEL_2 = 3;
  const LEVEL_3 = 4;

  const ACCOUNT_LEVELS = [
    self::LEVEL_SUPER => '최고관리자',
    self::LEVEL_1 => '레벨1',
    self::LEVEL_2 => '레벨2',
    self::LEVEL_3 => '레벨3',
  ];
}