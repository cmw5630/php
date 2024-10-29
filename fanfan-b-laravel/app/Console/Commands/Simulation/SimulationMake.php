<?php

namespace App\Console\Commands\Simulation;

use App\Enums\Simulation\ScheduleWinnerStatus as SimulationScheduleWinnerStatus;
use App\Enums\Simulation\SimulationEndingType;
use App\Enums\Simulation\SimulationEventType;
use App\Enums\Simulation\SimulationTeamSide;
use App\Enums\SimulationCalculator\SimulationCalculatorType;
use App\Enums\System\NotifyLevel;
use App\Libraries\Classes\simulation\EndingHandler;
use App\Libraries\Classes\simulation\SubstituteEventHandler;
use App\Libraries\Classes\SimulationCalculator;
use App\Models\simulation\RefSimulationScenario;
use App\Models\simulation\SimulationLineup;
use App\Models\simulation\SimulationLineupMeta;
use App\Models\simulation\SimulationRefCardValidation;
use App\Models\simulation\SimulationSchedule;
use App\Models\simulation\SimulationSequenceMeta;
use App\Models\simulation\SimulationStep;
use App\Models\simulation\SimulationUserLineupMeta;
use DB;
use Exception;
use Illuminate\Console\Command;
use Throwable;

class SimulationMake extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'simulation:make {--mode=}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Command description';

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct()
  {
    parent::__construct();
  }


  private function getBannedScheduleIds($_applicantId)
  {
    $a = SimulationUserLineupMeta::where('applicant_id', $_applicantId)
      ->with([
        'applicant.nextHomeSchedule' => function ($query) {
          $query->limit(2);
        },
        'applicant.nextAwaySchedule' => function ($query) {
          $query->limit(2);
        }
      ])->first();

    $b = __sortByKeys(array_merge(
      $a->nextAwaySchedule->toArray(),
      $a->nextHomeSchedule->toArray()
    ), ['keys' => ['started_at'], 'hows' => ['asc']]);

    return [$b[0]['id'] ?? null, $b[1]['id'] ?? null];
  }


  private function applyYRCards($_userPlateCardId, $_applicantId, $_yCount, $_rCount)
  {
    $bannedScheduleIds = [];
    $refCardV = SimulationRefCardValidation::where('user_plate_card_id', $_userPlateCardId)
      ->first();

    if ($refCardV === null) {
      $refCardV = (new SimulationRefCardValidation);
      $refCardV->user_plate_card_id = $_userPlateCardId;
    }
    $beforeYellowCardCount = $refCardV->yellow_card_count ?? 0;
    $beforeRedCardCount = $refCardV->red_card_count ?? 0;
    $nextYellowCardCount = $beforeYellowCardCount + $_yCount;
    $nextRedCardCount = $beforeRedCardCount + $_rCount;

    if ($nextYellowCardCount >= 5 || $nextRedCardCount >= 1) {
      $bannedScheduleIds = $this->getBannedScheduleIds($_applicantId);
    }

    $refCardV->banned_schedules = $bannedScheduleIds;
    $refCardV->$refCardV->yellow_card_count = $nextYellowCardCount;
    $refCardV->red_card_count = $nextRedCardCount;
    $refCardV->save();
  }


  private function updateLineup($_scheduleId,  $_activeLineups)
  {
    $newLineups = [];
    foreach (['home' => $_activeLineups['home'], 'away' => $_activeLineups['away']] as $teamSide => $lineupSet) {
      /**
       * 임시코드
       * rating, mom 계산
       */
      $maxRatingUserPlateCardId = null;
      $maxRating = -987;
      $maxTeamSide = null;
      $lineups = array_merge($lineupSet['players'], $lineupSet['substitutions']);



      $oneContents = [];
      foreach ($lineups as $idx => $contents) {
        $oneContents['stamina'] = $contents['stamina'];
        $oneContents['goal'] = $contents['goal'] ?? 0;
        $oneContents['assist'] = $contents['assist'] ?? 0;
        $oneContents['save'] = $contents['save'] ?? 0;
        $oneContents['key_pass'] = $contents['key_pass'] ?? 0;
        $oneContents['is_changed'] = $contents['is_changed'];
        $oneContents['yellow_card_count'] = $contents['yellow_card_count'] ?? 0;
        $oneContents['red_card_count'] = $contents['red_card_count'] ?? 0;
        $userPlateCardId = $contents['user_plate_card_id'];

        $v = (mt_rand() / mt_getrandmax()) * 6 + 4; // random rating
        $oneContents['rating'] = $v;
        if ($maxRating < $v) {
          $maxRatingUserPlateCardId = $userPlateCardId;
          $maxRating = $v;
          $maxTeamSide = $teamSide;
        }
        $newLineups[$teamSide][$userPlateCardId] = $oneContents;
      }
    }
    $newLineups[$maxTeamSide][$maxRatingUserPlateCardId]['is_mom'] = true;

    foreach ($newLineups as $teamSide => $lineupSet) {
      $a = SimulationLineupMeta::where([
        'schedule_id' => $_scheduleId,
        'team_side' => $teamSide,
      ])->with('lineup')->get()->map(
        function ($item) use ($lineupSet) {
          foreach ($item->lineup as $oneLineup) {
            $oneLineup->update($lineupSet[$oneLineup['user_plate_card_id']]);
          }
          $item->push();
        }
      );
      $a->push();
    }
  }

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    $mode = $this->options()['mode'] ?? 'default';

    /** 
     *  @var SimulationCalculator $simulationCalculator
     */
    $simulationCalculator = app(SimulationCalculatorType::SIMULATION);

    DB::beginTransaction();
    try {
      SimulationSchedule::whereColumn('home_applicant_id', '!=', 'away_applicant_id') // 임시 조취
        ->when($mode === 'default', function ($query) {
          $query->currentUserLineupLocked()->limit(5);
        })->when($mode === 'gameover', function ($query) {
          $query->gameOverNotReady()->limit(3);
        })->when($mode === 'test', function ($query) {
          logger('======TEST MODE======START!');
          $query->whereIn(
            'id',
            [
              'sc926d03c08b8f4d15b0a9930ebaf55e60', // 케빈 테스트
              'sc3b447fa458ba4e0e8b5a0d5646066c20', // 시온 테스트
              'sc2503bee19a994e429d50a8a9035f11d5', //
            ]
          );
        })
        // ->doesntHave('sequenceMeta')
        ->doesntHave('lineupMeta')
        ->with([
          // 'lineupMeta',
          'home.userLineupMeta',
          'away.userLineupMeta',
          // 'homeUserLineupMeta.applicant',
          // 'awayUserLineupMeta.applicant',
          'home.userLineupMeta.userLineup.userPlateCard' => function ($query) {
            $query->withoutGlobalScopes()->with([
              'plateCardWithTrashed:id,match_name',
              'simulationOverall:user_plate_card_id,attacking_overall,shot,finishing,dribbles,positioning,goalkeeping_overall,saves,high_claims,sweeper,punches,final_overall',
            ]);
          },
          'away.userLineupMeta.userLineup.userPlateCard' => function ($query) {
            $query->withoutGlobalScopes()->with([
              'plateCardWithTrashed:id,match_name',
              'simulationOverall:user_plate_card_id,attacking_overall,shot,finishing,dribbles,positioning,goalkeeping_overall,saves,high_claims,sweeper,punches,final_overall',
            ]);
          },
        ])->lockForUpdate()->get()
        ->map(function ($scheduleItem) use ($simulationCalculator) {
          $homeSubstitutionCount = 0; // 지바
          $awaySubstitutionCount = 0; // 지바

          $scores = $simulationCalculator->getWDL($scheduleId = $scheduleItem->id);
          $homeScore = $scores['home'];
          $awayScore = $scores['away'];
          $homeExpectedScore = $scores['home_expected_score']; // 지바
          $awayExpectedScore = $scores['away_expected_score']; // 지바

          foreach (SimulationTeamSide::getvalues() as $teamSide) {
            ${$teamSide . 'UserLineupMeta'} = $scheduleItem->toArray()[$teamSide]['user_lineup_meta'];
            $lineupMetaInst = new SimulationLineupMeta();
            $lineupMetaInst->applicant_id = $scheduleItem->{$teamSide . '_applicant_id'};
            // $lineupMetaInst->applicant_id = ${$teamSide . 'UserLineupMeta'}['applicant_id'];
            $lineupMetaInst->schedule_id = $scheduleItem->id;
            $lineupMetaInst->substitution_count = ${$teamSide . 'UserLineupMeta'}['substitution_count'];
            $lineupMetaInst->formation_used = ${$teamSide . 'UserLineupMeta'}['formation_used'];
            $lineupMetaInst->attack_power = ${$teamSide . 'UserLineupMeta'}['attack_power'];
            $lineupMetaInst->defence_power = ${$teamSide . 'UserLineupMeta'}['defence_power'];
            $lineupMetaInst->expected_score = ${$teamSide . 'ExpectedScore'};
            $lineupMetaInst->team_side = $teamSide;
            $lineupMetaInst->score = ${$teamSide . 'Score'};
            $lineupMetaInst->save();
            $lineupMetaInst->refresh();

            $lineupMetaInstId = $lineupMetaInst->id;

            ${$teamSide . 'SubstitutionCount'} += ${$teamSide . 'UserLineupMeta'}['substitution_count'];
            foreach (${$teamSide . 'UserLineupMeta'}['user_lineup'] as $lineup) {
              $lineupInst = new SimulationLineup();
              $lineupInst->lineup_meta_id = $lineupMetaInstId;
              foreach ($lineup as $columnName => $value) {
                if ($columnName === 'id' || $columnName === 'user_lineup_meta_id' || $columnName === 'user_plate_card') continue;
                $lineupInst->{$columnName} = $value;
              }
              $lineupInst->save();
            }
          }
          $winner = SimulationScheduleWinnerStatus::DRAW;
          if ($homeScore < $awayScore) {
            $winner = SimulationScheduleWinnerStatus::AWAY;
          } else if ($homeScore > $awayScore) {
            $winner = SimulationScheduleWinnerStatus::HOME;
          }
          $scheduleItem->winner = $winner;
          $scheduleItem->is_next_lineup_ready = true;
          $scheduleItem->save();
          $scheduleItem->refresh();

          $activeLineups = $simulationCalculator->getLineupFormatForCal(
            $homeUserLineupMeta['formation_used'],
            $homeUserLineupMeta['user_lineup'],
            $awayUserLineupMeta['formation_used'],
            $awayUserLineupMeta['user_lineup'],
            $scheduleItem->home->toArray(),
            $scheduleItem->away->toArray(),
            // $homeUserLineupMeta['applicant'],
            // $awayUserLineupMeta['applicant'],
          );
          $activeLineups['game_result'] = $homeScore . ':' . $awayScore;
          $substitutionTimes = $simulationCalculator->getSubstitutionTimeRange($homeSubstitutionCount, $awaySubstitutionCount);

          // -> 라인업 적용한 계산 추가 필요!

          foreach ($scheduleItem->lineupMeta->toArray() as $teamSet) {
            ${$teamSet['team_side'] . 'FormationUsed'} = $teamSet['formation_used'];
          }

          $timeSum = 0;
          $refSSInst = RefSimulationScenario::where([
            'home_score' => $homeScore,
            'away_score' => $awayScore,
          ])->with('refSimulationSequence');

          logger($scheduleItem->id . ' make simulation start! with scenario id:' . $refSSInst->clone()->first()->id);

          if (!($refSSInst->clone()->exists())) {
            logger(sprintf('home(%s):away(%s) scenario is not exists!', $homeScore, $awayScore));
            __telegramNotify(NotifyLevel::CRITICAL, 'scenario is not exists', sprintf('home(%s):away(%s) scenario is not exists!', $homeScore, $awayScore));
          } else {
            $refSSInst
              ->inRandomOrder()
              ->limit(1)
              ->get()
              ->map(function ($scenarioItem)
              use (
                $scheduleId,
                &$timeSum,
                &$scheduleItem,
                $simulationCalculator,
                &$activeLineups,
                $substitutionTimes,

                /** 지우면 바보 */
                $homeFormationUsed,
                /** 지우면 바보 */
                $awayFormationUsed,
                $winner,
              ) {
                $lastCoordsTrans = null;
                $playingSeconds = 0;
                $homeGoal = 0;
                $awayGoal = 0;
                foreach ($scenarioItem->refSimulationSequence->toArray() as $sequence) {
                  $sequenceBabo = [];
                  $nthHalf = $sequence['nth_half'];
                  $sequenceBabo['ending'] = $sequence['ending'];
                  $sequenceBabo['attack_direction'] = $sequence['attack_direction'];
                  $sequenceBabo['ref_sequence_id'] = $sequence['id'];
                  // schedule_id 필요!
                  $sequenceBabo['schedule_id'] = $scheduleId;
                  $sequenceBabo['time_taken'] = 0;
                  $timeTaken = 0;

                  $i = 0;
                  $stepBabo = [];
                  // $beforeFromationPlace = null;

                  /**
                   * 스태미너 정보 sequenceMeta에 json으로 추가
                   */
                  $sequenceBabo['sequence_events'] = $simulationCalculator->getStaminas($activeLineups);
                  // $simulationCalculator->aggreSave($activeLineups, $sequence);
                  while (null !== ($coordsOrigin = $sequence['step' . $i])) {
                    $stepBabo[$i]['home_goal'] = $stepBabo[$i - 1]['home_goal'] ?? $homeGoal;
                    $stepBabo[$i]['away_goal'] = $stepBabo[$i - 1]['away_goal'] ?? $awayGoal;
                    $isLastStep = isset($sequence['step' . $i + 1]) ? true : false;
                    // playingSeconds 계산 
                    $stepBabo[$i]['playing_seconds'] = $playingSeconds = $simulationCalculator->getPlayingSeconds($playingSeconds);
                    // sequence 순서
                    $stepBabo[$i]['seq_no'] = $sequence['seq'];
                    // 이벤트
                    $stepBabo[$i]['event'] = $sequence['event_split']['event'][$i] ?? null;
                    // highlight 여부
                    $stepBabo[$i]['is_highlight'] = in_array($i, ($sequence['highlight_check'] ?? []));
                    // // 포지션 계산
                    // $stepBabo[$i]['formation_place'] = $currentFormationPlace = $simulationCalculator->getRandomFormationPlace($activeLineups, $sequenceBabo['attack_direction'], $coords, ${$sequence['attack_direction']  . 'FormationUsed'}, $stepBabo[$i]['event'], $beforeFromationPlace);
                    // origin 좌표
                    $stepBabo[$i]['coords'] = $coordsOrigin;
                    // 시간계산 
                    $timeTaken += $stepBabo[$i]['tick'] = $simulationCalculator->getTimeTaken($stepBabo[$i]['event'], $sequence['ending'], $stepBabo[$i]['is_highlight']);
                    // commenary
                    $simulationCalculator->solveEventSplit($sequence, $stepBabo, $i, $activeLineups, $homeFormationUsed, $awayFormationUsed, $isLastStep);
                    // $simulationCalculator->aggreGoalAssist($activeLineups, $sequence, $i, $stepBabo, $beforeFromationPlace, $currentFormationPlace, $homeGoal, $awayGoal);
                    $stepBabo[$i]['coords'] = $lastCoordsTrans = json_encode($simulationCalculator->correctCoords($nthHalf, $sequenceBabo['attack_direction'], $coordsOrigin));
                    $i++;
                  }



                  // if (
                  //   $sequence['ending'] === SimulationEndingType::FIRST_HALF_START ||
                  //   $sequence['ending'] === SimulationEndingType::FIRST_HALF_END ||
                  //   $sequence['ending'] === SimulationEndingType::SECOND_HALF_START ||
                  //   $sequence['ending'] === SimulationEndingType::SECOND_HALF_END
                  // ) {
                  //   $simulationCalculator->makeExtraMinutes($activeLineups, $playingSeconds, $sequence['ending']);
                  //   $stepBabo[$i]['playing_seconds'] = $playingSeconds = $simulationCalculator->getPlayingSeconds($playingSeconds, $sequence['ending']);
                  //   $stepBabo[$i]['home_goal'] = $homeGoal;
                  //   $stepBabo[$i]['away_goal'] = $awayGoal;
                  //   if (
                  //     $sequence['ending'] === SimulationEndingType::FIRST_HALF_START ||
                  //     $sequence['ending'] === SimulationEndingType::SECOND_HALF_START
                  //   ) {
                  //     $stepBabo[$i]['coords'] = json_encode([4, 3]);
                  //   } else {
                  //     $stepBabo[$i]['coords'] = $lastCoordsTrans;
                  //   }
                  //   $stepBabo[$i]['seq_no'] = $sequence['seq'];
                  //   $stepBabo[$i]['event'] = null;
                  //   $timeTaken += $stepBabo[$i]['tick'] = $simulationCalculator->getTimeTaken(null, $sequence['ending'], 0);
                  //   (new EndingHandler)->handle(
                  //     $sequence['ending'],
                  //     $stepBabo,
                  //     $i,
                  //     $activeLineups,
                  //     $sequence['attack_direction']
                  //   );
                  //   // $simulationCalculator->makeEndingCommentary(
                  //   //   $stepBabo,
                  //   //   $i,
                  //   //   $activeLineups,
                  //   //   $sequence['ending']
                  //   // );
                  //   $i++;
                  // }

                  /**
                   * 스태미너 계산
                   */
                  $simulationCalculator->calStamina($activeLineups, $sequence['ending']);

                  /**
                   * 교체 선수 계산 후 교체선수 있으면 
                   */
                  $inOut =  $simulationCalculator->getPlayerInOut($activeLineups, $sequence['ending'], $substitutionTimes, $playingSeconds, $nthHalf, $scheduleId);
                  if (!empty($inOut) && $i != 0) {
                    $stepBabo[$i] = $stepBabo[$i - 1];
                    unset($stepBabo[$i]['commentary_template_id']);
                    // unset($stepBabo[$i]['coords']);
                    unset($stepBabo[$i]['formation_place']);
                    $stepBabo[$i]['event'] = SimulationEventType::SUBSTITUTE;
                    $stepBabo[$i]['ref_infos_temp'] = $inOut;
                    $stepBabo[$i]['is_highlight'] = 0;
                    $timeTaken += $stepBabo[$i]['tick'] = $simulationCalculator->getTimeTaken(SimulationEventType::SUBSTITUTE, null, 0);
                    (new SubstituteEventHandler)->handle(
                      $stepBabo,
                      $i,
                      $activeLineups,
                    );
                    // $simulationCalculator->processSubstituteEvent(
                    //   $stepBabo,
                    //   $i,
                    //   $activeLineups,
                    // );
                    $i++;
                  }

                  // sequence 종료 flag 추가
                  try {
                    // =>
                    $simulationCalculator->makeExtraMinutes($activeLineups, $playingSeconds, $sequence['ending']);
                    $stepBabo[$i]['playing_seconds'] = $playingSeconds = $simulationCalculator->getPlayingSeconds($playingSeconds, $sequence['ending']);
                    $stepBabo[$i]['home_goal'] = $homeGoal;
                    $stepBabo[$i]['away_goal'] = $awayGoal;
                    if (
                      $sequence['ending'] === SimulationEndingType::FIRST_HALF_START ||
                      $sequence['ending'] === SimulationEndingType::SECOND_HALF_START
                    ) {
                      $stepBabo[$i]['coords'] = json_encode([4, 3]);
                    } else {
                      $stepBabo[$i]['coords'] = $lastCoordsTrans;
                    }
                    $stepBabo[$i]['seq_no'] = $sequence['seq'];
                    $stepBabo[$i]['event'] = null;
                    $timeTaken += $stepBabo[$i]['tick'] = $simulationCalculator->getTimeTaken(null, $sequence['ending'], 0);
                    (new EndingHandler)->handle(
                      $sequence['ending'],
                      $stepBabo,
                      $i,
                      $activeLineups,
                      $sequence['attack_direction'],
                      $homeGoal,
                      $awayGoal,
                    );

                    // $stepBabo[$i] = $stepBabo[$i - 1];
                    // unset($stepBabo[$i]['highlight_overall']);
                    // unset($stepBabo[$i]['commentary_template_id']);
                    // unset($stepBabo[$i]['event']);
                    // unset($stepBabo[$i]['ref_params']);
                    // unset($stepBabo[$i]['is_highlight']);
                    $stepBabo[$i]['is_last_step'] = true;
                    // $stepBabo[$i]['seq_no'] = $stepBabo[$i - 1]['seq_no'];
                    // $stepBabo[$i]['tick'] = 0;
                    $stepBabo[$i]['ending'] = $sequence['ending'];
                    // if ($sequence['ending'] === SimulationEndingType::GOAL) {
                    //   $simulationCalculator->makeEndingCommentary(
                    //     $stepBabo,
                    //     $i,
                    //     $activeLineups,
                    //     $sequence['ending'],
                    //     $sequence['attack_direction'],
                    //   );
                    //   $stepBabo[$i]['event'] = SimulationEventType::GOAL;
                    // $stepBabo[$i]['tick'] = 3;
                    // $stepBabo[$i]['tick'] = 0.8;
                    // } else {
                    // unset($stepBabo[$i]['formation_place']);
                    // }
                  } catch (Throwable $e) {
                    logger($sequence);
                    logger($scenarioItem->id);
                    logger($e);
                  }

                  $sequenceBabo['time_taken'] = $timeTaken;
                  $sequenceBabo['time_sum'] = $timeSum += $sequenceBabo['time_taken'];


                  $id = (SimulationSequenceMeta::create($sequenceBabo))->id;
                  foreach ($stepBabo as $stepRaw) {
                    try {
                      $stepRaw['sequence_meta_id'] = $id;
                      SimulationStep::insert($stepRaw);
                    } catch (Throwable $e) {
                      logger($stepBabo);
                      logger($e);
                      throw $e;
                    }
                  }
                }
                $firstExtraMinutes = $activeLineups['first_extra_minutes'];
                $secondExtraMinutes = $activeLineups['second_extra_minutes'];
                $this->updateLineup($scheduleId, $activeLineups);
                $scheduleItem->is_sim_ready = true;
                $scheduleItem->first_extra_minutes = $firstExtraMinutes;
                $scheduleItem->second_extra_minutes = $secondExtraMinutes;
                $scheduleItem->save();
              });
          }
        });
      if ($mode === 'test') {
        DB::rollBack(); // 지바
        dd('test'); // 지바
      }
      DB::commit();
      logger('simulation make one!');
    } catch (Exception $e) {
      DB::rollBack();
      logger($e);
      logger('simulation rollback(fail)!');
    }
    return 0;
  }
}
