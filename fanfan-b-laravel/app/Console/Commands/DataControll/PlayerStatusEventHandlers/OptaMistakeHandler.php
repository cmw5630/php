<?php

namespace App\Console\Commands\DataControll\PlayerStatusEventHandlers;

use App\Enums\Opta\Squad\PlayerChangeStatus;

class OptaMistakeHandler extends PlayerStatusHandlerAbstract
{
  public $changedType = PlayerChangeStatus::OPTAMISTAKE;

  public function __construct()
  {
    parent::__construct();
  }

  public function update(array $_statusLogRow)
  {
    /**
     * DeactivatedHandler와 동일한 구조
     */
    logger($_statusLogRow);
    logger($this->changedType);
    if ($this->deleteTargetPlateCard($_statusLogRow)) {
      logger('CHOICE MODE');
    }
  }
}
