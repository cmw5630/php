<?php

namespace App\Libraries\Classes\simulation;

use App\Enums\Simulation\SimulationCommentType;
use App\Enums\Simulation\SimulationEventType;
use App\Enums\SimulationCalculator\SimulationCalculatorType;
use App\Libraries\Classes\SimulationCalculator;

class ShotEventHandler extends SimulationEventHandler
{
  public function __construct() {}

  public function handle(array &$_eventParamSet)
  {
    if ($_eventParamSet['event_type'] === SimulationEventType::SHOT) {
      $currentFormationPlace = $_eventParamSet['step_babo'][$_eventParamSet['i']]['formation_place']; // 슈팅선수
      if (
        $_eventParamSet['comment_type'] === SimulationCommentType::SHOT ||
        $_eventParamSet['comment_type'] === SimulationCommentType::SHOT_CORNERKICK ||
        $_eventParamSet['comment_type'] === SimulationCommentType::SHOT_CROSS
      ) {
        $this->handleCommentary($_eventParamSet, $currentFormationPlace);
      }
      $this->handleHighlight($_eventParamSet);
    } else {
      parent::handle($_eventParamSet);
    }
  }

  private function handleCommentary(array &$_eventParamSet, $_currentFormationPlace)
  {
    /** 
     *  @var SimulationCalculator $simulationCalculator
     */
    $simulationCalculator = app(SimulationCalculatorType::SIMULATION);
    $attackDirection = $_eventParamSet['attack_direction'];
    // $oppositeDirection = $this->simulationCalculator->getOppositeDirection($_eventParamSet['attack_direction']);
    $beforeFormationPlace = $_eventParamSet['step_babo'][$_eventParamSet['i'] - 1]['formation_place'];
    $activeLineups = $_eventParamSet['active_lineups'];

    $commentaryParamRef['comment'] = [
      'assist' => $activeLineups[$attackDirection]['players'][$beforeFormationPlace]['player_name'],
      'shot' => $activeLineups[$attackDirection]['players'][$_currentFormationPlace]['player_name'],
    ];
    $commentaryParamRef['ref_infos'] = [
      'shot_team' => $attackDirection,
      'assist' => $simulationCalculator->convertFormationPlaceToUserPlateCardId($activeLineups, $attackDirection, $beforeFormationPlace),
      'shot' => $simulationCalculator->convertFormationPlaceToUserPlateCardId($activeLineups, $attackDirection, $_currentFormationPlace),
    ];

    $_eventParamSet['step_babo'][$_eventParamSet['i']]['commentary_template_id'] = $simulationCalculator->getRandomCommentaryId($_eventParamSet['comment_type']);
    $_eventParamSet['step_babo'][$_eventParamSet['i']]['ref_params'] = json_encode($commentaryParamRef);
  }
}
