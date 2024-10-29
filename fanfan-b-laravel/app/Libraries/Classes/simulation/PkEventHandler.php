<?php

namespace App\Libraries\Classes\simulation;

use App\Enums\Simulation\SimulationEventType;
use App\Enums\SimulationCalculator\SimulationCalculatorType;
use App\Libraries\Classes\SimulationCalculator;

class PkEventHandler extends SimulationEventHandler
{
  public function __construct() {}

  public function handle(array &$_eventParamSet)
  {
    if ($_eventParamSet['event_type'] === SimulationEventType::PK) {
      /** 
       *  @var SimulationCalculator $simulationCalculator
       */
      $simulationCalculator = app(SimulationCalculatorType::SIMULATION);
      // $currentFormationPlace = $this->setCurrentFormationPlace($_eventParamSet); // PK선수 지정 임시처리
      // pk는 comment 없음
    } else {
      parent::handle($_eventParamSet);
    }
  }
}
