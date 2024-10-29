<?php

namespace App\Console\Commands\DataControll\PlayerStatusEventHandlers;

use App\Console\Commands\DataControll\PlateCardBase;
use App\Enums\Opta\Squad\PlayerChangeStatus;

abstract class PlayerStatusHandlerAbstract extends PlateCardBase
{

  public function __construct()
  {
    parent::__construct();
  }

  /**
   * @var PlayerChangeStatus
   */
  public $changedType;

  /**
   * @var string
   */
  public $obseverName;

  public function getChangedType()
  {
    return $this->changedType;
  }
  public function getListenerName()
  {
    return $this->changedType . '_LISTENER';
  }
  abstract public function update(array $_statusLogRow);

  public function registerSubject($_subject)
  {
  }
}
