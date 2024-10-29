<?php

namespace App\Enums\FantasyMeta;

use BenSampo\Enum\Enum;

final class FantasySyncGroupType extends Enum
{
  const ETC = 'etc';
  const ELASTIC = 'elastic';
  const DAILY = 'daily';
  const ALL = 'all';
  const CONDITIONALLY = 'conditionally';
}
