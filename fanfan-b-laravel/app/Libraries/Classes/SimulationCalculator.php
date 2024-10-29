<?php

namespace App\Libraries\Classes;

use App\Enums\Opta\Player\PlayerSubPosition;
use App\Enums\Simulation\SimulationEndingType;
use App\Enums\Simulation\SimulationEventType;
use App\Enums\Simulation\SimulationTeamSide;
use App\Enums\SimulationCalculator\SimulationCalculatorType;
use App\Libraries\Classes\simulation\FoulEventHandler;
use App\Libraries\Classes\simulation\PassEventHandler;
use App\Libraries\Classes\simulation\PkEventHandler;
use App\Libraries\Classes\simulation\ShotEventHandler;
use App\Models\simulation\SimulationCommentaryTemplate;
use App\Models\simulation\SimulationLeagueStat;
use App\Models\simulation\SimulationSchedule;
use App\Models\simulation\SimulationUserLeague;
use App\Models\simulation\SimulationUserLineup;
use App\Models\simulation\SimulationUserLineupMeta;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Str;
use Throwable;

class SimulationCalculator
{

  private $commantaryTemplats = [];
  public $simulationEventHander;
  private $firstEventsOfPattern;
  private $simulationRatingConfig;

  public function __construct()
  {
    $this->simulationEventHander = (new PassEventHandler);
    $foulEventHander = (new FoulEventHandler);
    $shotEventHander = (new ShotEventHandler);
    $pkEventHander = (new PkEventHandler);
    $this->simulationEventHander->setNextHandler($foulEventHander);
    $foulEventHander->setNextHandler($shotEventHander);
    $shotEventHander->setNextHandler($pkEventHander);

    $this->commantaryTemplats = SimulationCommentaryTemplate::get()->groupBy('name')->toArray();

    $a = array_unique(array_merge(
      array_keys(config('simulationrating.event_series')),
      array_keys(config('simulationrating.event_ending'))
    ));
    $this->firstEventsOfPattern = array_filter($a, function ($value) {
      return $value !== 'none';
    });

    $this->simulationRatingConfig = config('simulationrating');
  }

  public function getRandomCommentaryId($_commentaryName)
  {
    if (empty($this->commantaryTemplats)) {
      $this->commantaryTemplats = SimulationCommentaryTemplate::get()->groupBy('name')->toArray();
    }
    $commSet = $this->commantaryTemplats[$_commentaryName][array_rand($this->commantaryTemplats[$_commentaryName])];
    return $commSet['id'];
  }


  public function convertFormationPlaceToUserPlateCardId($_activeLineups, $_teamSide, $_formationPlace): int
  {
    return ($_activeLineups[$_teamSide]['players'][$_formationPlace]['user_plate_card_id']);
  }

  public function getPlayingSeconds($_playingSeconds, $_endingType = null)
  {
    if ($_endingType === SimulationEndingType::FIRST_HALF_START) {
      return 0;
    } else if ($_endingType === SimulationEndingType::FIRST_HALF_END) {
      return $_playingSeconds;
    } else if ($_endingType === SimulationEndingType::SECOND_HALF_START) {
      return  45 * 60 + 1;
    }
    return $_playingSeconds + 16;
  }

  public function aggreSave(&$_activeLineups, $_sequence)
  {
    $attackDirection = $_sequence['attack_direction'];
    $oppositeDirection = $attackDirection === 'home' ? 'away' : 'home';
    if ($_sequence['ending'] === 'saved') {
      if (isset($_activeLineups[$oppositeDirection]['players'][1]['save'])) {
        $_activeLineups[$oppositeDirection]['players'][1]['save']++;
      } else {
        $_activeLineups[$oppositeDirection]['players'][1]['save'] = 1;
      }
      return;
    }
  }

  // public function aggreGoalAssist(&$_activeLineups, $_sequence, $_i, $_stepBabo, $_beforeFromationPlace, $_currentFormationPlace, &$_homeGoal, &$_awayGoal)
  // {
  //   $attackDirection = $_sequence['attack_direction'];
  //   if (!isset($_sequence['step' . $_i + 1]) && $_sequence['ending'] === 'goal') { // 마지막이 goal event면 (득점 step)
  //     ${'_' . $attackDirection . 'Goal'}++;
  //     // shot
  //     if ($_stepBabo[$_i]['event'] === 'shot') {
  //       $player = &$_activeLineups[$attackDirection]['players'][$_beforeFromationPlace];
  //       !isset($player['assist']) ? $player['assist'] = 1 : $player['assist']++;
  //     }
  //     // shot, pk
  //     if ($_stepBabo[$_i]['event'] === 'shot' || $_stepBabo[$_i]['event'] === 'pk') {
  //       $player = &$_activeLineups[$attackDirection]['players'][$_currentFormationPlace];
  //       !isset($player['goal']) ? $player['goal'] = 1 : $player['goal']++;
  //     }
  //   } else if ($_stepBabo[$_i]['event'] === 'shot') { // no goal
  //     $player = &$_activeLineups[$attackDirection]['players'][$_beforeFromationPlace];
  //     !isset($player['key_pass']) ? $player['key_pass'] = 1 : $player['key_pass']++;
  //   }
  // }

  public function getOppositeDirection($_attackDirection)
  {
    return $_attackDirection === SimulationTeamSide::HOME ? SimulationTeamSide::AWAY : SimulationTeamSide::HOME;
  }


  public function solveEventSplit(
    $_sequence,
    &$_stepBabo,
    $_i,
    &$_activeLineups,
    $_homeFormationUsed,
    $_awayFormationUsed,
    $_isLastStep,
  ) {
    /**
     * default format  임시 코드
     */
    // $_stepBabo[$_i]['formation_place'] = $beforeFormationPlace = $_stepBabo[$_i - 1]['formation_place'] ?? null;

    $attackDirection = $_sequence['attack_direction'];
    $oppositeDirection = $this->getOppositeDirection($attackDirection);

    $ending = $_sequence['ending'];
    // $eventSplit = $_sequence['event_split'];
    // 이벤트
    $eventType = $_stepBabo[$_i]['event'] = $_sequence['event_split']['event'][$_i] ?? SimulationEventType::PASS;
    if ($_stepBabo[$_i]['event'] === SimulationEventType::PASS) {
      unset($_stepBabo[$_i]['event']); // pass 이벤트는 null로 처리하기로 약속
    }

    $commentType = $_sequence['event_split']['comm'][$_i] ?? null;

    $coordsOrigin = $_stepBabo[$_i]['coords'];

    $eventParamSet = [
      'sequence' => $_sequence,
      'event_type' => $eventType,
      'active_lineups' => &$_activeLineups,
      'step_babo' => &$_stepBabo,
      'i' => $_i,
      'attack_direction' => $attackDirection,
      'coords_origin' => $coordsOrigin,
      'attack_formation_used' => ${'_' . $attackDirection . 'FormationUsed'},
      'before_formation_place' => $_stepBabo[$_i - 1]['formation_place'] ?? null,
      'opposite_formation_used' => ${'_' . $oppositeDirection . 'FormationUsed'},
      'comment_type' => $commentType,
      'is_last_step' => $_isLastStep,
      'ending' => $ending,
    ];

    /** 
     *  @var SimulationCalculator $simulationCalculator
     */
    $simulationCalculator = app(SimulationCalculatorType::SIMULATION);
    $currentFormationPlace = $simulationCalculator->getRandomFormationPlace(
      $eventParamSet['active_lineups'],
      $eventParamSet['attack_direction'],
      $eventParamSet['coords_origin'],
      $eventParamSet['attack_formation_used'],
      $eventParamSet['event_type'],
      $eventParamSet['before_formation_place'],
      false,
      $eventParamSet['opposite_formation_used'],
    );
    $eventParamSet['step_babo'][$eventParamSet['i']]['formation_place'] = $currentFormationPlace;

    $this->findEventEndingPattern($eventParamSet);
    $this->calDifficultForRatingStats($eventParamSet);
    $this->simulationEventHander->handle($eventParamSet);
    return;


    // // 파울 관련(실시간으로 선수를 랜덤으로 선택해야하므로 집계와 commentary 생성작업 동시처리.)
    // // else 
    // if (Str::startsWith($eventType, 'foul')) {
    //   // 파울 관련 집계처리
    //   $foulFormationPlace = $this->getRandomFormationPlace(
    //     $_activeLineups,
    //     $attackDirection,
    //     $_stepBabo[$_i]['coords'],
    //     ${'_' . $attackDirection  . 'FormationUsed'},
    //     $eventType,
    //     $beforeFormationPlace, // foul call에선 의미없음
    //     true,
    //     ${'_' . $oppositeDirection  . 'FormationUsed'},
    //   );


    //   if (Str::startsWith($_stepBabo[$_i]['event'], 'foul_y')) { // yellow card
    //     $_activeLineups[$oppositeDirection]['players'][$foulFormationPlace]['yellow_card_count']++;
    //   } else if (Str::startsWith($_stepBabo[$_i]['event'], 'foul_r')) { // red card
    //     $_activeLineups[$oppositeDirection]['players'][$foulFormationPlace]['red_card_count']++;
    //     $_activeLineups[$oppositeDirection]['players'][$foulFormationPlace]['is_changed'] = true;
    //     $_activeLineups[$oppositeDirection]['r_card_players'][] = $foulFormationPlace;
    //     //퇴장 로직
    //     $_activeLineups[$oppositeDirection]['substitutions'][] = $_activeLineups[$oppositeDirection]['players'][$foulFormationPlace];
    //     unset($_activeLineups[$oppositeDirection]['players'][$foulFormationPlace]);
    //   }
    // }
  }

  //=============CUSTOM=============>>
  // custome event 처리 -> 
  // public function processSubstituteEvent(&$_stepBabo, $_i, &$_activeLineups,)
  // {
  //   /**
  //    * 이벤트 처리 없음 
  //    */
  //   $this->processSubstituteCommentary($_stepBabo, $_i, $_activeLineups,);
  // }
  // //

  // // custom commentary 처리 -> 
  // private function processSubstituteCommentary(&$_stepBabo, $_i, &$_activeLineups,)
  // {
  //   $_stepBabo[$_i]['commentary_template_id'] = $this->getRandomCommentaryId(SimulationEventType::SUBSTITUTE);
  //   $refInfos = $_stepBabo[$_i]['ref_infos_temp'];
  //   $teamSide = $refInfos['team_side'];
  //   $team = $_activeLineups[$teamSide]['club_code_name'];
  //   foreach ($_activeLineups[$teamSide]['substitutions'] as $idx => $player) {
  //     if ($player['user_plate_card_id'] === $refInfos['out']['user_plate_card_id']) {
  //       $out = $player['player_name'];
  //     }
  //   }
  //   $in = $_activeLineups[$teamSide]['players'][$refInfos['out']['formation_place']]['player_name'];
  //   $commentaryParamRef['comment'] = ['team' => $team, 'in' => $in, 'out' => $out];
  //   $commentaryParamRef['ref_infos'] = $refInfos;
  //   unset($_stepBabo[$_i]['ref_infos_temp']); // 이전 스텝에서 복사된 내용 제거(필요없지만 만일을 위해)

  //   if (!empty($commentaryParamRef)) $_stepBabo[$_i]['ref_params'] = json_encode($commentaryParamRef);
  // }


  // public function makeCommentary(
  //   &$_stepBabo,
  //   $i,
  //   &$_activeLineups,
  //   $_commentType,
  //   $_attackDirection,
  //   $beforeFormationPlace,
  //   $currentFormationPlace,
  // ) {
  //   $oppositeDirection = $this->getOppositeDirection($_attackDirection);

  // commentary가 있을 경우
  // if (Str::startsWith($_commentType, 'foul')) {
  //   // 코멘터리 준비를 위한 코드
  //   try {
  //     $xx['comment'] = [
  //       'foul' => $_activeLineups[$oppositeDirection]['players'][$foulFormationPlace]['player_name'],
  //       'was_fouled' => $_activeLineups[$_attackDirection]['players'][$currentFormationPlace]['player_name'],
  //       'was_fouled_team' => $_activeLineups[$_attackDirection]['club_code_name'],
  //       'attack_team' => $_activeLineups[$_attackDirection]['club_code_name'],
  //     ];
  //   } catch (Throwable $e) {
  //     logger($_activeLineups);
  //     logger($_attackDirection);
  //     logger($_stepBabo[$i]['coords']);
  //     logger($_activeLineups[$_attackDirection]['formation_used']);
  //     logger($beforeFormationPlace);
  //     logger($_activeLineups[$oppositeDirection]['formation_used']);
  //     logger($e);
  //     dd('!!!');
  //   }
  //   $xx['ref_infos'] = [
  //     'foul_team' => $oppositeDirection,
  //     'foul' => $this->convertFormationPlaceToUserPlateCardId($_activeLineups, $oppositeDirection, $foulFormationPlace),
  //     'was_fouled' => $this->convertFormationPlaceToUserPlateCardId($_activeLineups, $_attackDirection, $currentFormationPlace),
  //     'was_fouled_team' => $_attackDirection,
  //     'attack_team' => $_attackDirection,
  //   ];
  //   if ($_commentType === 'foul_pk' || $_commentType === 'foul_y_pk') {
  //     // format setting;
  //   } else if ($_commentType === 'foul_y_free') {
  //     // format setting;
  //   } else if ($_commentType === 'foul_r_free' || $_commentType === 'foul_r_pk') {
  //     $xx['comment']['red_card_team'] = $_activeLineups[$oppositeDirection]['club_code_name'];
  //     $xx['ref_infos']['red_card_team'] = $oppositeDirection;
  //   }
  // }

  // if (!empty($xx)) $_stepBabo[$i]['ref_params'] = json_encode($xx);
  // }


  // public function makeCommentaryDeprecated(
  //   &$_stepBabo,
  //   $i,
  //   &$_activeLineups,
  //   $_commentaryName, // 3가지의미 (1. commentaryName, 2. ending, 3. susbstitue)
  //   $_attackDirection,
  //   $beforeFormationPlace,
  //   $currentFormationPlace,
  // ) {
  //   if ($_commentaryName === null) return;
  //   $oppositeDirection  = $_attackDirection === 'home' ? 'away' : 'home';

  //   $commSet = $this->commantaryTemplats[$_commentaryName][array_rand($this->commantaryTemplats[$_commentaryName])];
  //   $_stepBabo[$i]['commentary_template_id'] = $commSet['id'];
  //   $xx = [];

  //   // 파울 관련(실시간으로 선수를 랜덤으로 선택해야하므로 집계와 commentary 생성작업 동시처리.)
  //   if (Str::startsWith($_stepBabo[$i]['event'], 'foul')) {
  //     // 파울 관련 집계처리
  //     $foulFormationPlace = $this->getRandomFormationPlace(
  //       $_activeLineups,
  //       $_attackDirection,
  //       $_stepBabo[$i]['coords'],
  //       $_activeLineups[$_attackDirection]['formation_used'],
  //       $_stepBabo[$i]['event'],
  //       $beforeFormationPlace, // foul call에선 의미없음
  //       true,
  //       $_activeLineups[$oppositeDirection]['formation_used'],
  //     );
  //     // commentary가 있을 경우
  //     if (Str::startsWith($_commentaryName, 'foul')) {
  //       // 코멘터리 준비를 위한 코드
  //       try {
  //         $xx['comment'] = [
  //           'foul' => $_activeLineups[$oppositeDirection]['players'][$foulFormationPlace]['player_name'],
  //           'was_fouled' => $_activeLineups[$_attackDirection]['players'][$currentFormationPlace]['player_name'],
  //           'was_fouled_team' => $_activeLineups[$_attackDirection]['club_code_name'],
  //           'attack_team' => $_activeLineups[$_attackDirection]['club_code_name'],
  //         ];
  //       } catch (Throwable $e) {
  //         logger($_activeLineups);
  //         logger($_attackDirection);
  //         logger($_stepBabo[$i]['coords']);
  //         logger($_activeLineups[$_attackDirection]['formation_used']);
  //         logger($beforeFormationPlace);
  //         logger($_activeLineups[$oppositeDirection]['formation_used']);
  //         logger($e);
  //         dd('!!!');
  //       }
  //       $xx['ref_infos'] = [
  //         'foul_team' => $oppositeDirection,
  //         'foul' => $this->convertFormationPlaceToUserPlateCardId($_activeLineups, $oppositeDirection, $foulFormationPlace),
  //         'was_fouled' => $this->convertFormationPlaceToUserPlateCardId($_activeLineups, $_attackDirection, $currentFormationPlace),
  //         'was_fouled_team' => $_attackDirection,
  //         'attack_team' => $_attackDirection,
  //       ];
  //       if ($_commentaryName === 'foul_pk' || $_commentaryName === 'foul_y_pk') {
  //         // format setting;
  //       } else if ($_commentaryName === 'foul_y_free') {
  //         // format setting;
  //       } else if ($_commentaryName === 'foul_r_free' || $_commentaryName === 'foul_r_pk') {
  //         $xx['comment']['red_card_team'] = $_activeLineups[$oppositeDirection]['club_code_name'];
  //         $xx['ref_infos']['red_card_team'] = $oppositeDirection;
  //       }
  //     }

  //     if (Str::startsWith($_stepBabo[$i]['event'], 'foul_y')) { // yellow card
  //       $_activeLineups[$oppositeDirection]['players'][$foulFormationPlace]['yellow_card_count']++;
  //     } else if (Str::startsWith($_stepBabo[$i]['event'], 'foul_r')) { // red card
  //       $_activeLineups[$oppositeDirection]['players'][$foulFormationPlace]['red_card_count']++;
  //       $_activeLineups[$oppositeDirection]['players'][$foulFormationPlace]['is_changed'] = true;
  //       $_activeLineups[$oppositeDirection]['r_card_players'][] = $foulFormationPlace;
  //       //퇴장 로직
  //       $_activeLineups[$oppositeDirection]['substitutions'][] = $_activeLineups[$oppositeDirection]['players'][$foulFormationPlace];
  //       unset($_activeLineups[$oppositeDirection]['players'][$foulFormationPlace]);
  //     }
  //   }
  //   // 기타 commantary 관련
  //   if ($_commentaryName === 'cornerkick' || $_commentaryName === 'cross' || $_commentaryName === 'shot') { //event
  //     $xx['comment'] = [
  //       'assist' => $_activeLineups[$_attackDirection]['players'][$beforeFormationPlace]['player_name'],
  //       'shot' => $_activeLineups[$_attackDirection]['players'][$currentFormationPlace]['player_name'],
  //     ];
  //     $xx['ref_infos'] = [
  //       'shot_team' => $_attackDirection,
  //       'assist' => $this->convertFormationPlaceToUserPlateCardId($_activeLineups, $_attackDirection, $beforeFormationPlace),
  //       'shot' => $this->convertFormationPlaceToUserPlateCardId($_activeLineups, $_attackDirection, $currentFormationPlace),
  //     ];
  //     // } else if ($_commentaryName === 'pass_comm') { // commentary
  //     //   $xx['comment'] = [
  //     //     'attack_team' => $_activeLineups[$_attackDirection]['club_code_name'],
  //     //   ];
  //     //   $xx['ref_infos'] = [
  //     //     'attack_team' => $_attackDirection,
  //     //   ];
  //   } else if ($_commentaryName === 'pass') { // event
  //     $xx['comment'] = [
  //       'attack_team' => $_activeLineups[$_attackDirection]['club_code_name'],
  //       'opposite_team' => $_activeLineups[$oppositeDirection]['club_code_name'],
  //     ];
  //     $xx['ref_infos'] = [
  //       'attack_team' => $_attackDirection,
  //       'opposite_team' => $oppositeDirection,
  //     ];
  //   } else if ($_commentaryName === 'saved') { // ending
  //     $xx['comment'] = [
  //       'shot' => $_activeLineups[$_attackDirection]['players'][$currentFormationPlace]['player_name'],
  //       'opposite_goalkeeper' => $_activeLineups[$oppositeDirection]['players'][1]['player_name'],
  //     ];
  //     $xx['ref_infos'] = [
  //       'shot_team' => $_attackDirection,
  //       'shot' => $this->convertFormationPlaceToUserPlateCardId($_activeLineups, $_attackDirection, $currentFormationPlace),
  //       'opposite_goalkeeper' => $this->convertFormationPlaceToUserPlateCardId($_activeLineups, $oppositeDirection, 1),
  //     ];
  //   } else if ($_commentaryName === 'out' || $_commentaryName === 'hitwoodwork' || $_commentaryName === 'goal') {  // ending
  //     $xx['comment'] = [
  //       'shot' => $_activeLineups[$_attackDirection]['players'][$currentFormationPlace]['player_name'],
  //     ];
  //     $xx['ref_infos'] = [
  //       'shot_team' => $_attackDirection,
  //       'shot' => $this->convertFormationPlaceToUserPlateCardId($_activeLineups, $_attackDirection, $currentFormationPlace),
  //     ];
  //   } else if ($_commentaryName === 'blocked') { // ending 
  //     $oppositeFormationPlace = $this->getRandomFormationPlace(
  //       $_activeLineups,
  //       $_attackDirection,
  //       $_stepBabo[$i]['coords'],
  //       $_activeLineups[$_attackDirection]['formation_used'],
  //       'blocked',
  //       $beforeFormationPlace, // foul*, blocked call에선 의미없음
  //       true,
  //       $_activeLineups[$oppositeDirection]['formation_used'],
  //     );
  //     $xx['comment'] = [
  //       'shot' => $_activeLineups[$_attackDirection]['players'][$currentFormationPlace]['player_name'],
  //       'opposite_player' => $_activeLineups[$oppositeDirection]['players'][$oppositeFormationPlace]['player_name'],
  //     ];
  //     $xx['ref_infos'] = [
  //       'shot_team' => $_attackDirection,
  //       'shot' => $this->convertFormationPlaceToUserPlateCardId($_activeLineups, $_attackDirection, $currentFormationPlace),
  //       'opposite_player' => $this->convertFormationPlaceToUserPlateCardId($_activeLineups, $oppositeDirection, $oppositeFormationPlace),
  //     ];
  //   }

  //   if (!empty($xx)) $_stepBabo[$i]['ref_params'] = json_encode($xx);
  // }


  // public function makeEndingCommentary(
  //   &$_stepBabo,
  //   $_i,
  //   $_activeLineups,
  //   $_endingType,
  //   $_attackDirection = null,
  // ) {
  //   $commSet = $this->commantaryTemplats[$_endingType][array_rand($this->commantaryTemplats[$_endingType])];
  //   $_stepBabo[$_i]['commentary_template_id'] = $commSet['id'];
  //   if (Str::endsWith($_endingType, 'start') || Str::endsWith($_endingType, 'end')) {
  //     $xx['comment'] = ['result' => $_activeLineups['game_result']];
  //     $xx['ref_infos'] = [];
  //   } else if ($_endingType === 'goal') {
  //     $currentFormationPlace = $_stepBabo[$_i]['formation_place'];
  //     $xx['comment'] = ['shot' => $_activeLineups[$_attackDirection]['players'][$currentFormationPlace]['player_name']];
  //     $xx['ref_infos'] = ['shot' => $this->convertFormationPlaceToUserPlateCardId($_activeLineups, $_attackDirection, $currentFormationPlace)];
  //   }
  //   $_stepBabo[$_i]['ref_params'] = json_encode($xx);
  // }


  /*
  * Area 관련
  */

  private function getAreaPoint($_coords): String
  {
    $conf = config('simulationArea.formation_area');
    $x = $_coords[0];
    $y = $_coords[1];
    foreach ($conf['x'] as $xKey => $ySet) {
      if ($x <= $xKey) {
        foreach ($ySet['y'] as $yKey => $yValue) {
          if ($y <= $yKey) {
            if ($yValue === null) break;
            return $yValue;
          }
        }
      }
    }
  }


  public function correctCoords($_nthHalf, $_teamSide, $_coords)
  {
    if ($_nthHalf === 'second') {
      return $this->reverseCoordsDirection($_coords);
    }
    return $_coords;
  }

  private function getSubPositionsForCal($_activeLineups, $_teamSide, $_formationUsed)
  {
    // 임시 코드를 위한 임시함수
    $confSubs = config('formation-by-sub-position.formation_used')[$_formationUsed];
    $exitPlayers = $_activeLineups[$_teamSide]['r_card_players']; // 퇴장선수 처리

    $newConfSub = [];
    foreach ($confSubs as $fpNumber => $subPStr) {
      if (in_array($fpNumber, $exitPlayers)) continue;
      $newConfSub[$fpNumber] = $subPStr;
    }
    return $newConfSub;
  }

  public function reverseCoordsDirection($_coords)
  {
    return [4 * 2 - $_coords[0], 3 * 2 - $_coords[1]];
  }


  private function getRandomOppotiteFormationPlace(
    $_activeLineups,
    $_eventType,
    $_oppositeDirection,
    $_coordsForCal,
    $_oppositeFormationUsed = null
  ) {
    //oppositeTeam -->>
    $oppositeDirectionConfig = $this->getSubPositionsForCal($_activeLineups, $_oppositeDirection, $_oppositeFormationUsed);
    $simulationConfig = config('simulationArea.formation_used');
    // if (Str::startsWith($_eventType, 'foul') || $_eventType === 'blocked') { // blocked는 ending type
    if ($_coordsForCal[0] === 0 && ($_coordsForCal[1] === 4 || $_coordsForCal[1] === 3 || $_coordsForCal[1] === 2)) {
      // $k = config('formation-by-sub-position.formation_used')[$_oppositeFormationUsed];
      $defenders = [];
      foreach ($oppositeDirectionConfig as $fp => $subS) {
        if (Str::endsWith($subS, 'b')) {
          $defenders[$fp] = $subS;
        }
      }
      // 상대팀의 b로 끝나는 place_formation 중 랜덤으로 뽑기
      return array_rand($defenders);
    }
    // }
    $simulationConfig = config('simulationArea.formation_used');
    $formationCollections = $simulationConfig[$_oppositeFormationUsed]['formation_place'][$this->getAreaPoint($_coordsForCal)];

    foreach ($_activeLineups[$_oppositeDirection]['r_card_players'] as $exitPlayer) {
      unset($formationCollections[$exitPlayer]);
    }

    $totalPercent = array_sum($formationCollections);
    $randomPercentPoint = mt_rand(1, $totalPercent);

    $currentSum = 0;
    $innerBeforFormationPlace = null;
    foreach ($formationCollections as $formationPlace => $percentPoint) {
      $innerBeforFormationPlace = $formationPlace;
      $currentSum += $percentPoint;
      if ($randomPercentPoint <= $currentSum) /* && $formationPlace !== $_beforeFromationPlace) */ {
        return $formationPlace;
      }
    }
    return $innerBeforFormationPlace;
  }

  public function getRandomFormationPlace($_activeLineups, $_attackDirection, $_coords, $_attackSideFormationUsed, $_eventType, $_beforeFromationPlace, $_isOppositeTeam = false, $_oppositeFormationUsed = null)
  {
    /**
     * $_coordsForCal : 실제 좌표가 아닌 계산 공식에 맞춘 좌표(실제 좌표에 반영 X)
     */
    $coordsForCal = $_coords;
    if (($_attackDirection === SimulationTeamSide::AWAY) ^ $_isOppositeTeam) {
      $coordsForCal = $this->reverseCoordsDirection($_coords);
    }

    if ($_isOppositeTeam) {
      $oppositeDirection = $_attackDirection === 'home' ? 'away' : 'home';
      return $this->getRandomOppotiteFormationPlace($_activeLineups, $_eventType, $oppositeDirection, $coordsForCal, $_oppositeFormationUsed);
    } else {
      $attackDirectionConfig = $this->getSubPositionsForCal($_activeLineups, $_attackDirection, $_attackSideFormationUsed);
    }


    if ($_eventType === SimulationEventType::CORNERKICK) {
      // (임시코드) format setting
      return array_rand(
        array_filter(
          $attackDirectionConfig,
          // config('formation-by-sub-position.formation_used')[$_attackSideFormationUsed],
          function ($x) {
            return $x != PlayerSubPosition::GK;
          }
        )
      );
    } else if ($_eventType === SimulationEventType::PK) {
      // (임시코드) format setting
      return array_rand(
        array_filter(
          $attackDirectionConfig,
          // config('formation-by-sub-position.formation_used')[$_attackSideFormationUsed],
          function ($x) {
            return $x != PlayerSubPosition::GK;
          }
        )
      );
    }

    $simulationConfig = config('simulationArea.formation_used');
    $formationCollections = $simulationConfig[$_attackSideFormationUsed]['formation_place'][$this->getAreaPoint($coordsForCal)];
    foreach ($_activeLineups[$_attackDirection]['r_card_players'] as $exitPlayer) {
      unset($formationCollections[$exitPlayer]);
    }

    $totalPercent = array_sum($formationCollections);
    $randomPercentPoint = mt_rand(1, $totalPercent);

    $currentSum = 0;
    $innerBeforFormationPlace = null;
    foreach ($formationCollections as $formationPlace => $percentPoint) {
      $innerBeforFormationPlace = $formationPlace;
      $currentSum += $percentPoint;
      if (($randomPercentPoint <= $currentSum) && ($formationPlace !== $_beforeFromationPlace)) {
        return $formationPlace;
      }
    }
    return $innerBeforFormationPlace;
  }

  public function getTimeTaken($_eventType, $_endingType, $_isHighlight): float
  {
    if ($_isHighlight) {
      return 3;
    } else if ($_endingType === SimulationEndingType::FIRST_HALF_END) {
      return 60;
    } else if (
      $_endingType === SimulationEndingType::FIRST_HALF_START ||
      $_endingType === SimulationEndingType::SECOND_HALF_START ||
      $_endingType === SimulationEndingType::SECOND_HALF_END
    ) {
      return 3;
    } else if ($_eventType === SimulationEventType::SUBSTITUTE) {
      return 3;
    }
    return 0.8;
  }


  public function getSubstitutionTimeRange($_homeSubstitutionCount, $_awaySubstitutionCount): array
  {
    $totalSubstitutionCount = $_homeSubstitutionCount + $_awaySubstitutionCount;
    // 교체인원수별 교체 시간 범위
    $result = [];
    $minMaxMap = [];
    foreach (config('simulationpolicies.substitution_minuites_policies') as  $count => $confMap) {
      if ($totalSubstitutionCount >= $count) {
        $minMaxMap = $confMap;
        break;
      }
    }
    while (true) {
      $value = random_int($minMaxMap['min'], $minMaxMap['max']);
      if (in_array($value, $result)) continue;
      $result[] = $value;
      if (count($result) === $totalSubstitutionCount) break;
    }

    $result = array_chunk($result, $_homeSubstitutionCount);

    $homeResult = $result[0];
    $awayResult = $result[1];
    sort($homeResult);
    sort($awayResult);

    return [
      'substitution_count' => $totalSubstitutionCount,
      'home' => $homeResult,
      'away' => $awayResult,
    ];
  }

  public function getLineupFormatForCal(
    string $_homeFormationUsed,
    array $_homeUserLineups,
    string $_awayFormationUsed,
    array $_awayUserLineups,
    array $_homeApplicant,
    array $_awayApplicant,
  ) {
    $result = ['home' => [
      'applicant_id' => $_homeApplicant['id'],
      'club_code_name' => $_homeApplicant['club_code_name'],
      'formation_used' => $_homeFormationUsed,
      'r_card_players' => [],
      'y_card_players' => [],
      'event_memory' => [],
      'players' => [],
      'substitutions' => [],
    ], 'away' => [
      'applicant_id' => $_awayApplicant['id'],
      'club_code_name' => $_awayApplicant['club_code_name'],
      'formation_used' => $_awayFormationUsed,
      'r_card_players' => [],
      'y_card_players' => [],
      'event_memory' => [],
      'players' => [],
      'substitutions' => [],
    ]];
    foreach (['home', 'away'] as $teamSide) {
      foreach (${'_' . $teamSide . 'UserLineups'} as $lineup) {
        $baseOverallList = ['attacking_overall', 'shot', 'finishing', 'dribbles', 'positioning'];
        $keeperOverallList = ['goalkeeping_overall', 'saves', 'high_claims', 'sweeper', 'punches'];

        if ($lineup['sub_position'] === PlayerSubPosition::GK) {
          $baseOverallList = $keeperOverallList;
        }

        $overall = [];
        foreach ($baseOverallList as $name) {
          $overall[$name] = $lineup['user_plate_card']['simulation_overall'][$name];
        }

        if ($lineup['game_started']) {
          $result[$teamSide]['players'][$lineup['formation_place']] =
            [
              'formation_place' => $lineup['formation_place'],
              'user_plate_card_id' => $lineup['user_plate_card_id'],
              'player_name' => $lineup['user_plate_card']['plate_card_with_trashed']['match_name'],
              'overall' =>  $overall,
              'sub_position' => $lineup['sub_position'],
              'position' => $lineup['position'],
              'draft_level' => $lineup['user_plate_card']['draft_level'],
              'card_grade' => $lineup['user_plate_card']['card_grade'],
              'card_grade_idx' => config('constant.DRAFT_CARD_GRADE_ORDER')[$lineup['user_plate_card']['card_grade']],
              'is_changed' => false,
              'yellow_card_count' => 0,
              'red_card_count' => 0,
              'stamina' => 100,
            ];
        } else {
          $result[$teamSide]['substitutions'][] =
            [
              // 'formation_place' => $lineup['formation_place'],
              'user_plate_card_id' => $lineup['user_plate_card_id'],
              'player_name' => $lineup['user_plate_card']['plate_card_with_trashed']['match_name'],
              'overall' =>  $overall,
              'sub_position' => $lineup['sub_position'],
              'position' => $lineup['position'],
              'draft_level' => $lineup['user_plate_card']['draft_level'],
              'card_grade' => $lineup['user_plate_card']['card_grade'],
              'card_grade_idx' => config('constant.DRAFT_CARD_GRADE_ORDER')[$lineup['user_plate_card']['card_grade']],
              'final_overall' => $lineup['user_plate_card']['simulation_overall']['final_overall'][$lineup['sub_position']],
              'is_changed' => false,
              'yellow_card_count' => 0,
              'red_card_count' => 0,
              'stamina' => 100,
            ];
        }
      }
      $result[$teamSide]['substitutions'] = __sortByKeys($result[$teamSide]['substitutions'], ['keys' => ['final_overall', 'draft_level', 'card_grade_idx'], 'hows' => ['desc', 'desc', 'asc']]);
    }
    return $result;
  }

  public function isSubEvent($_endingType): bool
  {
    if (in_array($_endingType, [
      SimulationEndingType::FIRST_HALF_START,
      SimulationEndingType::FIRST_HALF_END,
      SimulationEndingType::SECOND_HALF_START,
      SimulationEndingType::SECOND_HALF_END,
    ])) return true;
    return false;
  }

  public function calStamina(&$_activeLineups, $_endingType)
  {
    if ($this->isSubEvent($_endingType)) return;
    $staminaMap = config('simulationpolicies.stamina_reducement');
    foreach (['home', 'away'] as $teamSide) {
      foreach ($_activeLineups[$teamSide]['players'] as &$lineup) {
        try {
          $currentStamina = $lineup['stamina'];
        } catch (Throwable $e) {
          logger($lineup);
          logger($_activeLineups);
          throw $e;
        }
        foreach ($staminaMap[$lineup['sub_position']]['policies'] as $staminaPoint => $reduceValue) {
          if ($currentStamina >= $staminaPoint) {
            $lineup['stamina'] = __setDecimal($lineup['stamina'] + $reduceValue + $this->randomFloat($staminaMap[$lineup['sub_position']]['dev']), 3);
            break;
          }
        }
      }
    }
  }

  public function randomFloat($_devs): string
  {
    $range = $_devs['upper'] - $_devs['under'];
    $num = mt_rand() / mt_getrandmax();
    return ($num * $range) + $_devs['under'];
  }

  public function getStaminas($_activeLineups)
  {
    $result = [];
    foreach (['home', 'away'] as $teamSide) {
      foreach ($_activeLineups[$teamSide]['players'] as $lineup) {
        $result['staminas'][$teamSide][$lineup['user_plate_card_id']] = $lineup['stamina'];
      }
      foreach ($_activeLineups[$teamSide]['substitutions'] as $lineup) {
        $result['staminas'][$teamSide][$lineup['user_plate_card_id']] = $lineup['stamina'];
      }
    }
    return $result;
  }

  public function getPlayerInOut(&$_activeLineups, $_endingType, &$_substitutionTimes, $_playingSeconds, $_nthHalf, $_scheduleId): array
  {
    if ($_endingType === SimulationEndingType::GOAL) return []; // 프론트 로직 구조상 substitute가 shot과 ending = goal 사이(step)에 있으면 안됨.
    $curTime = $_playingSeconds / 60;
    foreach (['home', 'away'] as $teamSide) {
      $teamSideTimes = $_substitutionTimes[$teamSide];
      sort($teamSideTimes); // 오름차순

      foreach ($teamSideTimes as $targetMinute) {
        if ($curTime >= $targetMinute) {
          try {
            $possiblePostions = $this->getPossiblePositions($_activeLineups, $teamSide);
            $outPlayerIdx = $this->getLowestStaminaPlayerIdx($_activeLineups, $teamSide, $possiblePostions);
            $inOut = $this->getInOut($_activeLineups, $teamSide, $possiblePostions, $outPlayerIdx);
            array_shift($_substitutionTimes[$teamSide]);
          } catch (Throwable $e) {
            logger('교체 선수 로직 error');
            logger($_activeLineups);
            logger($teamSide);
            logger($possiblePostions);
            logger($outPlayerIdx);
            logger($_scheduleId);
            logger($e);
            break;
          }
          return $inOut;
        }
      }
    }
    return []; // 교체없음
  }


  private function getLowestStaminaPlayerIdx(array $_activeLineups, string $_teamSide, array $_possiblePositions)
  {
    $minIdx = null;
    $minValue = 999;
    foreach ($_activeLineups[$_teamSide]['players'] as $idx => $lineup) {
      if (in_array(
        $lineup['position'],
        $_possiblePositions,
      ) && $lineup['stamina'] < $minValue) {
        $minIdx = $idx;
        $minValue = $lineup['stamina'];
      }
    }
    return (int)$minIdx;
  }

  private function getPossiblePositions($_activeLineups, $_teamSide)
  {
    // (in)교체가능한 (벤치)선수 pool
    $positions = [];
    foreach ($_activeLineups[$_teamSide]['substitutions'] as $subs) {
      if (!$subs['is_changed']) $positions[] = $subs['position'];
    }
    return array_unique($positions);
  }

  private function getInOut(array &$_activeLineups, string $_teamSide, array $_possiblePositions, int $_outPlayerIdx)
  {
    $outPlayerPosition = $_activeLineups[$_teamSide]['players'][$_outPlayerIdx]['position'];
    $inPlayerIdx = null;

    $subsSet = $_activeLineups[$_teamSide]['substitutions']; // 이미 정렬상태.
    foreach ($subsSet as $idx => $subs) {
      if ($subs['is_changed'] === false && $subs['position'] === $outPlayerPosition) {
        $inPlayerIdx = $idx;
        break;
      }
    }
    $outPlayer = $_activeLineups[$_teamSide]['players'][$_outPlayerIdx];
    $outPlayer['is_changed'] = true;
    // $_activeLineups[$_teamSide]['substitutions'][$inPlayerIdx]['is_changed'] = true;
    $inPlayer = $_activeLineups[$_teamSide]['substitutions'][$inPlayerIdx];
    $inPlayer['is_changed'] = true;
    $_activeLineups[$_teamSide]['players'][$_outPlayerIdx] = $inPlayer;
    $_activeLineups[$_teamSide]['substitutions'][$inPlayerIdx] = $outPlayer;
    return [
      'team_side' => $_teamSide,
      'in' => ['user_plate_card_id' => $inPlayer['user_plate_card_id']],
      'out' => ['user_plate_card_id' => $outPlayer['user_plate_card_id'], 'formation_place' => $outPlayer['formation_place']],
    ];
  }



  public function getSequenceAreaCoordsSet()
  {
    $coorList = [];
    foreach (config('simulationArea.sequence_area') as $idx => $sequenceArea) {
      foreach ($sequenceArea as $rangeSet) {
        $xMin = $rangeSet['x']['min'];
        $xMax = $rangeSet['x']['max'];
        $yMin = $rangeSet['y']['min'];
        $yMax = $rangeSet['y']['max'];
        for ($x = $xMin; $x <= $xMax; $x++) {
          for ($y = $yMin; $y <= $yMax; $y++) {
            $coorList[$idx][] = [$x, $y];
          }
        }
      }
    }
    // 계속 호출하지 말고 리턴 결과를 변수에 담은 후에 쓸 것
    return  $coorList;
  }

  public function getRandomAreaCoords($_arearCoords)
  {
    return $_arearCoords[array_rand($_arearCoords)];
  }

  public function getAttDefPower($_applicantId)
  {
    $config = config('simulationwdl');

    // myTeamDeck
    $teamChk = $teamCnt = [];

    logger('start');
    SimulationUserLineup::with('userPlateCard')
      ->whereHas('userLineupMeta', function ($query) use ($_applicantId) {
        $query->where('applicant_id', $_applicantId);
      })
      ->where('game_started', true)
      ->get()
      ->map(function ($info) use (&$teamCnt) {
        if (!isset($teamCnt[$info->userPlateCard->draft_team_id]['all'])) {
          $teamCnt[$info->userPlateCard->draft_team_id]['all'] = 0;
        }
        if (!isset($teamCnt[$info->userPlateCard->draft_team_id]['game_started'])) {
          $teamCnt[$info->userPlateCard->draft_team_id]['game_started'] = 0;
        }
        $teamCnt[$info->userPlateCard->draft_team_id]['all']++;
        if ($info->game_started) $teamCnt[$info->userPlateCard->draft_team_id]['game_started'] = 1;

        return $teamCnt;
      });

    foreach ($teamCnt as $teamId => $valArr) {
      foreach ($valArr as $type => $cnt) {
        if ($type === 'all' && $cnt === 16) {
          $teamChk[$teamId] = $config['additional_team']['all'][16]['add'];
        } else if ($type === 'game_started' && $cnt >= 8) {
          foreach ($config['additional_team']['game_started'] as $standard => $addArr) {
            if ($cnt >= $standard) {
              $teamChk[$teamId] = $addArr['add'];
            }
          }
        }
      }
    }

    // my 공격력 / 수비력
    $myPower =  ['attack' => [
      'stats_count' => 0,
      'power' => 0
    ], 'defence' => [
      'stats_count' => 0,
      'power' => 0
    ]];

    SimulationUserLineup::whereHas('userLineupMeta', function ($query) use ($_applicantId) {
      $query->where('applicant_id', $_applicantId);
    })
      ->with(['simulationOverall', 'plateCardWithTrashed'])
      ->get()
      ->map(function ($info) use (&$myPower, &$config, $teamChk) {
        foreach (['attack', 'defence'] as $key) {
          $stats = $config[$key . '_power'][$info->simulationOverall->sub_position];
          $myPower[$key]['stats_count'] += count($stats);

          foreach ($stats as $stat) {
            $myPower[$key]['power'] += $info->simulationOverall->$stat['overall'];
            if (isset($teamChk[$info->plateCardWithTrashed->team_id]) && $info->plateCardWithTrashed->team_id === $teamChk[$info->plateCardWithTrashed->team_id]) {
              $myPower[$key]['power'] += $teamChk[$info->plateCardWithTrashed->team_id];
            }
          }
        }
      });

    $myAttackPower = $myPower['attack']['power'];
    $myDefencePower = $myPower['defence']['power'];

    // lineup_meta update
    $myLineupMeta = SimulationUserLineupMeta::where([
      ['applicant_id', $_applicantId],
    ])->first();
    $myLineupMeta->attack_power = $myAttackPower;
    $myLineupMeta->defence_power = $myDefencePower;
    $myLineupMeta->save();
  }

  /**
   * 승무패 로직
   * @return void 
   */
  public function getWDL(string $_scheduleId)
  {
    $scheduleInfo = SimulationSchedule::where('id', $_scheduleId)
      ->withHas('leagueStat')
      ->first();

    $homeId = $scheduleInfo->home_applicant_id;
    $awayId = $scheduleInfo->away_applicant_id;

    foreach (SimulationTeamSide::getValues() as $teamSide) {
      foreach (['Attack', 'Defence'] as $key) {
        ${$teamSide . $key . 'Power'} = 0;
      }
    }

    SimulationUserLineupMeta::whereIn('applicant_id', [$homeId, $awayId])
      ->get()
      ->map(function ($info) use (&$homeAttackPower, &$homeDefencePower, &$awayAttackPower, &$awayDefencePower, $homeId, $awayId) {
        if ($homeId === $info->applicant_id) {
          $homeAttackPower = $info->attack_power;
          $homeDefencePower = $info->defence_power;
        } else if ($awayId === $info->applicant_id) {
          $awayAttackPower = $info->attack_power;
          $awayDefencePower = $info->defence_power;
        }
      });

    $allApplicants = SimulationUserLeague::where('league_id', $scheduleInfo->league_id)
      ->pluck('applicant_id')->toArray();
    $allPowerBase = SimulationUserLineupMeta::whereIn('applicant_id', $allApplicants);
    $allAttackArr = $allPowerBase->pluck('attack_power')->toArray();
    $allDefenceArr = $allPowerBase->pluck('defence_power')->toArray();

    $leagueAttackMax = max($allAttackArr);
    $leagueAttackMin = min($allAttackArr);
    $leagueDefenceMax = max($allDefenceArr);
    $leagueDefenceMin = min($allDefenceArr);

    $changeHomeAttackPower = BigDecimal::of($homeAttackPower - $leagueAttackMin)->dividedBy(BigDecimal::of($leagueAttackMax - $leagueAttackMin), 2, RoundingMode::HALF_UP)->multipliedBy(45)->plus(45);

    $changeAwayAttackPower = BigDecimal::of($awayAttackPower - $leagueAttackMin)->dividedBy(BigDecimal::of($leagueAttackMax - $leagueAttackMin), 2, RoundingMode::HALF_UP)->multipliedBy(45)->plus(45);

    $changeHomeDefencePower = BigDecimal::of($homeDefencePower - $leagueDefenceMin)->dividedBy(BigDecimal::of($leagueDefenceMax - $leagueDefenceMin), 2, RoundingMode::HALF_UP)->multipliedBy(45)->plus(45);
    $changeAwayDefencePower = BigDecimal::of($awayDefencePower - $leagueDefenceMin)->dividedBy(BigDecimal::of($leagueDefenceMax - $leagueDefenceMin), 2, RoundingMode::HALF_UP)->multipliedBy(45)->plus(45);


    // 1. 기본 득점 산출 = 공격력 / 상대팀 수비력
    // 1-home
    $homeBase = $changeHomeAttackPower->dividedBy($changeAwayDefencePower, 2, RoundingMode::HALF_UP);
    // 1-away
    $awayBase = $changeAwayAttackPower->dividedBy($changeHomeDefencePower, 2, RoundingMode::HALF_UP);

    // 2. 추가 득점 산출 = 기본득점 >1 or 공격력 > 상대 수비력
    // 3. 기대득점 = 기본득점 + 추가득점
    if ($homeBase->compareTo(BigDecimal::of(1)) || $homeAttackPower > $awayDefencePower) {
      $homeBase->plus(BigDecimal::of(1));
    }
    if ($awayBase->compareTo(BigDecimal::of(1)) || $awayAttackPower > $homeDefencePower) {
      $awayBase->plus(BigDecimal::of(1));
    }

    $homeBase = (float)$homeBase->toFloat();
    $awayBase = (float)$awayBase->toFloat();

    // 기대득점 lineup_meta update
    $homeLineupMeta = SimulationUserLineupMeta::where('applicant_id', $homeId)->first();
    $homeLineupMeta->expected_score = $homeBase;
    $homeLineupMeta->save();

    $awayLineupMeta = SimulationUserLineupMeta::where('applicant_id', $awayId)->first();
    $awayLineupMeta->expected_score = $awayBase;
    $awayLineupMeta->save();

    // 4. 실제득점 = Poi(기대득점)
    $homeGoal = $this->getRealGoal($homeBase);
    $awayGoal = $this->getRealGoal($awayBase);

    return [
      'home' => $homeGoal,
      'home_expected_score' => $homeBase,
      'away' => $awayGoal,
      'away_expected_score' => $awayBase,
    ];
  }

  private function getRealGoal($_expectedGoal)
  {
    $pmfValues = [];
    $totalPmf = 0;

    // 0부터 10까지의 PMF 값을 계산하고 누적 합계를 구합니다.
    for ($k = 0; $k <= 10; $k++) {
      $pmf = $this->poisson_pmf($_expectedGoal, $k);
      $pmfValues[$k] = $pmf;
      $totalPmf += $pmf;
    }

    // 0과 1 사이의 무작위 값을 생성합니다.
    $randomValue = mt_rand() / mt_getrandmax();

    // PMF 값을 기반으로 무작위 값을 선택합니다.
    $cumulativePmf = 0;
    for ($k = 0; $k <= 10; $k++) {
      $cumulativePmf += $pmfValues[$k] / $totalPmf;
      if ($randomValue <= $cumulativePmf) {
        return $k;
      }
    }
    return -1;
  }

  private function poisson_pmf($lambda, $k)
  {
    return (pow($lambda, $k) * exp(-$lambda)) / $this->factorial($k);
  }

  /**
   * 팩토리얼 값을 계산합니다.
   *
   * @param int $n 팩토리얼을 계산할 숫자
   * @return int 팩토리얼 값
   */
  private function factorial($n)
  {
    if ($n === 0) {
      return 1;
    }
    return $n * $this->factorial($n - 1);
  }

  public function calculate()
  {
    // return $this->{'get' . $this->simulationCalculatorType}();
  }

  private function getEventEndingByI($_sequence, $_i)
  {
    if (isset($_sequence['step' . $_i])) {
      return $_sequence['event_split']['event'][$_i] ?? 'none';
    } else {
      return $_sequence['ending'];
      // ending
    }
  }



  private function calAttackRatingStats() {}

  private function calDefendRatingStats() {}


  private function applyRatingConfig(bool $_isEventSeries, array &$_eventParamSet, $_tokenOne, $_tokenTwo = null, $_tokenThree = null)
  {
    $eventType = $_eventParamSet['event_type'];
    $activeLineups = &$_eventParamSet['active_lineups'];
    $attackDirection = $_eventParamSet['attack_direction'];
    $coords = $_eventParamSet['coords_origin'];
    $oppositeFormationUsed = $_eventParamSet['opposite_formation_used'];
    $stepBabo = $_eventParamSet['step_babo'];
    $i = $_eventParamSet['i'];
    $oppositeFormationPlace = null;

    // logger($_tokenOne . '/' . $_tokenTwo . '/' . $_tokenThree);

    if (Str::startsWith($_tokenOne, 'foul') || in_array(SimulationEndingType::BLOCKED, [$_tokenTwo, $_tokenThree])) {
      $oppositeFormationPlace = $this->getRandomFormationPlace(
        $activeLineups,
        $attackDirection,
        $coords,
        null,
        $eventType,
        null,
        true,
        $oppositeFormationUsed
      );
      if (Str::startsWith($_tokenOne, 'foul')) {
        $activeLineups[$this->getOppositeDirection($attackDirection)]['event_memory'][$eventType][] = $oppositeFormationPlace;
        if (Str::startsWith($_tokenOne, 'foul_y')) {
          $activeLineups[$this->getOppositeDirection($attackDirection)]['y_card_players'][] = $oppositeFormationPlace;
        } else if (Str::startsWith($_tokenOne, 'foul_r')) {
          $activeLineups[$this->getOppositeDirection($attackDirection)]['r_card_players'][] = $oppositeFormationPlace;
        }
      } else {
        $activeLineups[$this->getOppositeDirection($attackDirection)]['event_memory'][SimulationEndingType::BLOCKED][] = $oppositeFormationPlace;
      }
    }

    if ($_isEventSeries) {
      $cfg = $this->simulationRatingConfig['event_series'];
    } else {
      $cfg = $this->simulationRatingConfig['event_ending'];
    }
    if (is_null($_tokenTwo) && !is_null($_tokenThree)) {
      logger('wrong parameter combination bug!!!!!!!!!!!!!!');
      return;
    }

    $result = $cfg[$_tokenOne];
    if (!is_null($_tokenTwo)) {
      $result = $result[$_tokenTwo];
    }
    if (!is_null($_tokenThree)) {
      $result = $result[$_tokenThree];
    }

    $player = $stepBabo[$i]['formation_place'];
    // logger('player:' . $player);
    $prePlayer = $stepBabo[$i - 1]['formation_place'];
    // logger('prePlayer:' . $prePlayer);
    $oppositePlayer = $oppositeFormationPlace;

    if (isset($result[$resultFirstKey = 'attack'][$resultSecondKey = 'pre_player'])) {
      $this->calRatingStats(
        $_eventParamSet,
        $result,
        [
          'result_first_key' => $resultFirstKey,
          'result_second_key' => $resultSecondKey,
          'first_key' => $attackDirection,
          'second_key' => 'players',
          'third_key' => $prePlayer,
        ],
      );
    }
    if (isset($result[$resultFirstKey = 'attack'][$resultSecondKey = 'player'])) {
      $this->calRatingStats(
        $_eventParamSet,
        $result,
        [
          'result_first_key' => $resultFirstKey,
          'result_second_key' => $resultSecondKey,
          'first_key' => $attackDirection,
          'second_key' => 'players',
          'third_key' => $player,
        ],
      );
    }
    if (isset($result[$resultFirstKey = 'defend'][$resultSecondKey = 'player'])) {
      $this->calRatingStats(
        $_eventParamSet,
        $result,
        [
          'result_first_key' => $resultFirstKey,
          'result_second_key' => $resultSecondKey,
          'first_key' => $this->getOppositeDirection($attackDirection),
          'second_key' => 'players',
          'third_key' => $oppositeFormationPlace,
        ],
      );
    }
    if (isset($result[$resultFirstKey = 'defend'][$resultSecondKey = 'keeper'])) {
      $this->calRatingStats(
        $_eventParamSet,
        $result,
        [
          'result_first_key' => $resultFirstKey,
          'result_second_key' => $resultSecondKey,
          'first_key' => $this->getOppositeDirection($attackDirection),
          'second_key' => 'players',
          'third_key' => 1,
        ],
      );
    }

    if (isset($result[$resultFirstKey = 'defend'][$resultSecondKey = 'common'])) {
      // 구현 필요
    }
  }

  private function calRatingStats(&$_eventParamSet, $_result, $_keyDatas)
  {
    $activeLineups = &$_eventParamSet['active_lineups'];
    foreach ($_result[$_keyDatas['result_first_key']][$_keyDatas['result_second_key']]['add'] as $statName => $value) {
      if (!isset($activeLineups[$_keyDatas['first_key']][$_keyDatas['second_key']][$_keyDatas['third_key']][$statName])) {
        $activeLineups[$_keyDatas['first_key']][$_keyDatas['second_key']][$_keyDatas['third_key']][$statName] = 1;
      } else {
        $activeLineups[$_keyDatas['first_key']][$_keyDatas['second_key']][$_keyDatas['third_key']][$statName]++;
      }
    }
  }


  private function calDifficultForRatingStats(&$_eventParamSet)
  {
    /**
     * loss_possessions 계산
     * (first, second, third_plus_)_goal 계산
     * (first, second, third_plus_)_goal_conceded 계산
     * 최종 step에서만 계산
     */
    $sequence = $_eventParamSet['sequence'];
    $currentI = $_eventParamSet['i'];

    // is last step?
    if (!isset($sequence['step' . $currentI + 1])) return;

    $preEvent = $this->getEventEndingByI($sequence, $currentI - 1);
    $currentEvent = $this->getEventEndingByI($sequence, $currentI);
    $ending = $sequence['ending'];
    $attackDirection = $_eventParamSet['attack_direction'];
    // loss_possessions 계산
    $targetFormationPlace = null;
    if (Str::startsWith($preEvent, 'foul_') && $currentEvent === 'none' && !isset($sequence['step' . $currentI + 1]) && $ending !== SimulationEventType::GOAL) {
      $targetFormationPlace = $_eventParamSet['step_babo'][$currentI - 1]['formation_place'];
    } else if ($currentEvent !== SimulationEventType::PK) {
      $targetFormationPlace = $_eventParamSet['step_babo'][$currentI]['formation_place'];
    }
    if (!is_null($targetFormationPlace)) {
      if (isset($_eventParamSet['active_lineups'][$attackDirection]['players'][$targetFormationPlace]['lose_possessions'])) {
        $_eventParamSet['active_lineups'][$attackDirection]['players'][$targetFormationPlace]['lose_possessions']++;
      } else {
        $_eventParamSet['active_lineups'][$attackDirection]['players'][$targetFormationPlace]['lose_possessions'] = 1;
      }
    }
    // (first, second, third_plus_)_goal 계산




    // (first, second, third_plus_)_goal_conceded 계산

  }


  private function findEventEndingPattern(&$_eventParamSet)
  {
    $_sequence = $_eventParamSet['sequence'];
    $_currentI = $_eventParamSet['i'];
    $preEvent = $this->getEventEndingByI($_sequence, $_currentI - 1);
    $currentEvent = $this->getEventEndingByI($_sequence, $_currentI);
    $ending = $_sequence['ending'];

    if (in_array($currentEvent, $this->firstEventsOfPattern)) {
      if (in_array(
        $currentEvent,
        [
          SimulationEventType::FOUL,
          SimulationEventType::FOUL_PK,
          SimulationEventType::FOUL_Y_PK,
          SimulationEventType::FOUL_R_PK,
        ]
      )) {
        // logger("FOUL");
        $this->applyRatingConfig(true, $_eventParamSet, $currentEvent);
        return;
      }

      if (!isset($_sequence['step' . $_currentI + 1])) {  // pk, shot ~ ending
        // logger('event ending pattern->');
        // logger(Str::upper($currentEvent) . '-' . Str::upper($ending));
        $this->applyRatingConfig(false, $_eventParamSet, $currentEvent, $ending);
        return;
      }
      // ->>>>>>>>>>>>>>>>>>>>>>>>>>>>
      $nextEvent = $this->getEventEndingByI($_sequence, $_currentI + 1);

      if ($currentEvent === SimulationEventType::SHOT && $nextEvent === SimulationEventType::CORNERKICK) { // shot
        // logger("SHOT_CORNERKICK");
        $this->applyRatingConfig(true, $_eventParamSet, $currentEvent, $nextEvent);
        return;
      }

      if (!isset($_sequence['step' . $_currentI + 2])) {
        if (Str::startsWith($currentEvent, 'foul')) { // foul_* none ~ ending
          // logger(Str::upper($currentEvent) . '-' . Str::upper($nextEvent) . '-' . Str::upper($ending));
          $this->applyRatingConfig(true, $_eventParamSet, $currentEvent, $nextEvent);
          return;
        }

        logger('처리되지 않은 event event ending (new)pattern->');
        logger($currentEvent . '->' . $nextEvent . '->' . $ending);
        return;
      }
      // ->>>>>>>>>>>>>>>>>>>>>>>>>>>>
      if (Str::startsWith($currentEvent, 'foul')) { // foul_* none event
        // logger(Str::upper($currentEvent) . '-' . Str::upper($nextEvent) . '-' . Str::upper($eventAfternextEvent));
        $this->applyRatingConfig(true, $_eventParamSet, $currentEvent, $nextEvent);
        return;
      }

      $eventAfternextEvent = $this->getEventEndingByI($_sequence, $_currentI + 2);

      logger('처리되지 않은 event event event (new)pattern->');
      logger($currentEvent . '->' . $nextEvent . '->' . $eventAfternextEvent);
      return;
      // 패턴 체크
    } else if ($currentEvent === 'none') {
      if (Str::startsWith($preEvent, 'foul_') && !isset($_sequence['step' . $_currentI + 1])) {
        $this->applyRatingConfig(false, $_eventParamSet, $currentEvent, $ending);
        return;
      }
    }
  }

  public function makeExtraMinutes(&$_activeLineups, $_playingSeconds, $_ending)
  {
    if ($_ending === SimulationEndingType::FIRST_HALF_END) {
      $_activeLineups['first_extra_minutes'] = (int)($_playingSeconds / 60) - 45;
    } else if ($_ending === SimulationEndingType::SECOND_HALF_END) {
      $_activeLineups['second_extra_minutes'] = (int)($_playingSeconds / 60) - 90;
    }
  }
}
