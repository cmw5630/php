<?php

namespace App\Libraries\Classes\simulation;

use App\Enums\Simulation\SimulationEventType;
use App\Enums\SimulationCalculator\SimulationCalculatorType;
use App\Libraries\Classes\SimulationCalculator;

class PassEventHandler extends SimulationEventHandler
{

  public function __construct() {}

  public function handle(array &$_eventParamSet)
  {
    if ($_eventParamSet['event_type'] === SimulationEventType::PASS) {
      // $currentFormationPlace = $this->setCurrentFormationPlace($_eventParamSet);

      // pass_comm 처리
      $this->handleCommentary($_eventParamSet);
    } else {
      parent::handle($_eventParamSet);
    }
  }

  private function handleCommentary(array &$_eventParamSet)
  {
    /** 
     *  @var SimulationCalculator $simulationCalculator
     */
    $simulationCalculator = app(SimulationCalculatorType::SIMULATION);
    if ($_eventParamSet['comment_type'] === 'pass_comm') {
      $activeLineups = $_eventParamSet['active_lineups'];
      $attackDirection = $_eventParamSet['attack_direction'];

      $commentaryParamRef['comment'] = [
        'attack_team' => $activeLineups[$attackDirection]['club_code_name'],
      ];
      $commentaryParamRef['ref_infos'] = [
        'attack_team' => $attackDirection,
      ];

      $_eventParamSet['step_babo'][$_eventParamSet['i']]['commentary_template_id'] = $simulationCalculator->getRandomCommentaryId($_eventParamSet['comment_type']);
      $_eventParamSet['step_babo'][$_eventParamSet['i']]['ref_params'] = json_encode($commentaryParamRef);
    }
  }
}
