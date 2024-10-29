<?php

namespace App\Console\Commands\DataControll\PlayerStatusEventHandlers;

use App\Enums\Opta\Squad\PlayerChangeStatus;

class UnknownHandler extends PlayerStatusHandlerAbstract
{
  public $changedType = PlayerChangeStatus::UNKNOWN;
  // public $obseverName = "UNKNOWN_LISTENER";

  public function __construct()
  {
    parent::__construct();
  }

  public function update(array $_statusLogRow)
  {
    logger($_statusLogRow);
    logger($this->changedType);
    // 활성화된 선수가 -> 선수 상태를 알 수 없음(UNKNOWN)으로 변화됨?(로그 주기적으로 확인 후 적절히(수동)처리)
    if ($this->deleteTargetPlateCard($_statusLogRow)) {
      logger('CHOICE MODE');
    }
  }
}
