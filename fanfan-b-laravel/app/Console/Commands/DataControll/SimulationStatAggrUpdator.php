<?php

namespace App\Console\Commands\DataControll;

use App\Enums\Simulation\ScheduleWinnerStatus;
use App\Enums\Simulation\SimulationScheduleStatus;
use App\Models\simulation\SimulationApplicant;
use App\Models\simulation\SimulationApplicantStat;
use App\Models\simulation\SimulationDivisionStat;
use App\Models\simulation\SimulationLeagueStat;
use App\Models\simulation\SimulationLineup;
use App\Models\simulation\SimulationLineupMeta;
use App\Models\simulation\SimulationOverall;
use App\Models\simulation\SimulationSchedule;
use App\Models\simulation\SimulationUserLineupMeta;
use App\Models\user\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DB;
use Throwable;

class SimulationStatAggrUpdator
{
  protected $seasonId;
  protected $scheduleIds = [];
  protected $leagueIds = [];
  protected $divisionIds = [];

  public function __construct(array $_scheduleIds)
  {
    $this->scheduleIds = $_scheduleIds;
  }

  private function setBase()
  {
    SimulationSchedule::with('league')
      ->whereIn('id', $this->scheduleIds)
      ->distinct('league_id')
      ->get()
      ->each(function ($item) {
        if (!isset($this->seasonId)) $this->seasonId = $item->season_id;
        if (!in_array($item->league->division_id, $this->divisionIds)) {
          array_push($this->divisionIds, $item->league->division_id);
        }
        array_push($this->leagueIds, $item->league_id);
      });
  }

  private function divisionUpdate()
  {
    $update = [];
    foreach ($this->divisionIds as $divisionId) {
      $update['overall_avg'] = SimulationOverall::whereHas('lineup.lineupMeta.schedule', function ($query) {
        $query->where([
          ['season_id', $this->seasonId],
          ['status', SimulationScheduleStatus::PLAYED]
        ]);
      })->selectRaw("ROUND(AVG(JSON_UNQUOTE(JSON_EXTRACT(final_overall, CONCAT('$.', sub_position)))),1) AS overall_avg")->value('overall_avg') ?? 0;

      SimulationDivisionStat::updateOrCreateEx(
        [
          'division_id' => $divisionId,
          'season_id' => $this->seasonId
        ],
        $update,
        false,
        true,
      );
    }
  }

  private function leagueUpdate()
  {
    $update = [];
    foreach ($this->leagueIds as $leagueId) {
      $update['overall_avg'] = SimulationOverall::whereHas('lineup.lineupMeta.schedule', function ($query) use ($leagueId) {
        $query->where([
          ['season_id', $this->seasonId],
          ['league_id', $leagueId],
          ['status', SimulationScheduleStatus::PLAYED]
        ]);
      })->selectRaw("ROUND(AVG(JSON_UNQUOTE(JSON_EXTRACT(final_overall, CONCAT('$.', sub_position)))),1) AS overall_avg")->value('overall_avg') ?? 0;

      $bestStatBase = SimulationLineup::whereHas('lineupMeta.schedule', function ($query) use ($leagueId) {
        $query->where([
          ['season_id', $this->seasonId],
          ['league_id', $leagueId],
          ['status', SimulationScheduleStatus::PLAYED]
        ]);
      });

      $update['goal_against_avg'] = $update['goal_avg'] = 0;
      $goalAvg = $bestStatBase->clone()->selectRaw('ROUND(AVG(goal),1) AS goal_avg, ROUND(AVG(goal),1) AS against_avg')->first();
      if (!is_null($goalAvg)) {
        $update['goal_avg'] = $goalAvg->goal_avg;
        $update['goal_against_avg'] = $goalAvg->against_avg;
      }

      $update['best_goal_card'] = $bestStatBase->clone()->orderByDesc('goal')->limit(1)->value('user_plate_card_id');
      $update['best_assist_card'] = $bestStatBase->clone()->orderByDesc('assist')->limit(1)->value('user_plate_card_id');
      $update['best_save_card'] = $bestStatBase->clone()->orderByDesc('save')->limit(1)->value('user_plate_card_id');

      if (isset($update)) {
        SimulationLeagueStat::updateOrCreateEx(
          [
            'league_id' => $leagueId,
            'season_id' => $this->seasonId
          ],
          $update,
          false,
          true,
        );
      }
    }
  }

  private function applicantUpdate()
  {
    foreach ($this->scheduleIds as $scheduleId) {
      $homeId = $awayId = $leagueId = null;
      $update = [];
      SimulationLineupMeta::with('schedule')
        ->where('schedule_id', $scheduleId)
        ->get()
        ->map(function ($info) use (&$homeId, &$awayId, &$leagueId) {
          if (is_null($leagueId)) {
            $leagueId = $info->schedule->league_id;
          }
          ${$info->team_side . 'Id'} = $info->applicant_id;
        });

      if (User::whereHas('applicant', function ($query) use ($homeId) {
        $query->where('id', $homeId);
      })->exists() && User::whereHas('applicant', function ($query) use ($awayId) {
        $query->where('id', $awayId);
      })->exists()) {
        // 오버롤 평균 : user_plate_card_id group simulation overall 
        $update = $this->getOverallAvg($homeId, $awayId, $update);

        // 승무패
        $update = $this->getWDL($homeId, $awayId, $update);

        // 득점 평균 : lineup의 goal/
        $update = $this->getGoalRecord($homeId, $awayId, $update);

        // 실점 평균 : scheduleList 뽑고 내가 아닌 상대의 goal 평균
        $update = $this->getAgainstRecord($homeId, $awayId, $update);

        // 최근5경기 전적
        $update = $this->getLast5Record($homeId, $awayId, $update);

        // 선제/역전 골 시 승패비율 : scenario.first_goal,winner
        $update = $this->firstComebackAvg($homeId, $awayId, $update);

        // 나의 득점/어시스트/평점 1~3위 카드
        $update = $this->getMyBestPlayer($homeId, $awayId, $update);

        foreach ($update as $applicantId => $data) {
          SimulationApplicantStat::where([
            'applicant_id' => $applicantId,
            'season_id' => $this->seasonId,
            'league_id' => $leagueId
          ])
            ->update($data);
        }
      }
    }

    return 0;
  }

  private function getOverallAvg($homeId, $awayId, $update)
  {
    $userAvgs = SimulationOverall::whereHas('lineup.lineupMeta', function ($query) use ($homeId, $awayId) {
      $query->whereIn('applicant_id', [$homeId, $awayId])
        ->whereHas('schedule', function ($scheduleQuery) {
          $scheduleQuery->where([
            ['season_id', $this->seasonId],
            ['status', SimulationScheduleStatus::PLAYED]
          ]);
        });
    })->selectRaw("user_id, ROUND(AVG(JSON_UNQUOTE(JSON_EXTRACT(final_overall, CONCAT('$.', sub_position)))),1) AS overall_avg")
      ->groupBy('user_id')
      ->get()
      ?->keyBy('user_id')
      ->toArray();

    SimulationApplicant::whereIn('user_id', array_keys($userAvgs))->get()
      ->map(function ($info) use ($userAvgs, &$update) {
        $update[$info->id]['season_id'] = $this->seasonId;
        $update[$info->id]['overall_avg'] = $userAvgs[$info->user_id]['overall_avg'];
      });

    return $update;
  }

  private function getWDL($homeId, $awayId, $update)
  {
    foreach ([$homeId, $awayId] as $applicantId) {
      $sub = SimulationSchedule::where([
        'season_id' => $this->seasonId,
        'status' => SimulationScheduleStatus::PLAYED,
      ])
        ->where(function ($query) use ($applicantId) {
          $query->where('home_applicant_id', $applicantId)
            ->orWhere('away_applicant_id', $applicantId);
        })
        ->selectRaw("count(id) as count_played,
                CAST(SUM(CASE WHEN (home_applicant_id = $applicantId AND winner = 'home') 
                  OR (away_applicant_id = $applicantId AND winner = 'away') THEN 1 ELSE 0 END) AS unsigned) AS count_won,
                CAST(SUM(CASE WHEN (home_applicant_id = $applicantId AND winner = 'away')
                  OR (away_applicant_id = $applicantId AND winner = 'home') THEN 1 ELSE 0 END) AS unsigned) AS count_lost,
                CAST(SUM(CASE WHEN (home_applicant_id = $applicantId AND winner = 'draw')
                 OR (away_applicant_id = $applicantId AND winner = 'draw') THEN 1 ELSE 0 END) AS unsigned) AS count_draw
            ");
      $wdl = DB::query()->fromSub($sub, 's')
        ->selectRaw("s.*, count_won * 3 + count_draw as points")
        ->first();

      $update[$applicantId] = array_merge($update[$applicantId], (array) $wdl);
    }

    return $update;
  }

  private function getGoalRecord($homeId, $awayId, $update)
  {
    SimulationLineupMeta::whereIn('applicant_id', [$homeId, $awayId])->whereHas('schedule', function ($schedulequery) {
      $schedulequery->where([
        ['season_id', $this->seasonId],
        ['status', SimulationScheduleStatus::PLAYED]
      ]);
    })->selectRaw('applicant_id, sum(score) as goal_total, ROUND(AVG(score),1) AS goal_avg, ROUND(AVG(rating),1) AS rating_avg')
      ->groupBy('applicant_id')
      ->get()
      ->map(function ($info) use (&$update) {
        $update[$info->applicant_id]['goal'] = $info->goal_total ?? 0;
        $update[$info->applicant_id]['goal_avg'] = $info->goal_avg ?? 0;
        // Rating 추가
        $update[$info->applicant_id]['rating_avg'] = $info->rating_avg ?? 0;
      });

    $sub = SimulationLineupMeta::whereIn('applicant_id', [$homeId, $awayId])->whereHas('schedule', function ($schedulequery) {
      $schedulequery->where([
        ['season_id', $this->seasonId],
        ['status', SimulationScheduleStatus::PLAYED]
      ]);
    })->select('id', 'applicant_id');
    SimulationLineup::joinSub($sub, 'lineup_meta', function ($join) {
      $lineupTbl = SimulationLineup::getModel()->getTable();
      $join->on($lineupTbl . '.lineup_meta_id', 'lineup_meta.id');
    })->selectRaw('applicant_id, ROUND(AVG(rating),1) AS rating_avg')
      ->groupBy('applicant_id')
      ->get()
      ->map(function ($info) use (&$update) {
        $update[$info->applicant_id]['rating_avg'] = $info->rating_avg ?? 0;
      });

    return $update;
  }

  private function getAgainstRecord($homeId, $awayId, $update)
  {
    foreach (['home', 'away'] as $side) {
      $schedules = SimulationLineupMeta::whereHas('schedule', function ($query) {
        $query->where([
          ['season_id', $this->seasonId],
          ['status', SimulationScheduleStatus::PLAYED]
        ]);
      })->where('applicant_id', ${$side . 'Id'})
        ->select('schedule_id')
        ->get()
        ->pluck('schedule_id')
        ->toArray();

      $against = SimulationLineupMeta::whereNot('applicant_id', ${$side . 'Id'})
        ->whereIn('schedule_id', $schedules)
        ->selectRaw('sum(score) as against_total, ROUND(AVG(score),1) AS against_avg')
        ->first();

      $update[${$side . 'Id'}]['goal_against'] = $against->against_total ?? 0;
      $update[${$side . 'Id'}]['goal_against_avg'] = $against->against_avg ?? 0;
    }

    return $update;
  }

  private function getLast5Record($homeId, $awayId, $update)
  {
    $last5Home = [];
    $last5Away = [];
    SimulationSchedule::where('status', SimulationScheduleStatus::PLAYED)
      ->where(function ($query) use ($homeId) {
        $query->where('home_applicant_id', $homeId)
          ->orWhere('away_applicant_id', $homeId);
      })
      ->latest('started_at')
      ->limit(5)
      ->get()
      ->map(function ($info) use ($homeId, &$last5Home) {
        if ($info->winner === ScheduleWinnerStatus::DRAW) {
          array_push($last5Home, $info->winner);
        } else if ($info->home_applicant_id === $homeId) {
          if ($info->winner === ScheduleWinnerStatus::HOME) {
            array_push($last5Home, 'win');
          } else if ($info->winner === ScheduleWinnerStatus::AWAY) {
            array_push($last5Home, 'lost');
          }
        } else if ($info->away_applicant_id === $homeId) {
          if ($info->winner === ScheduleWinnerStatus::HOME) {
            array_push($last5Home, 'lost');
          } else if ($info->winner === ScheduleWinnerStatus::AWAY) {
            array_push($last5Home, 'win');
          }
        }
      });
    $update[$homeId]['recent_5_match'] = $last5Home;

    SimulationSchedule::where('status', SimulationScheduleStatus::PLAYED)
      ->where(function ($query) use ($awayId) {
        $query->where('home_applicant_id', $awayId)
          ->orWhere('away_applicant_id', $awayId);
      })
      ->latest('started_at')
      ->limit(5)
      ->get()
      ->map(function ($info) use ($awayId, &$last5Away) {
        if ($info->winner === ScheduleWinnerStatus::DRAW) {
          array_push($last5Away, $info->winner);
        } else if ($info->home_applicant_id === $awayId) {
          if ($info->winner === ScheduleWinnerStatus::HOME) {
            array_push($last5Away, 'win');
          } else if ($info->winner === ScheduleWinnerStatus::AWAY) {
            array_push($last5Away, 'lost');
          }
        } else if ($info->away_applicant_id === $awayId) {
          if ($info->winner === ScheduleWinnerStatus::HOME) {
            array_push($last5Away, 'lost');
          } else if ($info->winner === ScheduleWinnerStatus::AWAY) {
            array_push($last5Away, 'win');
          }
        }
      });

    $update[$awayId]['recent_5_match'] = $last5Away;
    return $update;
  }

  private function firstComebackAvg($homeId, $awayId, $update)
  {
    $homeAllInfo = SimulationLineupMeta::whereHas('schedule', function ($query) {
      $query->where('season_id', $this->seasonId);
    })->where('applicant_id', $homeId)
      ->whereNotNull('goal_winner_comb')
      ->selectRaw("
      (COUNT(CASE WHEN team_side ='home' AND goal_winner_comb='homehome' THEN 1 END ) + COUNT(CASE WHEN team_side ='away' AND goal_winner_comb='awayaway' THEN 1 END ) )AS firstWin,
      (COUNT(CASE WHEN team_side ='home' AND goal_winner_comb='homedraw' THEN 1 END ) + COUNT(CASE WHEN team_side ='away' AND goal_winner_comb='awaydraw' THEN 1 END ) )AS firstDraw,
      (COUNT(CASE WHEN team_side ='home' AND goal_winner_comb='homeaway' THEN 1 END ) + COUNT(CASE WHEN team_side ='away' AND goal_winner_comb='awayhome' THEN 1 END ) )AS firstLost,
      (COUNT(CASE WHEN team_side ='home' AND goal_winner_comb='awayhome' THEN 1 END ) + COUNT(CASE WHEN team_side ='away' AND goal_winner_comb='homeaway' THEN 1 END ) )AS comebackWin,
      (COUNT(CASE WHEN team_side ='home' AND goal_winner_comb='awayaway' THEN 1 END ) + COUNT(CASE WHEN team_side ='away' AND goal_winner_comb='homehome' THEN 1 END ) )AS comebackLost
    ")->first();

    $awayAllInfo = SimulationLineupMeta::whereHas('schedule', function ($query) {
      $query->where('season_id', $this->seasonId);
    })->where('applicant_id', $awayId)
      ->whereNotNull('goal_winner_comb')
      ->selectRaw("
      (COUNT(CASE WHEN team_side ='home' AND goal_winner_comb='homehome' THEN 1 END ) + COUNT(CASE WHEN team_side ='away' AND goal_winner_comb='awayaway' THEN 1 END ) )AS firstWin,
      (COUNT(CASE WHEN team_side ='home' AND goal_winner_comb='homedraw' THEN 1 END ) + COUNT(CASE WHEN team_side ='away' AND goal_winner_comb='awaydraw' THEN 1 END ) )AS firstDraw,
      (COUNT(CASE WHEN team_side ='home' AND goal_winner_comb='homeaway' THEN 1 END ) + COUNT(CASE WHEN team_side ='away' AND goal_winner_comb='awayhome' THEN 1 END ) )AS firstLost,
      (COUNT(CASE WHEN team_side ='home' AND goal_winner_comb='awayhome' THEN 1 END ) + COUNT(CASE WHEN team_side ='away' AND goal_winner_comb='homeaway' THEN 1 END ) )AS comebackWin,
      (COUNT(CASE WHEN team_side ='home' AND goal_winner_comb='awayaway' THEN 1 END ) + COUNT(CASE WHEN team_side ='away' AND goal_winner_comb='homehome' THEN 1 END ) )AS comebackLost
    ")->first();

    if ($homeAllInfo) {
      $firstAllCnt = $homeAllInfo['firstWin'] + $homeAllInfo['firstDraw'] + $homeAllInfo['firstLost'];
      $combackAllCnt = $homeAllInfo['comebackWin'] + $homeAllInfo['comebackLost'];

      if ($firstAllCnt > 0 && $combackAllCnt > 0) {
        $update[$homeId]['scoring_first_won_avg'] = BigDecimal::of($homeAllInfo['firstWin'])->dividedBy(BigDecimal::of($firstAllCnt), 2, RoundingMode::HALF_UP)->multipliedBy(100);
        $update[$homeId]['scoring_first_draw_avg'] = BigDecimal::of($homeAllInfo['firstDraw'])->dividedBy(BigDecimal::of($firstAllCnt), 2, RoundingMode::HALF_UP)->multipliedBy(100);
        $update[$homeId]['scoring_first_lost_avg'] = BigDecimal::of($homeAllInfo['firstLost'])->dividedBy(BigDecimal::of($firstAllCnt), 2, RoundingMode::HALF_UP)->multipliedBy(100);
        $update[$homeId]['comeback_won_avg'] = BigDecimal::of($homeAllInfo['comebackWin'])->dividedBy(BigDecimal::of($combackAllCnt), 2, RoundingMode::HALF_UP)->multipliedBy(100);
        $update[$homeId]['comeback_lost_avg'] = BigDecimal::of($homeAllInfo['comebackLost'])->dividedBy(BigDecimal::of($combackAllCnt), 2, RoundingMode::HALF_UP)->multipliedBy(100);
      }
    }

    if ($awayAllInfo) {
      if ($firstAllCnt > 0 && $combackAllCnt > 0) {
        $firstAllCnt = $awayAllInfo['firstWin'] + $awayAllInfo['firstDraw'] + $awayAllInfo['firstLost'];
        $combackAllCnt = $awayAllInfo['comebackWin'] + $awayAllInfo['comebackLost'];
        $update[$awayId]['scoring_first_won_avg'] = BigDecimal::of($awayAllInfo['firstWin'])->dividedBy(BigDecimal::of($firstAllCnt), 2, RoundingMode::HALF_UP)->multipliedBy(100);
        $update[$awayId]['scoring_first_draw_avg'] = BigDecimal::of($awayAllInfo['firstDraw'])->dividedBy(BigDecimal::of($firstAllCnt), 2, RoundingMode::HALF_UP)->multipliedBy(100);
        $update[$awayId]['scoring_first_lost_avg'] = BigDecimal::of($awayAllInfo['firstLost'])->dividedBy(BigDecimal::of($firstAllCnt), 2, RoundingMode::HALF_UP)->multipliedBy(100);
        $update[$awayId]['comeback_won_avg'] = BigDecimal::of($awayAllInfo['comebackWin'])->dividedBy(BigDecimal::of($combackAllCnt), 2, RoundingMode::HALF_UP)->multipliedBy(100);
        $update[$awayId]['comeback_lost_avg'] = BigDecimal::of($awayAllInfo['comebackLost'])->dividedBy(BigDecimal::of($combackAllCnt), 2, RoundingMode::HALF_UP)->multipliedBy(100);
      }
    }
    return $update;
  }

  private function getMyBestPlayer($homeId, $awayId, $update)
  {
    // TODO : simulationOverall 구조 변경 후 refactoring 예정
    $best3BaseHome = SimulationLineup::whereHas('lineupMeta', function ($query) use ($homeId) {
      $query->where('applicant_id', $homeId)
        ->whereHas('schedule', function ($scheduleQuery) {
          $scheduleQuery->where('status', SimulationScheduleStatus::PLAYED)
            ->whereHas('league', function ($leagueQuery) {
              $leagueQuery->where('season_id', $this->seasonId);
            });
        });
    })->with([
      'userPlateCard:id,plate_card_id,draft_level,card_grade,player_name,position',
      'userPlateCard.plateCardWithTrashed:id,headshot_path,match_name',
      'simulationOverall:user_plate_card_id,sub_position,final_overall',
    ])->where(function ($query) {
      $query->where('game_started', true)
        ->orWhere('is_changed', true);
    })->selectRaw('user_plate_card_id, SUM(goal) AS goals, SUM(assist) AS assists, SUM(save) AS saves')
      ->groupBy('user_plate_card_id')
      ->get()
      ->map(function ($info) use ($homeId, $awayId) {
        // $info->player_name  = $info->userPlateCard->player_name;
        $info->position = $info->userPlateCard->position;
        $info->sub_position = $info->simulationOverall->sub_position;
        $info->final_overall = $info->simulationOverall->final_overall[$info->simulationOverall->sub_position];
        $info->card_grade = $info->userPlateCard->card_grade;
        $info->card_grade_order = config('constant.DRAFT_CARD_GRADE_ORDER')[$info->userPlateCard->card_grade];
        $info->goal_position_order = config('constant.SIMULATION_POSITION_ORDER')['goal'][$info->position];
        $info->assist_position_order = config('constant.SIMULATION_POSITION_ORDER')['assist'][$info->position];
        $info->draft_level = $info->userPlateCard->draft_level;
        $info->headshot_path = $info->userPlateCard->plateCardWithTrashed?->headshot_path ?? null;
        $info->player_name = $info->userPlateCard->plateCardWithTrashed?->match_name;

        unset($info->userPlateCard);
        unset($info->simulationOverall);

        return $info;
      })
      ->sortBy('player_name');

    $best3BaseAway = SimulationLineup::whereHas('lineupMeta', function ($query) use ($awayId) {
      $query->where('applicant_id', $awayId)
        ->whereHas('schedule', function ($scheduleQuery) {
          $scheduleQuery->where('status', SimulationScheduleStatus::PLAYED)
            ->whereHas('league', function ($leagueQuery) {
              $leagueQuery->where('season_id', $this->seasonId);
            });
        });
    })->with([
      'userPlateCard:id,plate_card_id,draft_level,card_grade,player_name,position',
      'userPlateCard.plateCardWithTrashed:id,headshot_path,match_name',
      'simulationOverall:user_plate_card_id,sub_position,final_overall'
    ])->where(function ($query) {
      $query->where('game_started', true)
        ->orWhere('is_changed', true);
    })->selectRaw('user_plate_card_id, SUM(goal) AS goals, SUM(assist) AS assists, SUM(save) AS saves')
      ->groupBy('user_plate_card_id')
      ->get()
      ->map(function ($info) {
        // $info->player_name  = $info->userPlateCard->player_name;
        $info->position = $info->userPlateCard->position;
        $info->sub_position = $info->simulationOverall->sub_position;
        $info->final_overall = $info->simulationOverall->final_overall[$info->simulationOverall->sub_position];
        $info->card_grade = $info->userPlateCard->card_grade;
        $info->card_grade_order = config('constant.DRAFT_CARD_GRADE_ORDER')[$info->userPlateCard->card_grade];
        $info->goal_position_order = config('constant.SIMULATION_POSITION_ORDER')['goal'][$info->position];
        $info->assist_position_order = config('constant.SIMULATION_POSITION_ORDER')['assist'][$info->position];
        $info->draft_level = $info->userPlateCard->draft_level;
        $info->headshot_path = $info->userPlateCard->plateCardWithTrashed?->headshot_path ?? null;
        $info->player_name = $info->userPlateCard->plateCardWithTrashed?->match_name;

        unset($info->userPlateCard);
        unset($info->simulationOverall);

        return $info;
      })
      ->sortBy('player_name');


    $update[$homeId]['best_goal_players'] = array_values($best3BaseHome->sortBy('goal_position_order')->sortBy('card_grade_order')->sortByDesc('draft_level')->sortByDesc('final_overall')->sortByDesc('goals')->take(3)->toArray());
    $update[$homeId]['best_assist_players'] = array_values($best3BaseHome->sortBy('assist_position_order')->sortBy('card_grade_order')->sortByDesc('draft_level')->sortByDesc('final_overall')->sortByDesc('assists')->take(3)->toArray());
    $update[$homeId]['best_save_players'] = array_values($best3BaseHome->sortBy('card_grade_order')->sortByDesc('draft_level')->sortByDesc('final_overall')->sortByDesc('saves')->take(3)->toArray());
    $update[$awayId]['best_goal_players'] = array_values($best3BaseAway->sortBy('goal_position_order')->sortBy('card_grade_order')->sortByDesc('draft_level')->sortByDesc('final_overall')->sortByDesc('goals')->take(3)->toArray());
    $update[$awayId]['best_assist_players'] = array_values($best3BaseAway->sortBy('assist_position_order')->sortBy('card_grade_order')->sortByDesc('draft_level')->sortByDesc('final_overall')->sortByDesc('assists')->take(3)->toArray());
    $update[$awayId]['best_save_players'] = array_values($best3BaseAway->sortBy('card_grade_order')->sortByDesc('draft_level')->sortByDesc('final_overall')->sortByDesc('saevs')->take(3)->toArray());

    //평점 1~3위 카드 : 50% 이상 출전한 선수만
    $allCnts = SimulationLineupMeta::whereHas('schedule', function ($query) {
      $query->where('season_id', $this->seasonId);
    })->whereIn('applicant_id', [$homeId, $awayId])
      ->groupBy('applicant_id')
      ->selectRaw('COUNT(*) AS cnt, applicant_id')
      ->get()->keyBy('applicant_id')->toArray();

    $update[$homeId]['best_rating_players'] = array_values(SimulationLineup::with([
      'userPlateCard:id,plate_card_id,draft_level,card_grade,player_name,position',
      'userPlateCard.plateCardWithTrashed:id,headshot_path,match_name',
      'simulationOverall:user_plate_card_id,sub_position,final_overall'
    ])->whereHas('lineupMeta', function ($query) use ($homeId) {
      $query->where('applicant_id', $homeId)
        ->whereHas('schedule', function ($scheduleQuery) {
          $scheduleQuery->where('season_id', $this->seasonId);
        });
    })->selectRaw('COUNT(*) as cnt, IFNULL(ROUND(AVG(rating),2),0) as rating_avg, user_plate_card_id, lineup_meta_id')
      ->groupBy(['lineup_meta_id', 'user_plate_card_id'])
      ->get()
      ->map(function ($info) use ($allCnts, $homeId) {
        // $info->player_name  = $info->userPlateCard->player_name;
        $info->position = $info->userPlateCard->position;
        $info->sub_position = $info->simulationOverall->sub_position;
        $info->final_overall = $info->simulationOverall->final_overall[$info->simulationOverall->sub_position];
        $info->card_grade = $info->userPlateCard->card_grade;
        $info->card_grade_order = config('constant.DRAFT_CARD_GRADE_ORDER')[$info->userPlateCard->card_grade];
        $info->draft_level = $info->userPlateCard->draft_level;
        $info->headshot_path = $info->userPlateCard->plateCardWithTrashed?->headshot_path ?? null;
        $info->half = false;
        if ($info->cnt > ($allCnts[$homeId]['cnt'] / 2)) {
          $info->half = true;
        }
        $info->player_name = $info->userPlateCard->plateCardWithTrashed?->match_name;

        unset($info->userPlateCard);
        unset($info->simulationOverall);
        unset($info->plateCardWithTrashed);

        return $info;
      })
      ->sortBy('player_name')
      ->sortBy('card_grade_order')
      ->sortByDesc('draft_level')
      ->sortByDesc('final_overall')
      ->sortByDesc('rating_avg')
      ->sortByDesc('half')
      ->take(3)->toArray());

    $update[$awayId]['best_rating_players'] = array_values(SimulationLineup::with([
      'userPlateCard:id,plate_card_id,draft_level,card_grade,player_name,position',
      'userPlateCard.plateCardWithTrashed:id,headshot_path,match_name',
      'simulationOverall:user_plate_card_id,sub_position,final_overall'
    ])->whereHas('lineupMeta', function ($query) use ($awayId) {
      $query->where('applicant_id', $awayId)
        ->whereHas('schedule', function ($scheduleQuery) {
          $scheduleQuery->where('season_id', $this->seasonId);
        });
    })->selectRaw('COUNT(*) as cnt, IFNULL(ROUND(AVG(rating),2),0) as rating_avg, user_plate_card_id, lineup_meta_id')
      ->groupBy(['lineup_meta_id', 'user_plate_card_id'])
      ->get()

      ->map(function ($info) use ($allCnts, $awayId) {
        // $info->player_name  = $info->userPlateCard->player_name;
        $info->position = $info->userPlateCard->position;
        $info->sub_position = $info->simulationOverall->sub_position;
        $info->final_overall = $info->simulationOverall->final_overall[$info->simulationOverall->sub_position];
        $info->card_grade = $info->userPlateCard->card_grade;
        $info->card_grade_order = config('constant.DRAFT_CARD_GRADE_ORDER')[$info->userPlateCard->card_grade];
        $info->draft_level = $info->userPlateCard->draft_level;
        $info->headshot_path = $info->userPlateCard->plateCardWithTrashed?->headshot_path ?? null;
        $info->half = false;
        if ($info->cnt > ($allCnts[$awayId]['cnt'] / 2)) {
          $info->half = true;
        }
        $info->player_name = $info->userPlateCard->plateCardWithTrashed?->match_name;

        unset($info->userPlateCard);
        unset($info->simulationOverall);
        unset($info->plateCardWithTrashed);

        return $info;
      })
      ->sortBy('player_name')
      ->sortBy('card_grade_order')
      ->sortByDesc('draft_level')
      ->sortByDesc('final_overall')
      ->sortByDesc('cnt')
      ->sortByDesc('rating_avg')
      ->sortByDesc('half')
      ->take(3)->toArray());

    return $update;
  }

  public function update()
  {
    try {
      $this->setBase();
      $this->divisionUpdate();
      $this->leagueUpdate();
      $this->applicantUpdate();
    } catch (Throwable $th) {
      throw $th;
    }
  }
}
