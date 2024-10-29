<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PurchaseOrderType extends Enum
{
  const DIRECT = 'direct';
  const CART = 'cart';
}
