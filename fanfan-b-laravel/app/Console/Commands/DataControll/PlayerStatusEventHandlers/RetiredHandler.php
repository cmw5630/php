<?php

namespace App\Console\Commands\DataControll\PlayerStatusEventHandlers;

use App\Enums\Opta\Squad\PlayerChangeStatus;

class RetiredHandler extends PlayerStatusHandlerAbstract
{
  public $changedType = PlayerChangeStatus::RETIRED;
  // public $obseverName = "RETIRED_LISTENER";

  public function __construct()
  {
    parent::__construct();
  }

  public function update(array $_statusLogRow)
  {
    logger($_statusLogRow);
    logger($this->changedType);
    // plate_cards 에서 선수 delete 처리
    // user_plate_cards 에서 해당 선수 choice_mode 처리
    if ($this->deleteTargetPlateCard($_statusLogRow)) {
      logger('CHOICE MODE');
    }
  }
}
