<?php

namespace App\Console\Commands\DataControll;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Card\OriginGrade;
use App\Enums\SimulationCalculator\SimulationCategoryType;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\ParserMode;
use App\Libraries\Traits\CommonTrait;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\game\PlateCard;
use App\Models\log\PlateCardPriceChangeLog;
use App\Models\meta\RefPlayerOverallHistory;
use Carbon\Carbon;
use DB;
use Exception;

class PlayerOverallUpdator
{
  use FantasyMetaTrait, CommonTrait;

  protected $feedNick;
  protected $playerId;
  protected $gradeChangeTickMap;

  public function __construct($_playerId = null)
  {
    $this->playerId = $_playerId;
    $this->feedNick = 'POU';
    $this->gradeChangeTickMap = [
      OriginGrade::SS => 4,
      OriginGrade::S => 1,
      OriginGrade::A => 1,
      OriginGrade::B => 1,
      OriginGrade::C => 1,
      OriginGrade::D => 1,
    ];
  }

  public function update()
  {
    $fluctuation = config('fantasyoverall.fluctuation');

    PlateCard::isOnSale()
      ->where('season_id', '9n12waklv005j8r32sfjj2eqc')
      ->withWhereHas('optaPlayerDailyStat', function ($query) {
        $query->whereHas('season', function ($seasonQuery) {
          $seasonQuery->where('id', '9n12waklv005j8r32sfjj2eqc');
          //$seasonQuery->currentSeasons();
        })->has('schedule')
          ->with('schedule');
      })
      ->when($this->playerId, function ($query, $playerId) {
        $query->where('player_id', $playerId);
      })
      ->get()
      ->map(function ($playerInst) use ($fluctuation) {
        try {
          $before = RefPlayerOverallHistory::where([
            ['player_id', $playerInst->player_id],
            ['season_id', '9n12waklv005j8r32sfjj2eqc'],
            ['is_current', true]
          ]);

          $beforeOverallInfo = $before->first();

          if (is_null($beforeOverallInfo)) {
            logger("before overall is null");
            dd($playerInst->player_id);
          }

          $alpha = 0;
          $updateArr = ['is_current' => true];

          if (count($playerInst->optaPlayerDailyStat) > 0) {
            $lastStat = $playerInst->optaPlayerDailyStat->sortByDesc('schedule.started_at')->first()->toArray();
            // $lastStat = $playerInst->optaPlayerDailyStat->sortBy('schedule.started_at')->first();
            // $lastStat = [];
            // $playerInst->optaPlayerDailyStat->map(function ($opds) use (&$lastStat) {
            //   if ($opds->schedule->round === 2) {
            //     $lastStat = $opds;
            //   }
            // });
            if ($lastStat['season_id'] !== '9n12waklv005j8r32sfjj2eqc') {
              logger("wrong season stat");
              dd($playerInst->player_id);
            }

            $changeLog = PlateCardPriceChangeLog::when($this->playerId, function ($query, $playerId) {
              $query->where([
                ['player_id', $playerId],
                ['season_id', '9n12waklv005j8r32sfjj2eqc']
              ]);
            })
              // ->where('is_change_spot', true)
              // ->whereNotNull('schedule_id')
              ->where('schedule_id', $lastStat['schedule_id'])
              // ->whereDate('updated_at', Carbon::today())
              ->orderByDesc('id')
              ->first();

            //dd($playerInst->player_id);
            // 최소 경기시간은 등급과 무관
            if ($lastStat['mins_played'] >= $fluctuation['match']['min_mins']) {
              $standard = $fluctuation['match']['grades'][$changeLog->price_grade];
              if ($standard['rating'][0] <= $lastStat['rating'] && $standard['power_ranking'][0] <= $lastStat['power_ranking']) {
                $alpha = 1;
              } else if ($standard['rating'][1] >= $lastStat['rating'] && $standard['power_ranking'][1] >= $lastStat['power_ranking']) {
                $alpha = -1;
              }
              logger('overallUpdator plus alpha first');
              logger($alpha);
            }

            if ($changeLog->is_change_spot) {
              $beforeLog = PlateCardPriceChangeLog::where('id', '<', $changeLog->id)
                ->where([
                  ['player_id', $changeLog->player_id],
                  ['season_id', '9n12waklv005j8r32sfjj2eqc']
                ])
                ->orderByDesc('id')
                ->first();
              $currentGrade = $playerInst->grade;
              $beforeGrade = $beforeLog->price_grade;

              if (($currentGrade === OriginGrade::SS) || ($currentGrade != $beforeGrade)) {
                $alpha += $this->getGradeFluctuation($playerInst, $beforeGrade, $changeLog->power_ranking_avg);
                logger('overallUpdator plus alpha second');
                logger($playerInst->player_id);
                logger($alpha);
              }
            }

            $updateArr['league_id'] = $beforeOverallInfo->league_id;
            $updateArr['season_id'] = $lastStat['season_id'];
            $updateArr['schedule_id'] = $lastStat['schedule_id'];
            $updateArr['player_id'] = $playerInst->player_id;
            if (!is_null($lastStat['formation_used']) && $lastStat['formation_place'] > 0) {
              $updateArr['sub_position'] = config('formation-by-sub-position.formation_used')[$lastStat['formation_used']][$lastStat['formation_place']];
            }
            // if (!is_null($refPlayerOverall?->final_overall)) {
            $updateArr['final_overall'] = $beforeOverallInfo->final_overall + $alpha;
            foreach (config('fantasyoverall.column') as $column => $category) {
              if ($category === SimulationCategoryType::ATTACKING && $playerInst->position === PlayerPosition::GOALKEEPER) {
                $updateArr[$column] = $beforeOverallInfo->$column;
              } else if ($category === SimulationCategoryType::GOALKEEPING && $playerInst->position !== PlayerPosition::GOALKEEPER) {
                $updateArr[$column] = $beforeOverallInfo->$column;
              } else {
                $updateArr[$column] = $beforeOverallInfo->$column + $alpha;
              }
            }
            // }

            $updateArr['position'] = $beforeOverallInfo->position;
            $updateArr['sub_position'] = $beforeOverallInfo->sub_position;
            $updateArr['second_position'] = $beforeOverallInfo->second_position;
            $updateArr['third_position'] = $beforeOverallInfo->third_position;

            $before->update(['is_current' => false]);
            // RefPlayerOverallHistory::create($updateArr);
            RefPlayerOverallHistory::updateOrCreateEx(
              [
                'player_id' => $updateArr['player_id'],
                'schedule_id' => $updateArr['schedule_id']
              ],
              $updateArr,
              false,
              true,
            );
          } else {
            logger('stat이 없는 애들 >>');
            logger($playerInst->player_id);
          }
          return $playerInst->season_id;
        } catch (Exception $e) {
          logger($playerInst);
          logger($e);
          throw ($e);
          // dd("HERE2");
        }
      });
  }

  private function getGradeFluctuation($playerInfo, $beforeGrade, $prAvg)
  {
    // 
    // $beforeLog = PlateCardPriceChangeLog::where('id', '<', $log->id)
    // ->orderByDesc('id')
    // ->first();
    $gradeArr = OriginGrade::getValues();
    // dd(array_key($gradeArr, 'd'));
    $fluctuation = config('fantasyoverall.fluctuation');
    $currentGrade = $playerInfo->grade;
    // $beforeGrade = $beforeLog->price_grade;

    $gradeStandard = $fluctuation['grade'][$beforeGrade];

    if ((array_search($beforeGrade, $gradeArr) > array_search($currentGrade, $gradeArr)) || $currentGrade === OriginGrade::SS) {
      $standardPR = $gradeStandard['power_ranking'];
      // 평균 파워랭킹

      for ($i = 0; $i < count($standardPR); $i++) {
        if ($standardPR[$i][0] <= $prAvg) {
          return $standardPR[$i][1];
        }
      }
    }

    if ($currentGrade !== OriginGrade::SS) {
      return $gradeStandard['minus'];
    } else {
      return 0;
    }
  }
}
