<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PlateCardActionType extends Enum
{
  const SALE = 'sale';
  const STATS = 'stats';
  const PLATE_ORDER = 'plate_order';
  const UPGRADE = 'upgrade';
  const LINEUP = 'lineup';
}
