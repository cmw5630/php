<?php

namespace App\Libraries\Classes\simulation;

use App\Enums\Simulation\SimulationEventType;
use App\Enums\SimulationCalculator\SimulationCalculatorType;
use App\Libraries\Classes\SimulationCalculator;

class SubstituteEventHandler
{

  public function __construct()
  {
  }

  public function handle(
    &$_stepBabo,
    $_i,
    &$_activeLineups
  ) {
    /** 
     *  @var SimulationCalculator $simulationCalculator
     */
    $simulationCalculator = app(SimulationCalculatorType::SIMULATION);
    // $i = $_eventParamSet['i'];
    // $_activeLineups = &$_eventParamSet['active_lineups'];

    $_stepBabo[$_i]['commentary_template_id'] = $simulationCalculator->getRandomCommentaryId(SimulationEventType::SUBSTITUTE);
    $refInfos = $_stepBabo[$_i]['ref_infos_temp'];
    $teamSide = $refInfos['team_side'];
    $team = $_activeLineups[$teamSide]['club_code_name'];
    foreach ($_activeLineups[$teamSide]['substitutions'] as $idx => $player) {
      if ($player['user_plate_card_id'] === $refInfos['out']['user_plate_card_id']) {
        $out = $player['player_name'];
      }
    }
    $in = $_activeLineups[$teamSide]['players'][$refInfos['out']['formation_place']]['player_name'];
    $commentaryParamRef['comment'] = ['team' => $team, 'in' => $in, 'out' => $out];
    $commentaryParamRef['ref_infos'] = $refInfos;
    unset($_stepBabo[$_i]['ref_infos_temp']); // 이전 스텝에서 복사된 내용 제거(필요없지만 만일을 위해)

    if (!empty($commentaryParamRef)) $_stepBabo[$_i]['ref_params'] = json_encode($commentaryParamRef);
  }

  // private function handleCommentary(array &$_eventParamSet)
  // {
  //   /** 
  //    *  @var SimulationCalculator $simulationCalculator
  //    */
  //   $simulationCalculator = app(SimulationCalculatorType::SIMULATION);
  // }
}
