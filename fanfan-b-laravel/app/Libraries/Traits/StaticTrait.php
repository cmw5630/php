<?php

namespace App\Libraries\Traits;

use Str;

trait StaticTrait
{
  protected static function getUuid($model)
  {
    return Str::replace('-', '', Str::substr(Str::afterLast($model->getTable(), '.'), 0, 2) . Str::uuid());
  }
}