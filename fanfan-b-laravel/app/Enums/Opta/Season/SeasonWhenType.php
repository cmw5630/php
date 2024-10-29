<?php

namespace App\Enums\Opta\Season;

use BenSampo\Enum\Enum;

final class SeasonWhenType extends Enum
{
  const BEFORE = 'before';
  const CURRENT = 'current';
  const FUTURE = 'future';
}
