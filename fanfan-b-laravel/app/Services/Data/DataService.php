<?php

namespace App\Services\Data;

use App\Enums\GameType;
use App\Enums\Opta\League\LeagueStatusType;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\YesNo;
use App\Libraries\Classes\Exception;
use App\Libraries\Traits\CommonTrait;
use App\Libraries\Traits\DraftTrait;
use App\Libraries\Traits\GameTrait;
use App\Models\data\League;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\data\SeasonTeam;
use App\Models\game\PlateCard;
use App\Models\game\PlayerDailyStat;
use App\Models\meta\RefCountryCode;
use App\Models\user\UserPlateCard;
use Carbon\Carbon;
use DB;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

interface DataServiceInterface
{
  public function leaguesQuery();
  public function bestLineup(array $_filter);
  public function getMaxRound(array $_seasons);
  public function leaguesWithRound();
  public function roundSchedules(array $_filter);
  public function getSchedules(array $_filter);
  public function getFixtureSchedules($_teamId);
  public function getLeagueWithTeams(string $_seasonId);
  public function getTeams($_filter = [], $_limit = null, $_columns = null);
  public function getPlayers($_filter = [], $_limit = 10, $_columns = null);
  public function getUserCardsCountByStatus($_plateCardId = null);
  public function getPlateCardInfo($_plateCardId);
  public function getLeagues($_filter = [], $_limit = 10, $_columns = null);
}

class DataService implements DataServiceInterface
{
  use GameTrait, CommonTrait, DraftTrait;
  protected ?Authenticatable $user;

  public function __construct(?Authenticatable $_user)
  {
    $this->user = $_user;
  }

  public function leaguesQuery(): Builder|League
  {
    try {
      return League::query();
    } catch (Throwable $th) {
      throw $th;
    }
  }

  public function bestLineup(array $_filter)
  {
    if ($_filter['schedule']) {
      $redisKey = $this->getRedisCachingKey(sprintf('main_best_lineup_schedule_%s', $_filter['schedule']));
    } else {
      $redisKey = $this->getRedisCachingKey(sprintf('main_best_lineup_%s_%s', $_filter['season'], $_filter['round']));
    }

    if (Redis::exists($redisKey)) {
      return json_decode(Redis::get($redisKey), true);
    }

    $sub = OptaPlayerDailyStat::when($_filter['schedule'], function ($query, $schedule_id) {
      $query->where('schedule_id', $schedule_id);
    }, function ($query) use ($_filter) {
      $query->where('season_id', $_filter['season'])
        ->whereHas('schedule', function ($scheduleQuery) use ($_filter) {
          $scheduleQuery->where('round', $_filter['round']);
        });
    })
      ->selectRaw('player_id, 
    summary_position,
      sum(rating) as rating,
       cast(sum(mins_played) as unsigned) as mp,
        cast(sum(goals) as unsigned) as goals,
         cast(sum(goal_assist) as unsigned) as assists,
         cast(sum(saves) as unsigned) as saves,
         cast(sum(clean_sheet) as unsigned) as clean_sheet,
          ROW_NUMBER() over(PARTITION BY summary_position order by sum(rating) desc) as rnum
          ')
      ->groupBy('player_id', 'summary_position');

    $result = DB::query()->fromSub($sub, 'sub')
      ->selectRaw('sub.rnum, sub.player_id, sub.summary_position, sub.rating, sub.mp, sub.goals, sub.assists, sub.saves, sub.clean_sheet')
      ->where('rnum', '<=', 4)
      ->get()
      ->map(function ($info) {
        $info->rating = __setDecimal($info->rating, 1);
        $playerKeys = ['player_id', 'headshot_path', 'team_id', 'deleted_at', ...config('commonFields.player')];
        $player = PlateCard::withTrashed()
          ->with('team:id,code')
          ->select($playerKeys)->where('player_id', $info->player_id)->first();
        $info->player = $player;
        unset($player->plateCard);
        return $info;
      })
      ->sortBy(['rnum', 'player.first_name_eng'])
      ->groupBy('summary_position')
      ->toArray();

    if (empty($result)) {
      return [
        PlayerPosition::ATTACKER => [],
        PlayerPosition::MIDFIELDER => [],
        PlayerPosition::DEFENDER => [],
        PlayerPosition::GOALKEEPER => []
      ];
    } else {
      Redis::set($redisKey, json_encode($result), 'EX', 7200);
    }

    return $result;
  }

  public function getMaxRound($seasons)
  {
    return Schedule::whereIn('season_id', $seasons)
      ->groupBy('season_id')
      ->selectRaw('season_id, max(round) as max_round')
      ->get()
      ->pluck('max_round', 'season_id')
      ->toArray();
  }

  public function leaguesWithRound()
  {
    $redisKeyName = 'current_round_' . now()->toDateString();
    if (Redis::exists($redisKeyName)) {
      return json_decode(Redis::get($redisKeyName), true);
    }

    try {
      $sub = Schedule::selectRaw('*, ROW_NUMBER() OVER(PARTITION BY league_id ORDER BY started_at desc) AS rnum');

      $latest = DB::query()->fromSub($sub, 'sub')->where('rnum', 1)
        ->get()
        ->toArray();

      $seasons = array_column($latest, 'season_id');
      $maxRounds = $this->getMaxRound($seasons);

      $playedRoundsBySeason = Schedule::selectRaw('season_id, GROUP_CONCAT(DISTINCT(ga_round)) AS rounds')
        ->whereIn('season_id', $seasons)
        ->where([
          ['status', ScheduleStatus::PLAYED]
        ])
        ->groupBy('season_id')
        ->get()
        ->keyBy('season_id')
        ->toArray();

      $result = [];
      foreach ($latest as $item) {
        $league = League::find($item->league_id);
        if (is_null($league)) {
          continue;
        }

        $playedRounds = [];
        if (isset($playedRoundsBySeason[$item->season_id]) && !empty($playedRoundsBySeason[$item->season_id]['rounds'])) {
          $playedRounds = array_map(
            'intval',
            explode(',', $playedRoundsBySeason[$item->season_id]['rounds'])
          );
        }
        $prevSeason = null;
        if (empty($playedRounds)) {
          $prevSeason = Season::where([
            ['league_id', $item->league_id],
            ['id', '<>', $item->season_id],
          ])
            ->latest('start_date')
            ->first();
          $prevSeason['max_round'] = Schedule::where([
            'season_id' => $prevSeason->id,
            'status' => ScheduleStatus::PLAYED,
          ])->max('ga_round');
        }
        $currentRoundSchedules = $this->getStatusCount(Schedule::where([
          ['season_id', $item->season_id],
          ['ga_round', last($playedRounds)]
        ])->get()->toArray());

        if ($currentRoundSchedules['Fixture'] > 0) {
          unset($playedRounds[count($playedRounds) - 1]);
        }

        $currentRound = $this->upcomingRound($item->season_id);
        if (is_null($currentRound)) $currentRound = $maxRounds[$item->season_id];

        $data = [
          'league_id' => $item->league_id,
          'league_code' => $league['league_code'],
          'season_id' => $item->season_id,
          'order_no' => $league['order_no'],
          'status' => $league['status'],
          'current_round' => empty($playedRounds) ? 1 : $currentRound,
          'max_round' => $maxRounds[$item->season_id],
          'played_rounds' => $playedRounds,
          'prev_season' => $prevSeason ?? null,
        ];

        $result[] = $data;
      }
    } catch (Throwable $th) {
      throw $th;
    }

    Redis::set($redisKeyName, json_encode($result), 'EX', 600);

    return $result;
  }

  public function _leaguesWithRound(): array
  {
    $redisKeyName = 'current_round';
    if (Redis::exists($redisKeyName)) {
      return json_decode(Redis::get($redisKeyName), true);
    }

    $leagues = $this->leaguesQuery()
      ->with('currentSeason')
      ->get()
      ->keyBy('id')
      ->toArray();

    $maxRounds = $this->getMaxRound($leagues);

    $result = [];
    try {
      $sub = Schedule::selectRaw('*, ROW_NUMBER() OVER(PARTITION BY league_id ORDER BY started_at) AS rnum')
        ->whereIn('status', [ScheduleStatus::FIXTURE, ScheduleStatus::PLAYING]);

      DB::query()->fromSub($sub, 'sub')->where('rnum', 1)
        ->get()
        ->map(function ($item) use ($leagues, $maxRounds, &$result) {

          if (!isset($leagues[$item->league_id])) {
            return true;
          }

          $result[] = [
            'league_id' => $item->league_id,
            'season_id' => $leagues[$item->league_id]['current_season']['id'],
            'order_no' => $leagues[$item->league_id]['order_no'],
            'status' => $leagues[$item->league_id]['status'],
            'current_round' => $item->round,
            'max_round' => $maxRounds[$leagues[$item->league_id]['current_season']['id']],
          ];
        });

      // order_no로 정렬
      // foreach ($result as $key => $value) {
      //   $sort[$key] = $value['order_no'];
      // }
      // array_multisort($sort, SORT_ASC, $result);
    } catch (Throwable $th) {
      throw $th;
    }

    Redis::set($redisKeyName, json_encode($result), 'EX', 600);
    return $result;
  }

  // 사용 X
  public function roundSchedules(array $_filter): array
  {
    $result = [];
    $statusTypes = [
      ScheduleStatus::PLAYING,
      ScheduleStatus::FIXTURE,
      ScheduleStatus::PLAYED,
      [ScheduleStatus::POSTPONED, ScheduleStatus::SUSPENDED],
    ];

    try {
      $schedules = $this->getSchedules($_filter);
      foreach ($statusTypes as $type) {
        foreach ($schedules as $idx => $schedule) {
          if (is_array($type)) {
            if (in_array($schedule['status'], $type)) {
              $result['Canceled'][] = $schedule;
              unset($schedule[$idx]);
            }
            continue;
          }
          if ($schedule['status'] === $type) {
            $result[$schedule['status']][] = $schedule;
            unset($schedule[$idx]);
          }
        }
      }
    } catch (Throwable $th) {
      throw $th;
    }

    return $result;
  }

  //   public function weekSchedules()
  //   {
  //     $result = [];
  //     $statusTypes = [ScheduleStatus::PLAYING, ScheduleStatus::FIXTURE, ScheduleStatus::FINAL];
  //     $param = [
  //       'period' => 7,
  //       'status' => null,
  //     ];
  //     $schedules = $this->getSchedules($param);
  //     try {
  //       foreach ($schedules as $league => $leagueSchedules) {
  //         foreach ($statusTypes as $type) {
  //           foreach ($leagueSchedules as $idx => $schedule) {
  //             if (is_array($type)) {
  //               if (in_array($schedule['status'], $type)) {
  //                 $result[$league]['Final'][] = $schedule;
  //                 unset($leagueSchedules[$idx]);
  //               }
  //               continue;
  //             }
  //             if ($schedule['status'] === $type) {
  //               $result[$league][$schedule['status']][] = $schedule;
  //               unset($leagueSchedules[$idx]);
  //             }
  //           }
  //         }
  //       }
  // //
  //     } catch (Throwable $th) {
  //       throw $th;
  //     }
  //     dd($result);
  //   }

  public function getSchedules(array $_filter, bool $_onlyActive = true): array
  {
    $leagues = $this->leaguesQuery()->get()->keyBy('id')->toArray();

    // 현재 league, season인 schedules 가져오기
    $schedules = Schedule::whereNotIn('league_id', [config('constant.LEAGUE_CODE.UCL')])
      ->when($_onlyActive, function ($query) {
        $query->whereHas('season', function ($query) {
          $query->where('active', YesNo::YES);
        });
      })
      ->with([
        'home:' . implode(',', config('commonFields.team')),
        'away:' . implode(',', config('commonFields.team')),
      ])
      ->has('home')
      ->has('away')
      ->when($_filter['season'], function ($query, $seasonId) {
        $query->whereSeasonId($seasonId);
      })
      ->when($_filter['round'], function ($query, $round) {
        $query->whereRound($round);
      })
      // ->when($_filter['period'], function ($query, $day) {
      //   $query->whereBetween('started_at',
      //     [now()->subDays(30)->startOfDay(), now()->addDays($day)->endOfDay()]);
      // }, function ($query) {
      //   $query->whereDate('started_at', now()->toDateString());
      // })
      ->when($_filter['status'], function ($query, $status) {
        $query->whereStatus($status);
      })
      ->oldest('started_at')
      ->get()
      ->map(function ($info) use ($leagues) {
        // schedule 을 돌리면서 해당 name 가져오기
        $info->league_name = $leagues[$info['league_id']]['name'];
        $info->league_code = $leagues[$info['league_id']]['league_code'];

        if (!in_array($info->status, [ScheduleStatus::PLAYING, ScheduleStatus::PLAYED])) {
          $info->makeHidden([
            'match_length_min',
            'match_length_sec',
            'score_home',
            'score_away',
            'winner',
            'period_id'
          ]);
        }

        return $info;
      })
      ->toArray();

    return $schedules;
  }

  public function getFixtureSchedules($_playerId)
  {
    $teamId = PlateCard::where('player_id', $_playerId)->value('team_id');

    $upgrading = Schedule::whereHas('draftSelection', function ($query) use ($_playerId) {
      $query->where([
        ['user_id', $this->user->id],
        ['player_id', $_playerId]
      ]);
    })
      ->with([
        'home:' . implode(',', config('commonFields.team')),
        'away:' . implode(',', config('commonFields.team')),
      ])
      ->has('home')
      ->has('away')
      ->whereHas('gamePossibleSchedule', function ($query) {
        $query->where([
          ['status', '!=', ScheduleStatus::PLAYED],
        ])->has('gameSchedule');
      })
      ->where(function ($query) use ($teamId) {
        $query->where('home_team_id', $teamId)
          ->orWhere('away_team_id', $teamId);
      })->orderBy('started_at');

    $list['upgrading'] = $upgrading->get()->toArray();
    $upgradingIds = $upgrading->pluck('id');
    $fixtureCount = 5 - count($list['upgrading']);

    // fixture
    $list['fixture'] = Schedule::with([
      'home:' . implode(',', config('commonFields.team')),
      'away:' . implode(',', config('commonFields.team')),
    ])
      ->has('home')
      ->has('away')
      ->whereHas('gamePossibleSchedule', function ($query) {
        $query->where([
          ['status', '!=', ScheduleStatus::PLAYED],
        ])->whereHas('gameSchedule.game', function ($gameQuery) {
          $gameQuery->where('mode', GameType::NORMAL);
        });
      })
      ->where(function ($query) use ($teamId) {
        $query->where('home_team_id', $teamId)
          ->orWhere('away_team_id', $teamId);
      })
      ->whereNotIn('id', $upgradingIds)
      ->orderBy('started_at')
      ->limit($fixtureCount)
      ->get()
      ->toArray();
    return $list;
  }

  public function getLeagueWithTeams($_filter): array
  {
    $input = [
      'name' => null,
    ];

    $input = array_merge($input, $_filter);

    $clubList = [];
    $this->getTeams($input, '', ['season_id', 'team_id', 'code', 'name', 'short_name', 'name'])->map(function ($item) use (&$clubList) {
      $team['id'] = $item->team_id;
      $team['name'] = $item->name;
      $team['code'] = $item->code;
      $team['short_name'] = $item->short_name;
      $clubList[$item->season->league_id][] = $team;
    });

    $list = [];
    $this->leaguesQuery()
      ->select('id', 'name', 'league_code', 'order_no', 'country', 'country_id', 'country_code', 'is_friendly', 'status')
      ->get()
      ->map(
        function ($item) use ($clubList, &$list) {
          if (isset($clubList[$item->id])) {
            $item->club = $clubList[$item->id];
            $list[] = $item->toArray();
          }
        }
      );

    return $list;
  }

  public function getTeams($_filter = [], $_limit = null, $_columns = null)
  {
    try {
      return SeasonTeam::query()
        ->with('season.league')
        ->has('season.league')
        ->when($_filter['season'], function ($query, $season) {
          $query->where('season_id', $season);
        }, function ($query) {
          $query->currentSeason();
        })
        // ->currentSeason()
        ->when($_columns, function ($query, $columns) {
          $query->select($columns);
        })
        ->when($_filter['name'], function ($query, $name) {
          $query->whereLike('name', $name);
        })
        ->when($_limit, function ($query, $limit) {
          $query->limit($limit);
        })
        ->orderBy('short_name')
        ->get();
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function getPlayers($_filter = [], $_limit = 10, $_columns = null)
  {
    try {
      return PlateCard::withTrashed()
        ->with([
          'team',
          'optaPlayerSeasonStat' => function ($query) {
            $query->whereHas('season.league', function ($league) {
              $league->where('status', LeagueStatusType::SHOW);
            });
          },
          'optaPlayerSeasonStat.season.league',
        ])
        ->whereHas('season', function ($seasonQuery) {
          $seasonQuery->currentSeasons()->whereHas('league', function ($leagueQuery) {
            $leagueQuery->where('status', LeagueStatusType::SHOW);
          });
        })
        ->when($_columns, function ($query, $columns) {
          $query->select($columns);
        })
        ->when($_filter['name'], function ($query, $name) {
          $query->nameFilterWhere($name);
        })
        ->limit($_limit)
        ->get()
        ->map(function ($item) {
          // $startDate = null;
          // $seasonLeague = [];
          // foreach ($item->optaPlayerSeasonStat as $stat) {
          //   if (!isset($startDate) || Carbon::parse($stat->season->start_date)->timestamp > Carbon::parse($startDate)->timestamp) {
          //     $startDate = $stat->season->start_date;
          //     $seasonLeague['season_id'] = $stat->season_id;
          //     $seasonLeague['league_id'] = $stat->season->league_id;
          //   }
          // }
          $item->league_id = $item->season->league_id;
          unset($item->optaPlayerSeasonStat);
          return $item;
        });
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  // 카드 상태별 count
  public function getUserCardsCountByStatus($_plateCardId = null)
  {
    $userCard = [];
    if (!empty($this->user)) {
      UserPlateCard::where('user_id', $this->user->id)
        ->when($_plateCardId, function ($query, $plateCardId) {
          $query->where('plate_card_id', $plateCardId);
        })
        ->selectRaw('plate_card_id, status, COUNT(*) AS count')
        ->groupBy('plate_card_id', 'status')
        ->get()
        ->map(function ($item) use (&$userCard) {
          $userCard[$item->plate_card_id][$item->status] = $item->count;
          return $userCard;
        });
    }
    return $userCard;
  }

  // 플레이트카드 전체정보 + user가 가진 카드 개수
  public function getPlateCardInfo($_plateCardId)
  {
    try {
      $cardInfo = PlateCard::with('refPlayerOverall')
        ->withTrashed()->where('id', $_plateCardId)->first()->toArray();
      // 실제 경기에서 뛰었던 모든 position
      $positions = PlayerDailyStat::where([
        ['player_id', $cardInfo['player_id']],
        ['season_id', $cardInfo['season_id']]
      ])
        ->selectRaw('DISTINCT(summary_position)')->get()->pluck('summary_position');

      $cardInfo['sub_position'] = $positions;

      $cardInfo['final_overall'] = null;
      if ($cardInfo['ref_player_overall']) {
        foreach ($cardInfo['ref_player_overall'] as $playerOverall) {
          if ($playerOverall['season_id'] === $cardInfo['season_id']) {
            $cardInfo['final_overall'] = $playerOverall['final_overall'] ?? null;
          }
        }
      }

      unset($cardInfo['ref_player_overall']);

      return $cardInfo;
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function getLeagues($_filter = [], $_limit = 10, $_columns = null)
  {
    try {
      return $this->leaguesQuery()
        ->clone()
        ->when($_columns, function ($query, $columns) {
          $query->select($columns);
        })
        ->when($_filter['name'], function ($query, $name) {
          $query->whereLike(['name', 'league_code'], $name);
        })
        ->when($_limit, function ($query, $limit) {
          $query->limit($limit);
        })
        ->get();
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function getPlayerBaseInfo(PlateCard|UserPlateCard $_data)
  {
    $playerInfo = [];
    if ($_data instanceof PlateCard) {
      $playerId = $_data->player_id;
      $plateCardInfo = $_data;

      // 실제 경기에서 뛰었던 모든 position
      $positions = PlayerDailyStat::whereHas('season', function ($query) {
        $query->currentSeasons();
      })
        ->where('player_id', $plateCardInfo->player_id)
        ->selectRaw('DISTINCT(summary_position)')->get()->pluck('summary_position');

      // 선수 기본 정보
      $playerInfo['id'] = $playerId;
      $playerInfo['plate_card_id'] = $plateCardInfo->id;
      $playerInfo['plate_card_position'] = $plateCardInfo->position;
      foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
        $playerInfo[$field] = $plateCardInfo->{$field};
      }
      $playerInfo['player_name'] = $plateCardInfo->player_name;
      $playerInfo['short_player_name'] = $plateCardInfo->short_player_name;
      $playerInfo['match_name_eng'] = $plateCardInfo->match_name_eng;
      $playerInfo['position'] = $positions;
      $playerInfo['headshot_path'] = $plateCardInfo->headshot_path;
      $playerInfo['team']['id'] = $plateCardInfo->team_id;
      $playerInfo['team']['code'] = $plateCardInfo->team_code;
      $playerInfo['team']['name'] = $plateCardInfo->team_name;
      $playerInfo['team']['short_name'] = $plateCardInfo->team_short_name;

      $refPlayerInfo = $plateCardInfo->refPlayerOverall;
      $playerInfo['final_overall'] = null;
      if ($refPlayerInfo) {
        foreach ($refPlayerInfo as $playerOverall) {
          if ($playerOverall['season_id'] === $plateCardInfo['season_id']) {
            $playerInfo['final_overall'] = $playerOverall['final_overall'] ?? null;
          }
        }
      }
    } else {
      $userPlateCard = $_data;
      $plateCardInfo = $userPlateCard->plateCardWithTrashed;
      $playerInfo['id'] = $userPlateCard->id;
      $playerInfo['player_id'] = $plateCardInfo->player_id;
      $playerInfo['user_id'] = $userPlateCard->user_id;
      if (!is_null($userPlateCard->draftSeason)) {
        $playerInfo['draft_season'] = $userPlateCard->draftSeason->toArray();
      }
      foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
        $playerInfo[$field] = $plateCardInfo->{$field};
      }

      $playerInfo['match_name_eng'] = $plateCardInfo->match_name_eng;
      $playerInfo['position'] = $userPlateCard->position;
      $playerInfo['draft_shirt_number'] = $userPlateCard->draft_shirt_number;
      $playerInfo['is_mom'] = $userPlateCard->is_mom;
      $playerInfo['is_free'] = $userPlateCard->is_free;
      $playerInfo['card_grade'] = $userPlateCard->card_grade;
      $playerInfo['plate_card_id'] = $userPlateCard->plate_card_id;
      $playerInfo['ingame_fantasy_point'] = $userPlateCard->ingame_fantasy_point;
      $playerInfo['level_weight'] = $userPlateCard->level_weight;
      $playerInfo['draft_level'] = $userPlateCard->draft_level;
      $playerInfo['attacking_level'] = $userPlateCard->attacking_level;
      $playerInfo['goalkeeping_level'] = $userPlateCard->goalkeeping_level;
      $playerInfo['passing_level'] = $userPlateCard->passing_level;
      $playerInfo['defensive_level'] = $userPlateCard->defensive_level;
      $playerInfo['duels_level'] = $userPlateCard->duel_level;
      $playerInfo['special_skills'] = $userPlateCard->special_skills;
      $playerInfo['draft_schedule_round'] = $userPlateCard->draft_schedule_round;
      $playerInfo['headshot_path'] = $plateCardInfo->headshot_path;
      foreach (config('commonFields.team') as $field) {
        $team[$field] = $plateCardInfo->team->{$field};
      }
      $playerInfo['team'] = $team;

      if (!is_null($userPlateCard->draftTeam)) {
        $playerInfo['draft_team'] = $userPlateCard->draftTeam->toArray();
        $playerInfo['draft_team_names'] = $userPlateCard->draft_team_names;
      }
      $playerInfo['nation_code'] = RefCountryCode::whereHas('player', function ($query) use ($playerInfo) {
        $query->whereId($playerInfo['player_id']);
      })->value('alpha_3_code');

      $simulationInfo = $userPlateCard->simulationOverall;
      $playerInfo['final_overall'] = null;
      if ($simulationInfo) {
        $finalOverall = $simulationInfo->final_overall;
        $subPosition = $simulationInfo->sub_position;
        $playerInfo['final_overall'] = $finalOverall ? (int) $finalOverall[$subPosition] : null;
        $playerInfo['sub_position'] = $subPosition;
      }
    }

    return [$plateCardInfo, $playerInfo];
  }

  public function scheduleInfo($_scheduleId = null, $_condition = null, $sort = null)
  {
    return Schedule::with([
      'home:id,code,name,short_name',
      'away:id,code,name,short_name',
    ])
      ->has('home')
      ->has('away')
      ->when($_scheduleId, function ($query, $id) {
        $query->where('id', $id);
      }, function ($query) use ($_condition, $sort) {
        $query->where($_condition)
          ->orderBy($sort[0], $sort[1]);
      })
      ->limit(1)
      ->first();
  }
}
