<?php

namespace App\Console\Commands\DataControll\PlayerStatusEventController;

use App\Console\Commands\DataControll\PlayerStatusEventHandlers\PlayerStatusHandlerAbstract;

class PlayerStatusChangeEventController implements PlayerStatusChangeControllerInterface
{
  /**
   * @var PlayerStatusHandlerAbstract[]
   */
  private $registeredListeners = [];

  public function registerListener(PlayerStatusHandlerAbstract $_newListener)
  {
    if (isset($this->registeredListeners[$_newListener->getListenerName()])) {
      return;
    }
    logger($_newListener->getListenerName() . ' 등록');
    $this->registeredListeners[$_newListener->getListenerName()] = $_newListener;
  }
  public function removeListener(PlayerStatusHandlerAbstract $_targetListener)
  {
    foreach ($this->registeredListeners as $idx => $name) {
      if ($name === $_targetListener->getListenerName()) {
        unset($this->registeredListeners[$idx]);
        logger($_targetListener->getListenerName() . '제거 완료');
        break;
      }
    }
  }

  public function updateChangedStatus(array $_statusLogRow)
  {
    $this->notifyFilter($_statusLogRow);
  }

  public function isTargetListener(array $_statusLogRow, PlayerStatusHandlerAbstract  $_listener)
  {
    return $_statusLogRow['changed_type'] === $_listener->getChangedType();
  }

  public function notifyFilter(array $_statusLogRow)
  {
    foreach ($this->registeredListeners as $key => $listener) {
      if ($this->isTargetListener($_statusLogRow, $listener)) {
        $listener->update($_statusLogRow);
      }
    }
  }
}
