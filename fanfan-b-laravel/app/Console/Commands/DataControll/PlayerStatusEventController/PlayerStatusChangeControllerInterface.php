<?php

namespace App\Console\Commands\DataControll\PlayerStatusEventController;

use App\Console\Commands\DataControll\PlayerStatusEventHandlers\PlayerStatusHandlerAbstract;

interface PlayerStatusChangeControllerInterface
{
  public function registerListener(PlayerStatusHandlerAbstract $_newListener);
  public function removeListener(PlayerStatusHandlerAbstract $_listener);
  public function notifyFilter(array $_statusLogRow);
}
