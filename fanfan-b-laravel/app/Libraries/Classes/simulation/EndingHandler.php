<?php

namespace App\Libraries\Classes\simulation;

use App\Enums\Simulation\SimulationCommentType;
use App\Enums\Simulation\SimulationEndingType;
use App\Enums\Simulation\SimulationEventType;
use App\Enums\SimulationCalculator\SimulationCalculatorType;
use App\Libraries\Classes\SimulationCalculator;
use Throwable;

class EndingHandler
{
  public function __construct() {}

  private function isAvailableEnding($_ending): bool
  {
    return (in_array($_ending, [
      SimulationCommentType::PASS,
      SimulationCommentType::OFFSIDE,
      SimulationCommentType::SAVED,
      SimulationCommentType::OUT,
      SimulationCommentType::BLOCKED,
      SimulationCommentType::HITWOODWORK,
      SimulationCommentType::GOAL,
      SimulationCommentType::FIRST_HALF_START,
      SimulationCommentType::FIRST_HALF_END,
      SimulationCommentType::SECOND_HALF_START,
      SimulationCommentType::SECOND_HALF_END,
    ]));
  }

  private function refreshScore($_ending, &$_stepBabo, $_i, $_attackDirection, &$_homeGoal, &$_awayGoal)
  {
    if ($_ending === SimulationEndingType::GOAL) {
      $_stepBabo[$_i][$_attackDirection . '_' . 'goal']++;
      ${'_' . $_attackDirection . 'Goal'}++;
    }
  }




  public function handle($_ending, &$_stepBabo, $_i, &$_activeLineups, $_attackDirection, &$_homeGoal, &$_awayGoal)
  {
    if (!$this->isAvailableEnding($_ending)) return;
    $this->refreshScore($_ending, $_stepBabo, $_i, $_attackDirection, $_homeGoal, $_awayGoal);
    /** 
     *  @var SimulationCalculator $simulationCalculator
     */
    $simulationCalculator = app(SimulationCalculatorType::SIMULATION);

    $curIdx = $_i - 1;
    $beforeIdx = $_i - 2;
    if (isset($_stepBabo[$_i - 1]['event'])) {
      if ($_stepBabo[$_i - 1]['event'] === SimulationCommentType::SUBSTITUTE) {
        $curIdx = $_i - 2;
        $beforeIdx = $_i - 3;
      }
    }
    if (isset($_stepBabo[$curIdx]) && isset($_stepBabo[$beforeIdx])) {
      $currentFormationPlace = $_stepBabo[$curIdx]['formation_place'];
      $beforeFormationPlace = $_stepBabo[$beforeIdx]['formation_place'];
    }

    $oppositeDirection = $simulationCalculator->getOppositeDirection($_attackDirection);

    $commentaryParamRef = [];

    if ($_ending === SimulationCommentType::PASS) {
      $commentaryParamRef['comment'] = [
        'attack_team' => $_activeLineups[$_attackDirection]['club_code_name'],
        'opposite_team' => $_activeLineups[$oppositeDirection]['club_code_name'],
      ];
      $commentaryParamRef['ref_infos'] = [
        'attack_team' => $_attackDirection,
        'opposite_team' => $oppositeDirection,
      ];
    } else if ($_ending === SimulationCommentType::OFFSIDE) {
      $commentaryParamRef['comment'] = [
        'attack_team' => $_activeLineups[$_attackDirection]['club_code_name'],
        'sub_player' => $_activeLineups[$_attackDirection]['players'][$beforeFormationPlace]['player_name'],
        'player' => $_activeLineups[$_attackDirection]['players'][$currentFormationPlace]['player_name'],
      ];
      $commentaryParamRef['ref_infos'] = [
        'attack_team' => $_attackDirection,
        'sub_player' => $simulationCalculator->convertFormationPlaceToUserPlateCardId($_activeLineups, $_attackDirection, $beforeFormationPlace),
        'player' => $simulationCalculator->convertFormationPlaceToUserPlateCardId($_activeLineups, $_attackDirection, $currentFormationPlace),
      ];
    } else if (
      $_ending === SimulationCommentType::SAVED ||
      $_ending === SimulationCommentType::OUT ||
      $_ending === SimulationCommentType::HITWOODWORK ||
      $_ending === SimulationCommentType::GOAL
    ) {
      $isAssistExist = in_array(
        $_stepBabo[$beforeIdx]['event'],
        [
          SimulationEventType::ASSIST,
          SimulationEventType::CORNERKICK,
          SimulationEventType::CROSS,
        ]
      );
      $commentaryParamRef['comment'] = [
        'shot' => $_activeLineups[$_attackDirection]['players'][$currentFormationPlace]['player_name'],
        'assist' => $isAssistExist ? $_activeLineups[$_attackDirection]['players'][$beforeFormationPlace]['player_name'] : null,
        'opposite_goalkeeper' => $_activeLineups[$oppositeDirection]['players'][1]['player_name'],
        'team_side' => $_activeLineups[$_attackDirection]['club_code_name'],
      ];
      $commentaryParamRef['ref_infos'] = [
        'shot' => $simulationCalculator->convertFormationPlaceToUserPlateCardId($_activeLineups, $_attackDirection, $currentFormationPlace),
        'assist' => $isAssistExist ? $_activeLineups[$_attackDirection]['players'][$beforeFormationPlace]['user_plate_card_id'] : null,
        'opposite_goalkeeper' => $simulationCalculator->convertFormationPlaceToUserPlateCardId($_activeLineups, $oppositeDirection, 1),
        'team_side' => $_attackDirection,
      ];
    } else if ($_ending === SimulationCommentType::BLOCKED) {
      // $opposite$simulationCalculator->getOppositeDirection($_attackDirection);

      $blockedPlayer = array_pop($_activeLineups[$simulationCalculator->getOppositeDirection($_attackDirection)]['event_memory'][$_ending]);
      // $blockedPlayer = $simulationCalculator->getRandomFormationPlace(
      //   $_activeLineups,
      //   $_attackDirection,
      //   json_decode($_stepBabo[$_i]['coords']),
      //   null,
      //   null,
      //   null,
      //   true,
      //   $_activeLineups[$oppositeDirection]['formation_used']
      // );
      $commentaryParamRef['comment'] = [
        'shot' => $_activeLineups[$_attackDirection]['players'][$currentFormationPlace]['player_name'],
        'opposite_player' => $_activeLineups[$oppositeDirection]['players'][$blockedPlayer]['player_name'],
      ];
      $commentaryParamRef['ref_infos'] = [
        'shot' => $simulationCalculator->convertFormationPlaceToUserPlateCardId($_activeLineups, $_attackDirection, $currentFormationPlace),
        'opposite_player' => $simulationCalculator->convertFormationPlaceToUserPlateCardId($_activeLineups, $oppositeDirection, $blockedPlayer),
      ];
    } else if ($_ending === SimulationCommentType::FIRST_HALF_START) {
    } else if ($_ending === SimulationCommentType::FIRST_HALF_END) {
    } else if ($_ending === SimulationCommentType::SECOND_HALF_START) {
    } else if ($_ending === SimulationCommentType::SECOND_HALF_END) {
      $commentaryParamRef['comment'] = ['result' => $_activeLineups['game_result']];
      $commentaryParamRef['ref_infos'] = [];
    }

    $_stepBabo[$_i]['commentary_template_id'] = $simulationCalculator->getRandomCommentaryId($_ending);
    $_stepBabo[$_i]['ref_params'] = json_encode($commentaryParamRef);
  }
}
