<?php

namespace App\Libraries\Classes;

use Illuminate\Database\Schema\Blueprint as BaseBlueprint;

class Blueprint extends BaseBlueprint
{
  /**
   * Migration시에 SoftDelete 컬럼 자동화를 위한 Blueprint 확장 클래스
   *
   * @return void 
   */

  public function timestamps($precision = 0, $isSoftDelete = true)
  {
    $this->timestamp('created_at', $precision)->useCurrent();

    $this->timestamp('updated_at', $precision)->nullable()->useCurrentOnUpdate();

    if ($isSoftDelete === true) {
      $this->timestamp('deleted_at', $precision)->nullable();
    }
  }
}
