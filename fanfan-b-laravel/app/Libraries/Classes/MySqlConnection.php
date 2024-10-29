<?php

namespace App\Libraries\Classes;

use Illuminate\Database\MySqlConnection as BaseMySqlConnection;
use App\Libraries\Classes\Blueprint;

class MySqlConnection extends BaseMySqlConnection
{
  public function getSchemaBuilder()
  {
    /**
     * 확장된 Blueprint 삽입을 위한 Resolver
     *
     * @return Blueprint 
     */

    $builder = parent::getSchemaBuilder();
    $builder->blueprintResolver(function ($table, $callback) {
      return new Blueprint($table, $callback);
    });

    return $builder;
  }
}
