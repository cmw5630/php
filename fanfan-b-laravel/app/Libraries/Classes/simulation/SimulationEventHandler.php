<?php

namespace App\Libraries\Classes\simulation;

use App\Enums\SimulationCalculator\SimulationCalculatorType;
use App\Models\simulation\SimulationCommentaryTemplate;

class SimulationEventHandler
{
  protected $commantaryTemplats = [];

  protected $nextHandler;

  public function __construct() {}


  public function setNextHandler(SimulationEventHandler $_eventHandler)
  {
    $this->nextHandler = $_eventHandler;
  }

  public function handle(array &$_eventParamSet)
  {

    /**
     * $_eventParamSet key info
     * =====================================> 
     * 'event_type' => $eventType,
     * 'active_lineups' => &$_activeLineups,
     * 'step_babo' => &$_stepBabo,
     * 'i' => $_i,
     * 'attack_direction' => $attackDirection,
     * 'coords_origin' => $coordsOrigin,
     * 'attack_formation_used' => ${'_' . $attackDirection . 'FormationUsed'},
     * 'before_formation_place' => $beforeFormationPlace,
     * 'opposite_formation_used' => ${'_' . $oppositeDirection . 'FormationUsed'},
     * 'comment_type' => $commentType,
     * 'is_last_step' => $_isLastStep,
     */
    if ($this->nextHandler) {
      $this->nextHandler->handle($_eventParamSet);
    } else {
      // logger('simulation event handler do nothing!' . ' for ' . $_eventParamSet['event_type']);
    }
  }

  protected function handleHighlight(array &$_eventParamSet)
  {
    if ($_eventParamSet['step_babo'][$_eventParamSet['i']]['is_highlight']) {
      /** 
       *  @var SimulationCalculator $simulationCalculator
       */
      $simulationCalculator = app(SimulationCalculatorType::SIMULATION);
      $activeLineups = $_eventParamSet['active_lineups'];
      $kickerFormationPlace = $_eventParamSet['step_babo'][$_eventParamSet['i']]['formation_place'];
      $keeperFormationPlace = 1;
      $oppositDirection = $simulationCalculator->getOppositeDirection($_eventParamSet['attack_direction']);
      // keeper
      $keeperSet = $activeLineups[$oppositDirection]['players'][$keeperFormationPlace];
      $keeperUserPlateCardId = $keeperSet['user_plate_card_id'];
      $keeperOverall = $keeperSet['overall'];
      //kicker
      $kickerSet = $activeLineups[$_eventParamSet['attack_direction']]['players'][$kickerFormationPlace];
      $kickerUserPlateCardId = $kickerSet['user_plate_card_id'];
      $kickerOverall = $kickerSet['overall'];
      $_eventParamSet['step_babo'][$_eventParamSet['i']]['highlight_overall'] = json_encode([
        'keeper' => ['team_side' => $oppositDirection, 'user_plate_card_id' => $keeperUserPlateCardId, 'overall' => $keeperOverall],
        'kicker' => ['team_side' => $_eventParamSet['attack_direction'], 'user_plate_card_id' => $kickerUserPlateCardId, 'overall' => $kickerOverall],
      ]);
    }
  }

  protected function setCurrentFormationPlace(&$_eventParamSet)
  {
    /** 
     *  @var SimulationCalculator $simulationCalculator
     */
    $simulationCalculator = app(SimulationCalculatorType::SIMULATION);
    $currentFormationPlace = $simulationCalculator->getRandomFormationPlace(
      $_eventParamSet['active_lineups'],
      $_eventParamSet['attack_direction'],
      $_eventParamSet['coords_origin'],
      $_eventParamSet['attack_formation_used'],
      $_eventParamSet['event_type'],
      $_eventParamSet['before_formation_place'],
      false,
      $_eventParamSet['opposite_formation_used'],
    );

    return $_eventParamSet['step_babo'][$_eventParamSet['i']]['formation_place'] = $currentFormationPlace;
  }
}
