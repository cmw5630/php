<?php

namespace App\Libraries\Classes\simulation;

use App\Enums\SimulationCalculator\SimulationCalculatorType;
use App\Libraries\Classes\SimulationCalculator;
use Str;
use Throwable;

class FoulEventHandler extends SimulationEventHandler
{
  public function __construct() {}

  public function handle(array &$_eventParamSet)
  {
    if (Str::startsWith($_eventParamSet['event_type'], 'foul')) {
      /** 
       *  @var SimulationCalculator $simulationCalculator
       */
      $simulationCalculator = app(SimulationCalculatorType::SIMULATION);
      // $this->setCurrentFormationPlace($_eventParamSet);
      $attackDirection = $_eventParamSet['attack_direction'];
      $eventType = $_eventParamSet['event_type'];
      $activeLineups = &$_eventParamSet['active_lineups'];

      $foulFormationPlace = array_pop($activeLineups[$simulationCalculator->getOppositeDirection($attackDirection)]['event_memory'][$eventType]);

      $this->handleCommentary($_eventParamSet, $foulFormationPlace);
      $this->handleHighlight($_eventParamSet);
      $this->handleYRCards($_eventParamSet, $foulFormationPlace);
    } else {
      parent::handle($_eventParamSet);
    }
  }

  private function handleYRCards(array &$_eventParamSet, $_foulFormationPlace)
  {
    /** 
     *  @var SimulationCalculator $simulationCalculator
     */
    $simulationCalculator = app(SimulationCalculatorType::SIMULATION);
    $_stepBabo = $_eventParamSet['step_babo'];
    $_i = $_eventParamSet['i'];
    $_activeLineups = &$_eventParamSet['active_lineups'];
    $attackDirection = $_eventParamSet['attack_direction'];
    $oppositeDirection = $simulationCalculator->getOppositeDirection($attackDirection);

    if (Str::startsWith($_stepBabo[$_i]['event'], 'foul_y')) { // yellow card
      $_activeLineups[$oppositeDirection]['players'][$_foulFormationPlace]['yellow_card_count']++;
      // $_activeLineups[$oppositeDirection]['y_card_players'][] = $_foulFormationPlace;
    } else if (Str::startsWith($_stepBabo[$_i]['event'], 'foul_r')) { // red card
      $_activeLineups[$oppositeDirection]['players'][$_foulFormationPlace]['red_card_count']++;
      $_activeLineups[$oppositeDirection]['players'][$_foulFormationPlace]['is_changed'] = true;
      // $_activeLineups[$oppositeDirection]['r_card_players'][] = $_foulFormationPlace;
      //퇴장 로직
      $_activeLineups[$oppositeDirection]['substitutions'][] = $_activeLineups[$oppositeDirection]['players'][$_foulFormationPlace];
      unset($_activeLineups[$oppositeDirection]['players'][$_foulFormationPlace]);
    }
  }

  private function handleCommentary(array &$_eventParamSet, $_foulFormationPlace)
  {
    /** 
     *  @var SimulationCalculator $simulationCalculator
     */
    $simulationCalculator = app(SimulationCalculatorType::SIMULATION);
    $attackDirection = $_eventParamSet['attack_direction'];
    $oppositeDirection = $simulationCalculator->getOppositeDirection($_eventParamSet['attack_direction']);
    $currentFormationPlace = $_eventParamSet['step_babo'][$_eventParamSet['i']]['formation_place'];
    $activeLineups = $_eventParamSet['active_lineups'];

    if (Str::startsWith($_eventParamSet['comment_type'], 'foul')) {
      $_activeLineups = $_eventParamSet['active_lineups'];

      try {
        $commentaryParamRef['comment'] = [
          'foul' => $_activeLineups[$oppositeDirection]['players'][$_foulFormationPlace]['player_name'],
          'was_fouled' => $_activeLineups[$attackDirection]['players'][$currentFormationPlace]['player_name'],
          'was_fouled_team' => $_activeLineups[$attackDirection]['club_code_name'],
          'attack_team' => $_activeLineups[$attackDirection]['club_code_name'],
        ];
      } catch (Throwable $e) {
        logger($oppositeDirection);
        logger('foul formationplace:' . $_foulFormationPlace);
        logger($activeLineups);
        logger($e);
      }

      $commentaryParamRef['ref_infos'] = [
        'foul_team' => $oppositeDirection,
        'foul' => $simulationCalculator->convertFormationPlaceToUserPlateCardId($_activeLineups, $oppositeDirection, $_foulFormationPlace),
        'was_fouled' => $simulationCalculator->convertFormationPlaceToUserPlateCardId($_activeLineups, $attackDirection, $currentFormationPlace),
        'was_fouled_team' => $attackDirection,
        'attack_team' => $attackDirection,
      ];

      if ($_eventParamSet['comment_type'] === 'foul_pk' || $_eventParamSet['comment_type'] === 'foul_y_pk') {
        // format setting;
      } else if ($_eventParamSet['comment_type'] === 'foul_y_free') {
        // format setting;
      } else if ($_eventParamSet['comment_type'] === 'foul_r_free' || $_eventParamSet['comment_type'] === 'foul_r_pk') {
        $commentaryParamRef['comment']['red_card_team'] = $_activeLineups[$oppositeDirection]['club_code_name'];
        $commentaryParamRef['ref_infos']['red_card_team'] = $oppositeDirection;
      }

      $_eventParamSet['step_babo'][$_eventParamSet['i']]['commentary_template_id'] = $simulationCalculator->getRandomCommentaryId($_eventParamSet['comment_type']);
      $_eventParamSet['step_babo'][$_eventParamSet['i']]['ref_params'] = json_encode($commentaryParamRef);
    }
  }
}
