<?php

namespace App\Console\Commands\DataControll\Dev;

use App\Enums\Opta\Player\PlayerSubPosition;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Models\game\GameLineup;
use App\Models\game\GameSchedule;
use App\Models\game\PlateCard;
use App\Models\log\ScheduleStatusChangeLog;
use App\Models\meta\RefPlayerOverallHistory;
use App\Models\simulation\SimulationOverall;
use App\Models\simulation\SimulationSchedule;
use App\Models\simulation\SimulationUserLeague;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DB;
use Throwable;

class EtcDBDataUpdator
{
  protected $feedNick;

  public function __construct()
  {
    $this->feedNick = 'EDDU';
  }

  public function scheduleChagneCountUpdate()
  {
    DB::beginTransaction();
    try {
      ScheduleStatusChangeLog::all()
        ->groupBy('schedule_id')
        ->sortBy('id')
        ->map(function ($item) {
          $idx = 0;
          $item->map(function ($info) use (&$idx) {
            $updateFlag = true;
            if ($info->old_status === $info->new_status && $info->old_started_at != $info->new_started_at) {
              $updateFlag = false;
            }

            if ($info->old_status === ScheduleStatus::PLAYED && $info->new_stats === ScheduleStatus::AWARDED) {
              $updateFlag = false;
            }

            if (($info->old_status === ScheduleStatus::FIXTURE && $info->new_status === ScheduleStatus::PLAYING) || ($info->old_status === ScheduleStatus::FIXTURE && $info->new_status === ScheduleStatus::PLAYED)  || ($info->old_status === ScheduleStatus::PLAYING && $info->new_status === ScheduleStatus::PLAYED)) {
              $updateFlag = false;
            }

            if ($updateFlag) {
              $idx++;
            }
            if ($idx > 0) {
              $info->index_changed = $idx;
              $info->save();
            }
            if ($updateFlag) {
              logger($info->toArray());
            }
          });
        });
      DB::commit();
    } catch (Throwable $th) {
      DB::rollBack();
      dd($th);
    }
  }

  public function gameLineupScheduleIdUpdate()
  {
    GameLineup::whereHas('gameJoin')
      ->whereHas('userPlateCard.plateCard')
      ->whereNull('schedule_id')
      ->get()
      ->map(function ($info) {
        GameSchedule::where('game_id', $info->gameJoin->game_id)
          ->get()->map(function ($schedule) use ($info) {
            $userCardTeam = $info->userPlateCard->plateCard->team_id;
            if ($schedule->gamePossibleSchedule->schedule->home_team_id ===  $userCardTeam || $schedule->gamePossibleSchedule->schedule->away_team_id === $userCardTeam) {
              GameLineup::where('id', $info->id)->first()->update(['schedule_id' => $schedule->schedule_id]);
            }
          });
      });
  }

  public function playerOverallInsert()
  {
    $keeper = ['passing', 'long_pass', 'saves', 'high_claims', 'sweeper', 'punches'];
    $not = ['saves', 'high_claims', 'sweeper', 'punches'];

    $baseArr['keeper'] = $baseArr['not'] = [
      'season_id' => '',
      'player_id' => ''
    ];
    foreach (config('fantasyoverall.column') as $stat => $category) {
      if ($category === PlayerPosition::GOALKEEPER) {
        if (in_array($stat, $keeper)) {
          $baseArr['keeper'][$stat] = 45;
        }
      } else {
        if (!in_array($stat, $not)) {
          $baseArr['not'][$stat] = 45;
        }
      }
    }

    DB::beginTransaction();
    // 판매되는 plateCard 중에서 overall 들어있지 않은 player
    PlateCard::doesntHave('refPlayerOverall')->isOnSale()
      ->where('season_id', '1jt5mxgn4q5r6mknmlqv5qjh0')
      ->get()
      ->map(function ($info) use ($baseArr) {
        if ($info->position === PlayerPosition::GOALKEEPER) {
          $data = $baseArr['keeper'];
        } else {
          $data = $baseArr['not'];
        }

        $data['season_id'] = $info->season_id;
        $data['player_id'] = $info->player_id;

        RefPlayerOverallHistory::create($data);
      });

    DB::commit();
  }

  public function finalOverallAllPositionUpdate()
  {
    $updateArr = [];
    SimulationOverall::with([
      'draftSelection.schedule.oneOptaPlayerDailyStat',
      'userPlateCard',
      'refPlayerOverall'
    ])->has('refPlayerOverall')
      ->get()
      ->map(function ($info) use (&$updateArr) {
        foreach (config('fantasyoverall.final') as $position => $stats) {
          $overall = 0;
          foreach ($stats as $stat => $coefficient) {
            try {
              $overall = BigDecimal::of($overall)->plus(BigDecimal::of($info->{$stat} + $info->refPlayerOverall->{$stat})->multipliedBy(BigDecimal::of($coefficient), 1, RoundingMode::HALF_UP));
            } catch (\Exception $e) {
              dd($info);
            }
          }
          $overall = $overall->toScale(0, RoundingMode::HALF_UP);
          $updateArr[$info->id][$position] = $overall;
        }
        SimulationOverall::updateOrCreateEx(
          [
            'id' => $info->id
          ],
          ['final_overall' => $updateArr[$info->id]]
        );
      });
  }

  public function finalOverallMyPositionUpdate()
  {
    RefPlayerOverallHistory::whereNotNull('sub_position')
      ->get()
      ->map(function ($info) {
        $overall = 0;
        foreach (config('fantasyoverall.final')[$info->sub_position] as $stat => $coefficient) {
          try {
            if (!is_null($info->{$stat})) {
              $overall = BigDecimal::of($overall)->plus(BigDecimal::of($info->{$stat})->multipliedBy(BigDecimal::of($coefficient), 1, RoundingMode::HALF_UP));
            }
          } catch (\Exception $e) {
            dd($info->{$stat}, $e->getMessage());
          }
        }
        $overall = $overall->toScale(0, RoundingMode::HALF_UP);

        RefPlayerOverallHistory::updateOrCreateEx(
          [
            'id' => $info->id
          ],
          ['final_overall' => $overall]
        );
      });
  }

  public function draftSubPositionStartingUpdate()
  {
    // userPlatecardId로 draftSelection->schedule 에서 formation_used 찾고
    // optaPlayerDailyStat 에서 place_index 찾아서 config 에서 세부포지션 찾기
    SimulationOverall::with(['userPlateCard' => function ($query) {
      $query->withoutGlobalScope('excludeBurned');
    }])
      ->whereNull('sub_position')
      ->withWhereHas('draftSelection.schedule.optaPlayerDailyStat', function ($query) {
        $query->where('formation_place', '>', 0);
      })->has('refPlayerOverall')
      ->get()
      ->map(function ($info) {
        try {
          // formaion_used 찾기
          if ($info->draftSelection->schedule->home_team_id === $info->userPlateCard->draft_team_id) {
            $formation_used = $info->draftSelection->schedule->home_formation_used;
          } else if ($info->draftSelection->schedule->away_team_id === $info->userPlateCard->draft_team_id) {
            $formation_used = $info->draftSelection->schedule->away_formation_used;
          }

          $stat = $info->draftSelection->schedule->optaPlayerDailyStat->where('player_id', $info->player_id)->first()?->toArray();

          if (!is_null($stat)) {
            //place_index 찾기
            $subPosition = config('formation-by-sub-position.formation_used')[$formation_used][$stat['formation_place']];

            SimulationOverall::updateOrCreateEx(
              [
                'id' => $info->id
              ],
              ['sub_position' => $subPosition]
            );
          }
        } catch (\Exception $e) {
          dd($info->id, $e);
        }
      });
  }

  public function draftSubPositionSubstituteUpdate()
  {
    // userPlatecardId로 draftSelection->schedule 에서 formation_used 찾고
    // optaPlayerDailyStat 에서 place_index 찾아서 config 에서 세부포지션 찾기
    SimulationOverall::with('plateCard')
      ->has('refPlayerOverall')
      ->whereNull('sub_position')
      ->get()
      ->map(function ($info) {
        try {
          $subPosition = '';
          if ($info->plateCard->position === PlayerPosition::GOALKEEPER) {
            $subPosition = PlayerSubPosition::GK;
          } else {
            $arr = PlayerSubPosition::getValues();
            $subPosition = $arr[rand(1, count($arr) - 1)];
          }

          SimulationOverall::updateOrCreateEx(
            [
              'id' => $info->id
            ],
            ['sub_position' => $subPosition]
          );
        } catch (\Exception $e) {
          dd($info->id, $e->getMessage());
        }
      });
  }

  public function playerSubPositionUpdate()
  {
    RefPlayerOverallHistory::whereNull('sub_position')
      ->with(['refPlayerCurrentMeta', 'season:id,name', 'plateCard'])
      ->get()
      ->map(function ($info) {
        $subPosition = null;
        if (!is_null($info->refPlayerCurrentMeta)) {
          foreach ($info->refPlayerCurrentMeta as $currentMeta) {
            if ($info->season->name === $currentMeta->target_season_name) {
              $formationAggr = $currentMeta->formation_aggr;
              if (!is_null($formationAggr)) {
                unset($formationAggr['total']);
                arsort($formationAggr);
                $subPosition = key($formationAggr);
              }
            }
          }
        }

        if (is_null($subPosition)) {
          if ($info->plateCard->position === PlayerPosition::GOALKEEPER) {
            $subPosition = PlayerSubPosition::GK;
          } else {
            $arr = PlayerSubPosition::getValues();
            $subPosition = $arr[rand(1, count($arr) - 1)];
          }
        }

        RefPlayerOverallHistory::updateOrCreateEx(
          [
            'id' => $info->id
          ],
          ['sub_position' => $subPosition]
        );
      });
  }

  public function userLeagueUpdate()
  {
    // 루키 전용
    $update = [];
    SimulationUserLeague::whereNull('league_id')
      ->get()
      ->map(function ($info) use (&$update) {
        $schedule = SimulationSchedule::where(function ($query) use ($info) {
          $query->where('home_applicant_id', $info->applicant_id)
            ->orWhere('away_applicant_id', $info->applicant_id);
        })
          ->first();

        $update[$info->applicant_id]['league_id'] = $schedule->league_id;
        $update[$info->applicant_id]['season_id'] = $schedule->season_id;
      });

    if (!is_null($update)) {
      foreach ($update as $userId => $info) {
        $userLeague = SimulationUserLeague::where('applicant_id', $userId)->first();
        $userLeague->league_id = $info['league_id'];
        $userLeague->season_id = $info['season_id'];
        $userLeague->save();
      }
    }
  }

  public function updateCategoryOverall($userPlateCardId)
  {
    $update = [];
    $overallInfo = SimulationOverall::where('user_plate_card_id', $userPlateCardId)->first();

    if (!is_null($overallInfo)) {
      $columns = config('fantasyoverall.column');
      $categoryCntArr = array_count_values($columns);
      foreach ($columns as $column => $category) {
        if (!isset($total[$category]['total'])) $total[$category]['total'] = 0;
        $total[$category]['total'] += $overallInfo->{$column}['overall'];
        $update[$category]['avg'] = BigDecimal::of($total[$category]['total'])->dividedBy(BigDecimal::of($categoryCntArr[$category]), 0, RoundingMode::HALF_UP)->toInt();
      }
    }

    if (count($update) > 0) {
      $overallInfo->attacking = $update['attacking']['avg'];
      $overallInfo->passing2 = $update['passing']['avg'];
      $overallInfo->defensive = $update['defensive']['avg'];
      $overallInfo->duels = $update['duel']['avg'];
      $overallInfo->goalkeeping = $update['goalkeeping']['avg'];
      $overallInfo->physical = $update['physical']['avg'];

      $overallInfo->save();
    }
  }
}
