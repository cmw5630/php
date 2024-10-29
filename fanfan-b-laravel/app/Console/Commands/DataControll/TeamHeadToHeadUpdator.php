<?php

namespace App\Console\Commands\DataControll;

use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Schedule\ScheduleWinnerStatus;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Libraries\Traits\GameTrait;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\data\SeasonTeam;
use App\Models\meta\RefPlayerCurrentMeta;
use App\Models\meta\RefTeamAggregation;
use DB;
use Exception;

class TeamHeadToHeadUpdator
{
  use GameTrait;

  protected $seasonId = null;
  protected $infoByTeam = [];
  protected $teamMap = null;

  public function __construct(string $_seasonId)
  {
    $this->seasonId = $_seasonId;
    $this->infoByTeam = [
      'plus_goals' => 0,
      'minus_goals' => 0,
      'match_count' => 0
    ];
    $this->teamMap = SeasonTeam::where('season_id', $this->seasonId)->get();
  }

  // 현재 종료된 Round
  private function currentRound()
  {
    $maxPlayedRound = Schedule::where([
      ['season_id', $this->seasonId],
      ['status', ScheduleStatus::PLAYED]
    ])->orderByDesc('round')
      ->limit(1)
      ->value('round');

    if (is_null($maxPlayedRound)) {
      $leagueId = Season::whereId($this->seasonId)->value('league_id');
      $this->seasonId = Season::getBeforeFuture([SeasonWhenType::BEFORE], $leagueId)[$leagueId]['before'][0]['id'];
      return 38;
    }
    if ($maxPlayedRound > 38) return 38;

    $currentRoundSchedules = $this->getStatusCount(Schedule::where([
      ['season_id', $this->seasonId],
      ['ga_round', $maxPlayedRound]
    ])->get()->toArray());

    if ($currentRoundSchedules['Fixture'] > 0) {
      return $maxPlayedRound + 1;
    } else {
      return $maxPlayedRound;
    }
  }

  private function baseUpdate()
  {
    $this->teamMap->map(function ($info) {
      $this->infoByTeam[$info->team_id]['win_count'] = 0;
      $this->infoByTeam[$info->team_id]['lose_count'] = 0;
      $this->infoByTeam[$info->team_id]['draw_count'] = 0;
      $this->infoByTeam[$info->team_id]['plus_goals'] = 0;
      $this->infoByTeam[$info->team_id]['minus_goals'] = 0;
      $this->infoByTeam[$info->team_id]['match_count'] = 0;
      $recent5match = [];
      Schedule::where([
        ['season_id', $this->seasonId],
        ['round', '<=', $this->currentRound()],
      ])->where(function ($query) use ($info) {
        $query->where('home_team_id', $info->team_id)
          ->orWhere('away_team_id', $info->team_id);
      })->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
        ->orderByDesc('round')
        ->get()
        ->map(function ($schedule) use ($info, &$recent5match) {
          if ($info->team_id === $schedule->home_team_id) {
            if (count($recent5match) < 5) {
              if ($schedule->winner === ScheduleWinnerStatus::HOME) {
                array_push($recent5match, 'win');
              } else if ($schedule->winner === ScheduleWinnerStatus::AWAY) {
                array_push($recent5match, 'lose');
              } else if ($schedule->winner === ScheduleWinnerStatus::DRAW) {
                array_push($recent5match, 'draw');
              }
            }
            $this->infoByTeam[$info->team_id]['plus_goals'] += $schedule->score_home;
            $this->infoByTeam[$info->team_id]['minus_goals'] += $schedule->score_away;
            $this->infoByTeam[$info->team_id]['match_count']++;
            if ($schedule->winner === ScheduleWinnerStatus::HOME) {
              $this->infoByTeam[$info->team_id]['win_count']++;
            } else if ($schedule->winner === ScheduleWinnerStatus::AWAY) {
              $this->infoByTeam[$info->team_id]['lose_count']++;
            } else if ($schedule->winner === ScheduleWinnerStatus::DRAW) {
              $this->infoByTeam[$info->team_id]['draw_count']++;
            }
          } else if ($info->team_id === $schedule->away_team_id) {
            if (count($recent5match) < 5) {
              if ($schedule->winner === ScheduleWinnerStatus::HOME) {
                array_push($recent5match, 'lose');
              } else if ($schedule->winner === ScheduleWinnerStatus::AWAY) {
                array_push($recent5match, 'win');
              } else if ($schedule->winner === ScheduleWinnerStatus::DRAW) {
                array_push($recent5match, 'draw');
              }
            }
            $this->infoByTeam[$info->team_id]['minus_goals'] += $schedule->score_home;
            $this->infoByTeam[$info->team_id]['plus_goals'] += $schedule->score_away;
            $this->infoByTeam[$info->team_id]['match_count']++;
            if ($schedule->winner === ScheduleWinnerStatus::AWAY) {
              $this->infoByTeam[$info->team_id]['win_count']++;
            } else if ($schedule->winner === ScheduleWinnerStatus::HOME) {
              $this->infoByTeam[$info->team_id]['lose_count']++;
            } else if ($schedule->winner === ScheduleWinnerStatus::DRAW) {
              $this->infoByTeam[$info->team_id]['draw_count']++;
            }
          }
          $this->infoByTeam[$info->team_id]['recent_5_match'] = json_encode($recent5match);
        });

      if ($this->infoByTeam[$info->team_id]['match_count'] > 0) {
        $this->infoByTeam[$info->team_id]['avg_plus_goals'] = (float) $this->infoByTeam[$info->team_id]['plus_goals'] / $this->infoByTeam[$info->team_id]['match_count'];
        $this->infoByTeam[$info->team_id]['avg_minus_goals'] = (float) $this->infoByTeam[$info->team_id]['minus_goals'] / $this->infoByTeam[$info->team_id]['match_count'];
        $this->infoByTeam['plus_goals'] += $this->infoByTeam[$info->team_id]['plus_goals'];
        $this->infoByTeam['minus_goals'] += $this->infoByTeam[$info->team_id]['minus_goals'];
        $this->infoByTeam['match_count'] += $this->infoByTeam[$info->team_id]['match_count'];
      }

      $defaultArr = ['season_id' => $this->seasonId, 'team_id' => $info->team_id, 'max_avg_plus_goals' => 0, 'max_avg_minus_goals' => 0];
      RefTeamAggregation::updateOrCreateEx(
        [
          'team_id' => $info->team_id,
          'season_id' => $this->seasonId
        ],
        array_merge($this->infoByTeam[$info->team_id], $defaultArr),
        false,
        true,
      );
    });
  }

  // 전체 팀에 대한 평균 득실점 계산
  // currentRound가 0인 경우 이전시즌 5경기 승무패 가져오기
  private function extraUpdate()
  {
    $this->teamMap->map(function ($info) {
      $recent5match = [];
      if (is_null($this->currentRound())) {
        Schedule::where('status', ScheduleStatus::PLAYED)
          ->where(function ($query) use ($info) {
            $query->where('home_team_id', $info->team_id)
              ->orWhere('away_team_id', $info->team_id);
          })
          ->orderByDesc('round')
          ->limit(5)
          ->get()
          ->map(function ($schedule) use ($info, &$recent5match) {
            if ($info->team_id === $schedule->home_team_id) {
              if ($schedule->winner === ScheduleWinnerStatus::HOME) {
                array_push($recent5match, 'win');
              } else if ($schedule->winner === ScheduleWinnerStatus::AWAY) {
                array_push($recent5match, 'lose');
              } else {
                array_push($recent5match, 'draw');
              }
            } else if ($info->team_id === $schedule->away_team_id) {
              if ($schedule->winner === ScheduleWinnerStatus::HOME) {
                array_push($recent5match, 'lose');
              } else if ($schedule->winner === ScheduleWinnerStatus::AWAY) {
                array_push($recent5match, 'win');
              } else {
                array_push($recent5match, 'draw');
              }
            }
          });
        $recent5match = json_encode($recent5match);
      }

      $refTeamAggregation = RefTeamAggregation::where([
        ['team_id', $info->team_id],
        ['season_id', $this->seasonId]
      ])->first();
      // 승점 계산
      $winPoint = $refTeamAggregation->win_count * 3 + $refTeamAggregation->draw_count;
      $refTeamAggregation->win_point = $winPoint;
      // 득실점 계산
      $gd = $refTeamAggregation->plus_goals - $refTeamAggregation->minus_goals;
      $refTeamAggregation->goal_difference = $gd;
      if (!empty($recent5match)) {
        $refTeamAggregation->recent_5_match = $recent5match;
      }
      $refTeamAggregation->save();
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
    // $this->delOldData();
  }


  public function update()
  {
    DB::beginTransaction();
    try {
      logger('start team aggregation update');
      $this->UpdateTable();
      DB::commit();
      logger('team aggregation update 성공');
    } catch (Exception $e) {
      DB::rollBack();
      logger($e);
      logger('update team aggregation 실패(RollBack)');
      throw $e; // 관련된 모두 롤백되어야 함.
    }
  }
}
