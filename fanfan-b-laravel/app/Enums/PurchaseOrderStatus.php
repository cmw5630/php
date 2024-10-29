<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PurchaseOrderStatus extends Enum
{
  const COMPLETE = 'complete';
  const CANCEL = 'cancel';
  const REFUND = 'refund';
}
