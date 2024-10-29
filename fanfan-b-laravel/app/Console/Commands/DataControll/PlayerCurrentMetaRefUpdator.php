<?php

namespace App\Console\Commands\DataControll;

use App\Enums\Opta\Player\PlayerSubPosition;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Libraries\Traits\PlayerTrait;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\game\PlateCard;
use App\Models\game\PlayerDailyStat;
use App\Models\meta\RefPlayerCurrentMeta;
use Carbon\Carbon;
use DB;
use Exception;
use Throwable;

class PlayerCurrentMetaRefUpdator
{
  use PlayerTrait;
  protected $formations = [
    'total' => 0,
    PlayerSubPosition::ST => 0,
    PlayerSubPosition::LW => 0,
    PlayerSubPosition::AM => 0,
    PlayerSubPosition::RW => 0,
    PlayerSubPosition::LM => 0,
    PlayerSubPosition::CM => 0,
    PlayerSubPosition::RM => 0,
    PlayerSubPosition::LWB => 0,
    PlayerSubPosition::DM => 0,
    PlayerSubPosition::RWB => 0,
    PlayerSubPosition::LB => 0,
    PlayerSubPosition::CB => 0,
    PlayerSubPosition::RB => 0,
    PlayerSubPosition::GK => 0
  ];

  protected $refPCMeatsInitRow = [];
  protected $playerIds = null;
  protected $plateCardMap = [];
  protected $onlyCurrent;

  public function __construct(array|null $_playerIds, bool $_onlyCurrent = true)
  {
    $this->onlyCurrent = $_onlyCurrent;
    $this->playerIds = $_playerIds;
    foreach ((new RefPlayerCurrentMeta)->getTableColumns(true) as $col) {
      if ($col === 'id') continue;
      $this->refPCMeatsInitRow[$col] = null;
    }
    $this->plateCardMap = PlateCard::withoutGlobalScopes()
      ->currentSeason()
      ->withoutTrashed() // withoutGlobalScopes를 하면 softDelete 효과까지 날라가서 붙여줌.
      ->when($this->playerIds !== null, function ($query) {
        $query->whereIn('player_id', $this->playerIds);
      })
      ->select('player_id', 'season_id')
      ->get()
      ->keyBy('player_id')->toArray();
  }

  private function makeFormationAggr(string $playerId): array
  {
    $formations = $this->formations;
    $sub = Schedule::whereHas('optaPlayerDailyStat', function ($query) use ($playerId) {
      $query->where([
        ['player_id', $playerId],
        ['formation_place', '>', 0]
      ]);
    })->select('id', 'home_team_id', 'away_team_id', 'home_formation_used', 'away_formation_used');

    OptaPlayerDailyStat::joinSub($sub, 'sub', function ($join) {
      $tableName = OptaPlayerDailyStat::getModel()->getTable();
      $join->on($tableName . '.schedule_id', 'sub.id');
    })->where('player_id', $playerId)
      ->get()
      ->map(function ($info) use (&$formations) {
        if ($info->team_id === $info->home_team_id) {
          $formationUsed = $info->home_formation_used;
        } else {
          $formationUsed = $info->away_formation_used;
        }

        if (!empty($formationUsed)) {
          if ($info->formation_place > 0) {
            $formations[config('formation-by-sub-position.formation_used')[$formationUsed][$info->formation_place]]++;
          } else {
            $formations[config('formation-by-sub-position.substitution')[$info->sub_position]]++;
          }
          $formations['total']++;
        }
      });
    return $formations;
  }



  private function seasonGradeCnt($_season_id, $_playerId): null|array
  {
    $resultJson = PlayerDailyStat::where([
      'season_id' => $_season_id,
      'player_id' => $_playerId,
      'status' => ScheduleStatus::PLAYED
    ])
      ->whereNotNull('card_grade')
      ->gameParticipantPlayer()
      ->selectRaw('card_grade, COUNT(card_grade) AS cnt')
      ->groupBy('card_grade')
      ->get()
      ->toArray();
    if (empty($resultJson)) {
      return null;
    }
    return $resultJson;
  }

  private function baseUpdate()
  {
    PlateCard::when($this->playerIds === null, function ($query) {
      $query->currentSeason();
    }, function ($query) {
      $query->currentSeason()->whereIn('player_id', $this->playerIds);
    })
      ->get()->map(
        function ($cardInfo) {
          $currentSeasonId = $cardInfo['season_id'];
          $playerId = $cardInfo['player_id'];

          $seasons = Season::whereNot('league_id', config('constant.LEAGUE_CODE.UCL'))
            ->when($this->onlyCurrent, function ($query) {
              $query->currentSeasons();
            })->whereHas('optaPlayerDailyStat', function ($query) use ($playerId) {
              $query->where('player_id', $playerId)->gameParticipantPlayer();
            })->withWhereHas('league', function ($query) {
              $query->withoutGlobalScopes();
            })->get()->toArray();

          $formations = $this->makeFormationAggr($playerId);

          foreach ($seasons as $seasonItem) {
            $oneRow = $this->refPCMeatsInitRow; // 모든 컬럼을 null로 초기화 한후 시작(예를들어 현재 값이 null인데 기존 값이 그대로 남아있으면 안되므로)
            $playerId = $cardInfo['player_id'];
            $targetSeasonId = $seasonItem['id'];
            $lastFiveMatches = $this->playerLastXSchedule($targetSeasonId, $playerId, null, 5);

            $oneRow['player_id'] = $cardInfo['player_id'];
            $oneRow['plate_card_id'] = $cardInfo['id'];
            $oneRow['position'] = $cardInfo['position'];
            $oneRow['season_start_date'] = $seasonItem['start_date'];
            $oneRow['target_season_id'] = $seasonItem['id'];
            $oneRow['target_league_id'] = $seasonItem['league']['id'];
            $oneRow['target_league_code'] = $seasonItem['league']['league_code'];
            $oneRow['target_season_name'] = $seasonItem['name'];

            // grades
            $oneRow['grades'] = $this->seasonGradeCnt($targetSeasonId, $playerId);

            if ($lastFiveMatches->count() > 0) {
              $oneRow['last_5_matches'] = $lastFiveMatches->toArray();
            }

            if ($currentSeasonId === $targetSeasonId) {
              $oneRow['formation_aggr'] = $formations; // 현재 시즌에 대해서만 집계
              $teamId = $cardInfo['team_id'];
              $lastTeamMatch = $this->playerLastXSchedule($targetSeasonId, $playerId, $teamId, 1)->first()?->toArray();
              if ($lastTeamMatch) {
                $oneRow['last_team_match'] = $lastTeamMatch;
                $oneRow['last_season_id'] = $lastTeamMatch['season_id'];
                $oneRow['last_schedule_id'] = $lastTeamMatch['id'];
                $oneRow['last_player_fantasy_point'] = $lastTeamMatch['points']['fantasy_point'];
                $oneRow['last_is_mom'] = $lastTeamMatch['points']['is_mom'];
                foreach (['home', 'away'] as $side) {
                  $oneRow['last_' . $side] = $lastTeamMatch[$side];
                  $lastTeamMatch[$side]['is_player_team'] ? $prefix = 'last_' : $prefix = 'last_vs_';
                  $oneRow[$prefix . 'team_id'] = $lastTeamMatch[$side]['id'];
                  $oneRow[$prefix . 'team_scores'] = $lastTeamMatch['score_' . $side];
                }
              }

              // upcomming
              $nextSchedule = Schedule::with([
                'home:id,code,name,short_name',
                'away:id,code,name,short_name',
                'season:id,name'
              ])
                ->has('home')
                ->has('away')
                ->where(function ($query) use ($teamId) {
                  $query->where('home_team_id', $teamId)
                    ->orWhere('away_team_id', $teamId);
                })
                ->where([
                  ['status', ScheduleStatus::FIXTURE,],
                  ['started_at', '>',  Carbon::now()],
                ])->whereNot('league_id', config('constant.LEAGUE_CODE.UCL'))
                ->select([
                  'id',
                  'home_team_id',
                  'away_team_id',
                  'round',
                  'started_at',
                  'season_id',
                ])
                ->oldest('started_at')
                ->first();
              if ($nextSchedule) {
                foreach (['home', 'away'] as $teamSide) {
                  if ($nextSchedule[$teamSide]['id'] === $teamId) {
                    $nextSchedule[$teamSide]['is_player_team'] = true;
                    $oneRow['upcomming_team_id'] = $nextSchedule[$teamSide]['id'];
                  } else {
                    $nextSchedule[$teamSide]['is_player_team'] = false;
                    $oneRow['upcomming_vs_team_id'] = $nextSchedule[$teamSide]['id'];
                  }
                  $oneRow['upcomming_' . $teamSide] = $nextSchedule->toArray()[$teamSide];
                }
                $oneRow['upcomming_schedule_id'] = $nextSchedule['id'];
                $oneRow['upcomming_started_at'] = $nextSchedule['started_at'];
              }
            }

            RefPlayerCurrentMeta::updateOrCreateEx(
              [
                'player_id' => $oneRow['player_id'],
                'target_season_id' => $oneRow['target_season_id'],
              ],
              $oneRow,
              false,
              true,
            );
          }
        }
      );
  }

  private function extraUpdate()
  {
    // 현재시즌에 대해서, 모든 선수에 대해서 (팀 이적 상관없이 시즌 통합) 
    OptaPlayerDailyStat::when($this->onlyCurrent, function ($query) {
      $query->whereHas('season', function ($query) {
        return $query->whereNot('league_id', config('constant.LEAGUE_CODE.UCL'))->currentSeasons();
      });
    }, function ($query) {
      return $query->whereDoesntHave('season', function ($innerQuery) {
        $innerQuery->where('league_id', config('constant.LEAGUE_CODE.UCL'));
      });
    })
      ->gameParticipantPlayer() // 출전을 한번도 하지 않은 선수는 집계에 포함되지 않으므로 플레이트 카드 선수 수와 집계결과 수가 다를 수 있음(테이블엔 null로 기록됨)
      ->selectRaw(
        'player_id, 
        season_id target_season_id, 
        AVG(fantasy_point) as player_fantasy_point_avg, 
        -- ROW_NUMBER() OVER(PARTITION BY season_id order by AVG(fantasy_point) DESC) as rnk,
        -- count(*) OVER(PARTITION BY season_id) as total_count,
        COUNT(*) as matches,
        AVG(rating) as rating,
        SUM(goals) as goals,
        SUM(goal_assist) as assists,
        SUM(clean_sheet) as clean_sheets,
        SUM(saves) as saves,
        ROW_NUMBER() OVER(PARTITION BY season_id order by AVG(fantasy_point) DESC) / count(*) OVER(PARTITION BY season_id) as fantasy_top_rate'
      )->groupBy(['season_id', 'player_id'])
      ->get()
      ->map(function ($grouped) {
        if (
          ($this->playerIds === null || in_array($grouped->player_id, $this->playerIds)) &&
          (isset($this->plateCardMap[$grouped->player_id])
            // plate 카드로 만들어진 선수들의 실제 현재 시즌(plate 카드 시즌)기록 집계만 필터링(active 시즌 내에서 이적했던 선수는 집계 결과가 여럿일 수 있으므로)
            // $this->plateCardMap[$grouped->player_id]['season_id'] === $grouped['current_season_id'])
          )
        ) {
          $player = $grouped->toArray();
          RefPlayerCurrentMeta::updateOrCreateEx(
            [
              'player_id' => $player['player_id'],
              'target_season_id' => $player['target_season_id'],
            ],
            $player,
            false,
            true,
          );
        }
      });
  }

  private function delOldData()
  {
    RefPlayerCurrentMeta::whereHas('player.PlateCard', function ($query) {
      $query->onlyTrashed()
        ->orWhere(function ($query) {
          $query->currentSeason(false);
        });
    })->forceDelete();
  }


  private function UpdateTable()
  {
    $this->baseUpdate();
    $this->extraUpdate();
    $this->delOldData();
  }


  public function update()
  {
    DB::beginTransaction();
    try {
      logger('start currentMeta update');
      $this->UpdateTable();
      DB::commit();
      logger('currentMeta update 성공');
    } catch (Exception $e) {
      DB::rollBack();
      logger($e);
      logger('update currentMeta 실패(RollBack)');
      throw $e; // 관련된 모두 롤백되어야 함.
    }
  }
}
