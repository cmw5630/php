<?php

namespace App\Http\Controllers\API\v1;

use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\FantasyCalculator\FantasyDraftCategoryType;
use App\Enums\GameType;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\System\SocketChannelType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Game\CommentaryRequest;
use App\Http\Requests\Api\Game\LiveLineupDetailRequest;
use App\Http\Requests\Api\Game\LiveRequest;
use App\Http\Requests\Api\Game\LiveSideRequest;
use App\Http\Requests\Api\Game\MomentumRequest;
use App\Models\data\OptaTeamSeasonStat;
use ReturnData;
use Symfony\Component\HttpFoundation\Response;
use App\Libraries\Classes\Exception;
use App\Libraries\Classes\FantasyCalculator;
use App\Models\data\Commentary;
use App\Models\data\EventCard;
use App\Models\data\EventGoal;
use App\Models\data\MatchPreview;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\data\Substitution;
use App\Models\game\DailyStatTimeline;
use App\Models\game\FreeGameLineup;
use App\Models\game\Game;
use App\Models\game\GameJoin;
use App\Models\game\GameLineup;
use App\Models\game\PerScheduleTeam;
use App\Models\game\PerSeasonRound;
use App\Models\game\PerSeasonTeam;
use App\Models\game\PerSeason;
use App\Models\game\PlayerDailyStat;
use App\Models\meta\RefTeamAggregation;
use Illuminate\Contracts\Auth\Authenticatable;
use Str;

class LiveController extends Controller
{
  protected ?Authenticatable $user;

  public function __construct(?Authenticatable $_user)
  {
    $this->user = $_user;
  }

  private function currentRound($_seasonId)
  {
    $maxPlayedRound = Schedule::withUnrealSchedule()
      ->where([
        ['season_id', $_seasonId],
        ['status', ScheduleStatus::PLAYED]
      ])->max('round');

    if (is_null($maxPlayedRound)) {
      $leagueId = Season::whereId($_seasonId)->value('league_id');
      $beforeSeason = Season::getBeforeFuture([SeasonWhenType::BEFORE], $leagueId)[$leagueId]['before'][0];
      return ['id' => $beforeSeason['id'], 'name' => $beforeSeason['name']];
    } else {
      return Season::whereId($_seasonId)->select('id', 'name')->first()->toArray();
    }
  }

  /**
   * InGame : H2H-Main 
   */
  public function showHeadToHeadMain(LiveRequest $request)
  {
    $scheduleId = $request->only(['id']);
    try {
      $result = [];
      // 1-1. schedule 정보 : team_name, dateTime
      $scheduleInfo = Schedule::withUnrealSchedule()
        ->whereId($scheduleId)
        ->with([
          'home:' . implode(',', config('commonFields.team')),
          'away:' . implode(',', config('commonFields.team')),
        ])
        ->has('home')
        ->has('away')
        ->select('id', 'league_id', 'season_id', 'home_team_id', 'away_team_id', 'started_at', 'round', 'ga_round', 'status')
        ->first()->toArray();

      $homeTeamId = $scheduleInfo['home_team_id'];
      $awayTeamId = $scheduleInfo['away_team_id'];

      // teamHeadToHead Table
      $seasonId = $scheduleInfo['season_id'];
      $teamRank = OptaTeamSeasonStat::whereIn('team_id', [$homeTeamId, $awayTeamId])
        ->where('season_id', $seasonId)
        ->select('rank', 'team_id')
        ->get()
        ->keyBy('team_id');

      $scheduleInfo['home']['rank'] = $teamRank[$homeTeamId]['rank'];
      $scheduleInfo['away']['rank'] = $teamRank[$awayTeamId]['rank'];

      $teamAggregation = RefTeamAggregation::where('season_id', $seasonId)
        ->get()
        ->keyBy('team_id')
        ->toArray();

      if (!empty($teamAggregation)) {
        $scheduleInfo['home']['avg_plus_goals'] = $teamAggregation[$homeTeamId]['avg_plus_goals'];
        $scheduleInfo['home']['avg_minus_goals'] = $teamAggregation[$homeTeamId]['avg_minus_goals'];
        $scheduleInfo['away']['avg_plus_goals'] = $teamAggregation[$awayTeamId]['avg_plus_goals'];
        $scheduleInfo['away']['avg_minus_goals'] = $teamAggregation[$awayTeamId]['avg_minus_goals'];

        // 1-3. 두팀의  전적 -> parser
        $scheduleInfo['home']['season_record'] = ['win' => $teamAggregation[$homeTeamId]['win_count'], 'lose' => $teamAggregation[$homeTeamId]['lose_count'], 'draw' => $teamAggregation[$homeTeamId]['draw_count']];
        $scheduleInfo['away']['season_record'] = ['win' => $teamAggregation[$awayTeamId]['win_count'], 'lose' => $teamAggregation[$awayTeamId]['lose_count'], 'draw' => $teamAggregation[$awayTeamId]['draw_count']];
      } else {
        $beForeseasonId = Season::getBeforeFuture([SeasonWhenType::BEFORE], $scheduleInfo['league_id'])[$scheduleInfo['league_id']]['before'][0]['id'];
        $teamAggregation = RefTeamAggregation::where('season_id', $beForeseasonId)
          ->get()
          ->keyBy('team_id')
          ->toArray();
      }

      // season_record 의 season 정보
      $scheduleInfo['season'] = $this->currentRound($scheduleInfo['season_id']);

      $scheduleInfo['home']['all_record'] = ['win' => 0, 'lose' => 0, 'draw' => 0];
      $scheduleInfo['away']['all_record'] = ['win' => 0, 'lose' => 0, 'draw' => 0];

      $matchPreview = MatchPreview::where([
        ['home_team_id', $homeTeamId],
        ['away_team_id', $awayTeamId]
      ])->select('home_team_wins', 'away_team_wins', 'draws')->first();
      if (!is_null($matchPreview)) {
        $matchPreview = $matchPreview->toArray();
        $scheduleInfo['home']['all_record'] = ['win' => $matchPreview['home_team_wins'], 'lose' => $matchPreview['away_team_wins'], 'draw' => $matchPreview['draws']];
        $scheduleInfo['away']['all_record'] = ['win' => $matchPreview['away_team_wins'], 'lose' => $matchPreview['home_team_wins'], 'draw' => $matchPreview['draws']];
      }

      $result['schedule_info'] = $scheduleInfo;

      // 2-1. 현시즌 최근 5경기의 승무패 -> 1round 인 경우 이전시즌
      $recentMatch['home'] = json_decode($teamAggregation[$homeTeamId]['recent_5_match']);
      $recentMatch['away'] = json_decode($teamAggregation[$awayTeamId]['recent_5_match']);

      if (isset($recentMatch)) {
        $result['recent_match'] = $recentMatch;
      }

      // 3-1. 두 팀이 만난 최근 5경기
      $result['match_preview_last5'] = Schedule::where(function ($query) use ($homeTeamId, $awayTeamId) {
        $query->where([
          ['home_team_id', $homeTeamId],
          ['away_team_id', $awayTeamId]
        ])->orWhere([
          ['home_team_id', $awayTeamId],
          ['away_team_id', $homeTeamId]
        ]);
      })
        ->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
        ->with([
          'home:' . implode(',', config('commonFields.team')),
          'away:' . implode(',', config('commonFields.team')),
        ])
        ->has('home')
        ->has('away')
        ->select('id', 'home_team_id', 'away_team_id', 'score_home', 'score_away', 'started_at', 'winner', 'status')
        ->orderByDesc('started_at')
        ->limit(5)
        ->get()->toArray();

      // $result['wing'] = $this->headToHeadWing($scheduleInfo);

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  /**
   * InGame : H2H-Main-Stats
   * */
  // 3-2. 해당 경기에서 뛴 (fp > 0) 모든 선수들의 name, position, goals, goal_assist, fp
  public function matchPreviewLast5(LiveRequest $request)
  {
    try {
      $scheduleId = $request->only(['id']);
      $players = [];
      OptaPlayerDailyStat::where([
        ['schedule_id', $scheduleId],
        ['fantasy_point', '>', 0]
      ])
        ->get()
        ->sortBy('plateCardWithTrashed.first_name')
        ->sortByDesc('goal_assist')
        ->sortByDesc('goals')
        ->sortByDesc('fantasy_point')
        ->map(function ($info) use (&$players) {
          if (!is_null($info->plateCardWithTrashed)) {
            $player['id'] = $info->player_id;
            foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
              $player[$field] = $info->plateCardWithTrashed->{$field};
            }
            $player['position'] = $info->summary_position;
            $player['goals'] = $info->goals;
            $player['goal_assist'] = $info->goal_assist;
            $player['fantasy_point'] = $info->fantasy_point;

            $players[$info->team_id][] = $player;
          }
        });
      return ReturnData::setData($players)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  /**
   * InGame : H2H-Side 
   * 팀별 시즌 평균데이터 (home/away 두세트로 구성)
   * 1.팀참여한 시즌의 게임리스트 / 2.포지션별 시즌평균 / 3.시즌평균 선수데이터
   * */
  public function showHeadToHeadSide(LiveRequest $request)
  {
    try {
      $result = [];
      $input = $request->only([
        'id'
      ]);
      /* ---------------------------------------------------------------------------           
       * [1]. 경기 팀정보 조회 (home_team_id,away_team_id)
      ------------------------------------------------------------------------------ */
      $teamIds = Schedule::withUnrealSchedule()->whereId($input['id'])->select('home_team_id', 'away_team_id')->first()->toArray();
      $result['teamIds'] = $teamIds;

      /* ---------------------------------------------------------------------------           
       * [2]. 시즌 정보 조회 > 게임의 시즌기준적용
       * ( 해당시즌 진행되는 라운드없을경우 이전 시즌정보 조회 )
      ------------------------------------------------------------------------------ */
      $currentSeasonId = $this->currentRound(Schedule::withUnrealSchedule()->whereId($input['id'])->value('season_id'));
      $result['currentSeasonId'] = $currentSeasonId;

      ### home/away 세트로 구성 
      if ($teamIds) {
        foreach ($teamIds as  $key => $value) {
          $keyName = substr($key, 0, 4);
          /* ---------------------------------------------------------------------------           
          * 2-1. 경기 스케쥴 리스트 :시즌ID + 팀ID 기준 Data  
          ------------------------------------------------------------------------------ */
          // 3-1. 두 팀이 만난 최근 5경기
          $allSchedules = Schedule::where(function ($query) use ($value) {
            $query->where('home_team_id', $value)
              ->orWhere('away_team_id', $value);
          })
            ->where('season_id', $currentSeasonId)
            ->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
            ->with([
              'home:' . implode(',', config('commonFields.team')),
              'away:' . implode(',', config('commonFields.team')),
              'season:id,name'
            ])
            ->has('home')
            ->has('away')
            ->orderByDesc('round')
            ->get()->toArray();
          $result[$keyName]['schedules'] =  $allSchedules;

          /* ---------------------------------------------------------------------------           
         * [3]. 그래프 수치정보 (시즌 기준)
         * 3-1. 막대그래프 : 시즌 해당팀의 포지션별 전체평균 데이터 (per 90)    
         *      같은 시즌내 h2h조회시 최신데이터로 갱신필요, 해당시즌 종료된 전체경기라운드 데이터 
         * 3-2. 꺽은선그래프 : 시즌 전체팀의 포지션별평균 데이터  
         * 3-3. Max값 - 막대/꺽은선 해당포지션별 높은값으로 구성 
        ------------------------------------------------------------------------------ */
          $graphs = [];

          // 꺽은선 > 전체 
          $arrWhereLines = ['season_id' => $currentSeasonId];
          $linesProps = $this->callGraphsStats(PerSeason::class, $arrWhereLines);
          $graphs['lines'] = $linesProps;

          // 막대 > 팀 경기의 라운드정보 조회 
          $arrWhereBars = ['season_id' => $currentSeasonId, 'team_id' => $value];
          $barsProps = $this->callGraphsStats(PerSeasonTeam::class, $arrWhereBars);
          $graphs['bars'] = $barsProps;

          $maxs = []; // MAX값 
          $maxs = $graphs['bars']; // 그래프 Max값 세팅         
          foreach ($graphs['bars'] as $keys => $values) {
            if ($values < $graphs['lines'][$keys]) {
              $maxs[$keys] = $graphs['lines'][$keys];
            }
          }
          $graphs['maxs'] = $maxs;
          $result[$keyName]['graphs'] =  $graphs;

          /* ---------------------------------------------------------------------------           
         * [4].선수 경기정보 : 해당시즌 - fantasy point 조건없이 전달
        ------------------------------------------------------------------------------ */
          $players = [];
          OptaPlayerDailyStat::where([
            ['season_id', $currentSeasonId],
            ['team_id', $value]
          ])->with(['season', 'team', 'plateCardWithTrashed'])
            ->gameParticipantPlayer()
            ->selectRaw('player_id, SUM(goals) AS goals,SUM(goal_assist) AS goal_assist, AVG(fantasy_point) AS fantasy_point ')
            ->groupBy('player_id')
            ->get()
            ->sortBy('plateCardWithTrashed.first_name')
            ->sortByDesc('goal_assist')
            ->sortByDesc('goals')
            ->sortByDesc('fantasy_point')
            ->map(function ($info) use (&$players) {
              if (!is_null($info->plateCardWithTrashed)) {
                $player['id'] = $info->player_id;
                foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
                  $player[$field] = $info->plateCardWithTrashed->{$field};
                }
                $player['position'] = $info->plateCardWithTrashed->position;
                $player['goals'] = (int)$info->goals;
                $player['goal_assist'] = (int)$info->goal_assist;
                $player['fantasy_point'] = $info->fantasy_point;
                $players[] = $player;
              }
            });
          $result[$keyName]['players'] = $players;
        }
      }

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }


  /**
   * InGame : H2H-Side-Stats
   * 팀의 스케쥴에서 게임선택시 특정게임 데이터 노출 
   * */
  public function showHeadToHeadSideStats(LiveSideRequest $request)
  {
    try {
      $result = [];
      $input = $request->only([
        'id',
        'team_id'
      ]);

      /* ---------------------------------------------------------------------------           
       * [1].경기의 라운드정보 조회 
       ----------------------------------------------------------------------------- */
      $teamIds = Schedule::withUnrealSchedule()->whereId($input['id'])->select('round', 'league_id')->first()->toArray();
      $teamRounds = $teamIds['round'];
      /* ---------------------------------------------------------------------------           
       * [2].시즌 정보 조회 
       * ( 해당시즌 진행되는 라운드없을경우 이전시즌정보 조회 )
      ------------------------------------------------------------------------------ */
      $currentSeason = $this->currentRound(Schedule::withUnrealSchedule()->whereId($input['id'])->value('season_id'));
      $result['current_season'] = $currentSeason;
      $seasonId = $currentSeason['id'];
      // if ($teamRounds === 1) {
      //   $seasonId = Season::getBeforeFuture([SeasonWhenType::BEFORE], $teamIds['league_id'])[$teamIds['league_id']]['before'][0]['id'];
      // }
      /* ---------------------------------------------------------------------------           
        * [3].그래프 수치정보 (시즌기준)
        * 3-1. 막대그래프 : 시즌 해당팀의 포지션별 전체평균 데이터 (per 90)    
        * 3-2. 꺽은선그래프 : 시즌 전체팀의 포지션별평균 데이터  
        * 3-3. Max값 - 막대/꺽은선 해당포지션별 높은값으로 구성 
      ------------------------------------------------------------------------------ */
      $graphs = [];
      $maxs = []; // MAX값 

      // 꺽은선 > 전체 
      $arrWhereLines = ['season_id' => $seasonId, 'round' => $teamRounds];
      $linesProps = $this->callGraphsStats(PerSeasonRound::class, $arrWhereLines);
      $graphs['lines'] = $linesProps;

      // 막대 > 팀 경기의 라운드정보 조회 
      $arrWhereBars = ['season_id' => $seasonId, 'round' => $teamRounds, 'team_id' => $input['team_id']];
      $barsProps = $this->callGraphsStats(PerScheduleTeam::class, $arrWhereBars);
      $graphs['bars'] = $barsProps;

      $maxs = $graphs['bars']; // 그래프 Max값 세팅         
      foreach ($graphs['bars'] as $key => $value) {
        if ($value < $graphs['lines'][$key]) {
          $maxs[$key] = $graphs['lines'][$key];
        }
      }
      $graphs['maxs'] = $maxs;
      $result['graphs'] = $graphs;

      /* ---------------------------------------------------------------------------           
       * 4].선수 경기정보 : 특정게임 - fantasy point 조건없이 전달
      ------------------------------------------------------------------------------ */
      $players = [];
      OptaPlayerDailyStat::where([
        ['season_id', $seasonId],
        ['team_id', $input['team_id']],
        ['schedule_id', $input['id']]
      ])->with(['season', 'team', 'plateCardWithTrashed'])
        ->gameParticipantPlayer()
        ->selectRaw('player_id, SUM(goals) AS goals, SUM(goal_assist) AS goal_assist, AVG(fantasy_point) AS fantasy_point ')
        ->groupBy('player_id')
        ->get()
        ->sortBy('plateCardWithTrashed.first_name')
        ->sortByDesc('goal_assist')
        ->sortByDesc('goals')
        ->sortByDesc('fantasy_point')
        ->map(function ($info) use (&$players) {
          if (!is_null($info->plateCardWithTrashed)) {
            $player['id'] = $info->player_id;
            foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
              $player[$field] = $info->plateCardWithTrashed->{$field};
            }
            $player['position'] = $info->plateCardWithTrashed->position;
            $player['goals'] = (int)$info->goals;
            $player['goal_assist'] = (int)$info->goal_assist;
            $player['fantasy_point'] = $info->fantasy_point;
            $players[] = $player;
          }
        });
      $result['players'] = $players;
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  /**
   * Graphs - model별 포지션별 데이터    
   * model , where 
   * */
  private function callGraphsStats($model, $arrWhere)
  {
    $props = [];
    $arrWhereSql = [];
    if (is_array($arrWhere)) {
      foreach ($arrWhere as $key => $value) {
        array_push($arrWhereSql, [$key, $value]);
      };
    }
    $model::where($arrWhereSql)
      ->select('position', 'per_fp')
      ->orderByDesc('position')
      ->get()
      ->map(function ($info) use (&$props) {
        if (!is_null($info->position)) {
          $props[$info->position] = $info->per_fp;
        }
      });
    return $props;
  }

  public function showLiveLineup(LiveRequest $request)
  {
    $scheduleId = $request->only(['id']);
    try {
      $result = [
        'type' => 'formation'
      ];

      $homeSubCount = 0;
      $awaySubCount = 0;
      Schedule::withUnrealSchedule()->whereId($scheduleId)
        ->withWhereHas('optaPlayerDailyStat', function ($query) {
          $query
            // ->gameParticipantPlayer()
            ->with('plateCardWithTrashed');
        })
        ->has('gamePossibleSchedule')
        ->get()
        ->map(function ($info) use (&$result, &$homeSubCount, &$awaySubCount) {
          if (!isset($result['league_id'])) {
            $result['league_id'] = $info->league_id;
            $result['season_id'] = $info->season_id;
            // $result['game_id'] = $info->gameSchedule[0]->game_id; // 우선 주석처리
            $result['schedule_id'] = $info->id;
          }
          $schedule['id'] = $info->id;
          $schedule['home_team_id'] = $info->home_team_id;
          $schedule['away_team_id'] = $info->away_team_id;
          $schedule['home_formation_used'] = $info->home_formation_used;
          $schedule['away_formation_used'] = $info->away_formation_used;
          $schedule['started_at'] = $info->started_at;
          $schedule['status'] = $info->status;
          $schedule['score_home'] = $info->score_home;
          $schedule['score_away'] = $info->score_away;
          $schedule['period_id'] = $info->period_id;
          $schedule['match_length_min'] = $info->match_length_min;
          $schedule['match_length_sec'] = $info->match_length_sec;

          foreach (config('commonFields.team') as $field) {
            $schedule['home'][$field] = $info->home->{$field};
            $schedule['away'][$field] = $info->away->{$field};
          }
          $result['data_updated']['schedule'] = $schedule;

          $result['data_updated']['home'] = [];
          $result['data_updated']['away'] = [];

          $goalInfo = EventGoal::where('schedule_id', $info->id)->get()->groupBy('scorer_id')->toArray();
          $cardInfo = EventCard::where('schedule_id', $info->id)->get()->groupBy('player_id')->toArray();

          $inSubInfo = [];
          $outSubInfo = [];
          Substitution::whereNotNull('slot')
            ->where('schedule_id', $info->id)
            ->get()->map(function ($item) use (&$inSubInfo, &$outSubInfo) {
              $inSubInfo[$item['player_on_id']] = $item->toArray();
              $outSubInfo[$item['player_off_id']] = $item->toArray();
            });


          foreach ($info->optaPlayerDailyStat as $dailyStat) {
            $player = [];
            $player['is_mom'] = $dailyStat->is_mom;
            $player['player_id'] = $dailyStat->player_id;
            if ($dailyStat->plateCardWithTrashed === null) {
              continue;
            }
            $player['plate_card_id'] = $dailyStat->plateCardWithTrashed->id;

            foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
              $player[$field] = $dailyStat->plateCardWithTrashed->{$field};
            }

            $player['formation_place'] = $dailyStat->formation_place;
            $player['position'] = $dailyStat->summary_position;
            // 실제 경기에서 뛰었던 모든 position
            $player['played_position'] = PlayerDailyStat::whereHas('season', function ($query) {
              $query->currentSeasons();
            })
              ->where('player_id', $dailyStat->player_id)
              ->selectRaw('DISTINCT(summary_position)')->get()->pluck('summary_position');

            $player['headshot_path'] = $dailyStat->plateCardWithTrashed->headshot_path;
            $player['fantasy_point'] = $dailyStat->fantasy_point;
            $player['goals'] = $dailyStat->goals;
            $player['own_goals'] = $dailyStat->own_goals;
            $player['goal_assist'] = $dailyStat->goal_assist;
            $player['yellow_card'] = $dailyStat->yellow_card;
            $player['red_card'] = $dailyStat->red_card;

            $player['game_started'] = false;
            $player['total_sub_on'] = false;
            if ($dailyStat->game_started) $player['game_started'] = true;
            if ($dailyStat->total_sub_on) $player['total_sub_on'] = true;

            // substitution 체크
            $player['substitution'] = null;

            $targetPlayerId = $player['player_id'];

            if (isset($inSubInfo[$targetPlayerId])) {
              if ($inSubInfo[$targetPlayerId]['team_id'] === $info->home_team_id) $homeSubCount++;
              if ($inSubInfo[$targetPlayerId]['team_id'] === $info->away_team_id) $awaySubCount++;

              $player['substitution'][$inSubInfo[$targetPlayerId]['slot']]['out']['period_id'] = $inSubInfo[$targetPlayerId]['period_id'];
              $player['substitution'][$inSubInfo[$targetPlayerId]['slot']]['out']['time'] = $inSubInfo[$targetPlayerId]['time_min'];;
              $player['substitution'][$inSubInfo[$targetPlayerId]['slot']]['out']['player_id'] = $inSubInfo[$targetPlayerId]['player_off_id'];
            }

            if (isset($outSubInfo[$targetPlayerId])) {
              $player['substitution'][$outSubInfo[$targetPlayerId]['slot']]['in']['period_id'] = $outSubInfo[$targetPlayerId]['period_id'];
              $player['substitution'][$outSubInfo[$targetPlayerId]['slot']]['in']['time'] = $outSubInfo[$targetPlayerId]['time_min'];
              $player['substitution'][$outSubInfo[$targetPlayerId]['slot']]['in']['player_id'] = $outSubInfo[$targetPlayerId]['player_on_id'];
            }

            if (isset($inSubInfo[$targetPlayerId]) && !isset($player['substitution'][$inSubInfo[$targetPlayerId]['slot']]['in'])) {
              $player['substitution'][$inSubInfo[$targetPlayerId]['slot']]['in'] = null;
            }
            if (isset($outSubInfo[$targetPlayerId]) && !isset($player['substitution'][$outSubInfo[$targetPlayerId]['slot']]['out'])) {
              $player['substitution'][$outSubInfo[$targetPlayerId]['slot']]['out'] = null;
            }

            // goal 체크
            if (isset($goalInfo[$targetPlayerId])) {
              foreach ($goalInfo[$targetPlayerId] as $goalOneItem) {
                $player['goal'][$goalOneItem['slot']]['period_id'] = $goalOneItem['period_id'];
                $player['goal'][$goalOneItem['slot']]['player_id'] = $goalOneItem['scorer_id'];
                $player['goal'][$goalOneItem['slot']]['assist_player_id'] = $goalOneItem['assist_player_id'];
                $player['goal'][$goalOneItem['slot']]['time'] = $goalOneItem['time_min'];
                $player['goal'][$goalOneItem['slot']]['type'] = $goalOneItem['type'];
              }
            }

            // card  체크
            if (isset($cardInfo[$targetPlayerId])) {
              foreach ($cardInfo[$targetPlayerId] as $cardOneItem) {
                $player['card'][$cardOneItem['slot']]['period_id'] = $cardOneItem['period_id'];
                $player['card'][$cardOneItem['slot']]['player_id'] = $cardOneItem['player_id'];
                $player['card'][$cardOneItem['slot']]['time'] = $cardOneItem['time_min'];
                $player['card'][$cardOneItem['slot']]['type'] = $cardOneItem['type'];
              }
            }

            if ($dailyStat->team_id === $info->home_team_id) {
              $result['data_updated']['home'][$dailyStat->player_id] = $player;
            } else if ($dailyStat->team_id === $info->away_team_id) {
              $result['data_updated']['away'][$dailyStat->player_id] = $player;
            }

            $result['data_updated']['home_subs_info']['count'] = $homeSubCount;
            $result['data_updated']['away_subs_info']['count'] = $awaySubCount;
          }

          return $result;
        });

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function getTimeline(LiveRequest $_request)
  {
    $scheduleId = $_request->only(['id']);
    try {
      $result = [
        'type' => SocketChannelType::TIMELINE,
        'schedule_id' => $scheduleId,
        'data_updated' => [],
      ];
      // substitution
      $homeSubCount = 0;
      $awaySubCount = 0;
      $subs = Substitution::withWhereHas('schedule', function ($query) {
        $query->withUnrealSchedule();
      })->where('schedule_id', $scheduleId)->get()
        ->map(function ($subsOne) use (&$result, &$homeSubCount, &$awaySubCount) {
          $teamId = $subsOne->team_id;
          $teamSide = null;
          foreach (['home', 'away'] as $anyTeamSide) {
            if ($teamId === $subsOne->schedule->{$anyTeamSide . '_' . 'team_id'}) {
              $teamSide = $anyTeamSide;
              break;
            }
          }
          ${$teamSide . 'SubCount'}++;

          $result['data_updated']['substitution'][$teamSide][$subsOne->slot]['period_id'] = $subsOne->period_id;
          $result['data_updated']['substitution'][$teamSide][$subsOne->slot]['time_min'] = $subsOne->time_min;
          $result['data_updated']['substitution'][$teamSide][$subsOne->slot]['out'] = $subsOne->player_off_id;
          $result['data_updated']['substitution'][$teamSide][$subsOne->slot]['in'] = $subsOne->player_on_id;
        });

      $result['data_updated']['substitution']['home_subs_info']['count'] = $homeSubCount;
      $result['data_updated']['substitution']['away_subs_info']['count'] = $awaySubCount;

      // goal
      EventGoal::withWhereHas('schedule', function ($query) {
        $query->withUnrealSchedule();
      })->where('schedule_id', $scheduleId)->get()
        ->map(function ($goalOne) use (&$result) {
          $teamId = $goalOne->team_id;
          $teamSide = null;
          foreach (['home', 'away'] as $anyTeamSide) {
            if ($teamId === $goalOne->schedule->{$anyTeamSide . '_' . 'team_id'}) {
              $teamSide = $anyTeamSide;
              break;
            }
          }
          $result['data_updated']['goal'][$teamSide][$goalOne->slot]['period_id'] = $goalOne->period_id;
          $result['data_updated']['goal'][$teamSide][$goalOne->slot]['time_min'] = $goalOne->time_min;
          $result['data_updated']['goal'][$teamSide][$goalOne->slot]['player_id'] = $goalOne->scorer_id;
          $result['data_updated']['goal'][$teamSide][$goalOne->slot]['type'] = $goalOne->type;
          $result['data_updated']['goal'][$teamSide][$goalOne->slot]['assist_player_id'] = $goalOne->assist_player_id;
          $result['data_updated']['goal'][$teamSide][$goalOne->slot]['opta_event_id'] = $goalOne->opta_event_id;
        });

      // card
      EventCard::withWhereHas('schedule', function ($query) {
        $query->withUnrealSchedule();
      })->where('schedule_id', $scheduleId)->get()
        ->map(function ($cardOne) use (&$result) {
          $teamId = $cardOne->team_id;
          $teamSide = null;
          foreach (['home', 'away'] as $anyTeamSide) {
            if ($teamId === $cardOne->schedule->{$anyTeamSide . '_' . 'team_id'}) {
              $teamSide = $anyTeamSide;
              break;
            }
          }
          $result['data_updated']['card'][$teamSide][$cardOne->slot]['period_id'] = $cardOne->period_id;
          $result['data_updated']['card'][$teamSide][$cardOne->slot]['time_min'] = $cardOne->time_min;
          $result['data_updated']['card'][$teamSide][$cardOne->slot]['player_id'] = $cardOne->player_id;
          $result['data_updated']['card'][$teamSide][$cardOne->slot]['type'] = $cardOne->type;
          $result['data_updated']['card'][$teamSide][$cardOne->slot]['opta_event_id'] = $cardOne->opta_event_id;
        });

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }


  public function getUserRanking(LiveRequest $request)
  {
    $gameId = (int)$request->only(['id'])['id'];
    try {
      $result = [
        'type' => 'user_rank',
        'game_id' => $gameId,
      ];

      GameJoin::with(['user' => function ($query) {
        $query->withoutGlobalScope('excludeWithdraw');
      }])->where('game_id', $gameId)
        ->orderBy('ranking')
        ->orderBy('user_name')
        ->limit(20)
        ->get()
        ->map(function ($info) use (&$result, $request) {
          $user['game_join_id'] = $info->id;
          $user['user_id'] = $info->user_id;
          $user['user_name'] = $info->user->name;
          $user['user_photo_path'] = $info->user->userMeta->photo_path;
          $user['point'] = $info->point;
          $user['ranking'] = $info->ranking;
          $user['formation'] = $info->formation;
          $user['reward'] = $info->reward;
          $user['mine'] = false;
          if ($info->user_id === $request->user()->id) {
            $user['mine'] = true;
          }

          $result['data_updated'][$info->user_id] = $user;
          return $result;
        });

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function getMyRanking(LiveRequest $request)
  {
    $gameId = (int)$request->only(['id'])['id'];
    try {
      $result = [
        'type' => 'personal_rank',
        'game_id' => $gameId,
        'user_id' => $request->user()->id,
        'user_name' => $request->user()->name
      ];

      $result['data_updated'] = GameJoin::whereHas('user', function ($query) {
        $query->withoutGlobalScope('excludeWithdraw');
      })->where([
        ['game_id', $gameId],
        ['user_id', $request->user()->id]
      ])->selectRaw('id AS game_join_id ,ranking, point')
        ->first()
        ->toArray();
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function getUserLineup(LiveRequest $request)
  {
    $gameJoinId = $request->only(['id']);
    try {
      $result = ['type' => 'user_lineup'];

      $gameJoinInfo = GameJoin::with('game:id,mode')
        ->where('id', $gameJoinId)
        ->first();

      $gameType = $gameJoinInfo->game->mode;
      $myFormation = $gameJoinInfo->formation;

      if ($gameType === GameType::NORMAL || $gameType === GameType::TEST) {
        GameLineup::where('game_join_id', $gameJoinId)
          ->with([
            'plateCardWithTrashed',
            'gameJoin',
            'userPlateCard.draftSeason.league' => function ($query) {
              $query->withoutGlobalScope('serviceLeague');
            }
          ])->withWhereHas('schedule', function ($query) {
            $query->withUnrealSchedule();
          })
          ->withWhereHas('userPlateCard', function ($query) {
            $query->withoutGlobalScope('excludeBurned');
          })
          ->orderByRaw("FIELD(place_index," . implode(',', config('formation-by-position.lineup_formation')[$myFormation]) . ")")
          ->get()
          ->map(function ($info) use (&$result) {
            $result['game_join_id'] = $info->game_join_id;
            $result['user_id'] = $info->gameJoin->user_id;
            $result['user_name'] = $info->gameJoin->user->name;
            $result['formation'] = $info->gameJoin->formation;

            $lineup['user_plate_card_id'] = $info->user_plate_card_id;
            $lineup['m_fantasy_point'] = (float)$info->m_fantasy_point;
            $lineup['player_id'] = $info->player_id;
            $lineup['schedule_id'] = $info->schedule_id;
            $lineup['place_index'] = $info->place_index;
            foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
              $lineup[$field] = $info->plateCardWithTrashed->{$field};
            }
            $lineup['player_name'] = $info->plateCardWithTrashed->player_name;
            $lineup['short_player_name'] = $info->plateCardWithTrashed->short_player_name;
            $lineup['position'] = $info->userPlateCard->position;
            $lineup['league_code'] = $info->userPlateCard->draftSeason->league->league_code;
            $lineup['nation_code'] = $info->plateCardWithTrashed->nationality_code;
            $lineup['draft_level'] = $info->userPlateCard->draft_level;
            $lineup['card_grade'] = $info->userPlateCard->card_grade;
            $lineup['headshot_path'] = $info->plateCardWithTrashed->headshot_path;
            $lineup['is_participation'] = false;

            foreach (config('commonFields.team') as $field) {
              $team[$field] =  $info->plateCardWithTrashed->team->{$field};
            }
            $lineup['team'] = $team;

            $stat = OptaPlayerDailyStat::where([['schedule_id', $info->schedule_id], ['player_id', $info->player_id]])->first();

            $lineup['status'] = $info->schedule->status;

            if (in_array($info->schedule->status, [ScheduleStatus::PLAYING, ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])) {
              if ($stat?->game_started || $stat?->total_sub_on) {
                $lineup['is_participation'] = true;
              }
              $lineup['core_stats']['goals'] = $stat?->goals;
              $lineup['core_stats']['goal_assist'] = $stat?->goal_assist;
              $lineup['core_stats']['own_goals'] = $stat?->own_goals;
              $lineup['core_stats']['yellow_card'] = $stat?->yellow_card;
              $lineup['core_stats']['red_card'] = $stat?->red_card;
            } else {
              $lineup['core_stats']['started_at'] = $info->schedule->started_at;
            }

            // substitution 체크
            $lineup['substitution'] = null;
            $targetPlayerId = $info->player_id;

            $inSubInfo = [];
            $outSubInfo = [];
            Substitution::with([
              'onPlateCardWithTrashed:player_id,match_name',
              'offPlateCardWithTrashed:player_id,match_name',
            ])
              ->whereNotNull('slot')
              ->where('schedule_id', $info->schedule_id)
              ->where(function ($query) use ($targetPlayerId) {
                $query->where('player_on_id', $targetPlayerId)
                  ->orWhere('player_off_id', $targetPlayerId);
              })
              ->get()->map(function ($item) use (&$inSubInfo, &$outSubInfo) {
                $inSubInfo[$item['player_on_id']] = $outSubInfo[$item['player_off_id']] = $item->toArray();
              });

            if (isset($inSubInfo[$targetPlayerId])) {
              $lineup['substitution'][$inSubInfo[$targetPlayerId]['slot']]['out']['period_id'] = $inSubInfo[$targetPlayerId]['period_id'];
              $lineup['substitution'][$inSubInfo[$targetPlayerId]['slot']]['out']['time'] = $inSubInfo[$targetPlayerId]['time_min'];
              $lineup['substitution'][$inSubInfo[$targetPlayerId]['slot']]['out']['player_id'] = $inSubInfo[$targetPlayerId]['player_off_id'];
              $lineup['substitution'][$inSubInfo[$targetPlayerId]['slot']]['out']['player_name'] = $inSubInfo[$targetPlayerId]['off_plate_card_with_trashed']['match_name'];
            }

            if (isset($outSubInfo[$targetPlayerId])) {
              $lineup['substitution'][$outSubInfo[$targetPlayerId]['slot']]['in']['period_id'] = $outSubInfo[$targetPlayerId]['period_id'];
              $lineup['substitution'][$outSubInfo[$targetPlayerId]['slot']]['in']['time'] = $outSubInfo[$targetPlayerId]['time_min'];
              $lineup['substitution'][$outSubInfo[$targetPlayerId]['slot']]['in']['player_id'] = $outSubInfo[$targetPlayerId]['player_on_id'];
              $lineup['substitution'][$outSubInfo[$targetPlayerId]['slot']]['in']['player_name'] = $outSubInfo[$targetPlayerId]['on_plate_card_with_trashed']['match_name'];
            }

            if (isset($inSubInfo[$targetPlayerId]) && !isset($lineup['substitution'][$inSubInfo[$targetPlayerId]['slot']]['in'])) {
              $lineup['substitution'][$inSubInfo[$targetPlayerId]['slot']]['in'] = null;
            }
            if (isset($outSubInfo[$targetPlayerId]) && !isset($lineup['substitution'][$outSubInfo[$targetPlayerId]['slot']]['out'])) {
              $lineup['substitution'][$outSubInfo[$targetPlayerId]['slot']]['out'] = null;
            }

            $result['data_updated'][$info->player_id] = $lineup;
          });
      } else if (in_array($gameType, [GameType::FREE, GameType::SPONSOR])) {
        FreeGameLineup::where('game_join_id', $gameJoinId)
          ->with(['plateCardWithTrashed.league', 'gameJoin'])
          ->withWhereHas('schedule', function ($query) {
            $query->withUnrealSchedule();
          })
          ->orderByRaw("FIELD(formation_place," . implode(',', config('formation-by-position.lineup_formation')[$myFormation]) . ")")
          ->get()
          ->map(function ($info) use (&$result) {
            $result['game_join_id'] = $info->game_join_id;
            $result['user_id'] = $info->gameJoin->user_id;
            $result['user_name'] = $info->gameJoin->user->name;
            $result['formation'] = $info->gameJoin->formation;

            // $lineup['user_plate_card_id'] = $info->user_plate_card_id;
            $lineup['m_fantasy_point'] = (float)$info->m_fantasy_point;
            $lineup['player_id'] = $info->player_id;
            $lineup['schedule_id'] = $info->schedule_id;
            $lineup['place_index'] = $info->formation_place;
            foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
              $lineup[$field] = $info->plateCardWithTrashed->{$field};
            }
            // $lineup['player_name'] = $info->plateCardWithTrashed->player_name;
            // $lineup['short_player_name'] = $info->plateCardWithTrashed->short_player_name;
            $lineup['position'] = $info->position;

            if (!is_null($info->final_overall)) {
              $lineup['final_overall'] = $info->final_overall[$info->sub_position];
              $lineup['sub_position'] = $info->sub_position;
            } else {
              $playerOverall = $info->plateCardWithTrashed->currentRefPlayerOverall;
              $lineup['final_overall'] = $playerOverall?->final_overall ?? 45;
              $lineup['sub_position'] = $playerOverall?->sub_position ?? 'st';
            }

            $lineup['draft_level'] = $info->draft_level;
            $lineup['card_grade'] = $info->card_grade;
            $lineup['headshot_path'] = $info->plateCardWithTrashed->headshot_path;
            $lineup['is_participation'] = false;

            $lineup['special_skills'] = $info->special_skills;
            foreach (FantasyDraftCategoryType::getValues() as $category) {
              if (isset($info->{$category . '_level'})) {
                $lineup[$category . '_level'] = $info->{$category . '_level'};
              }
            }

            $lineup['shirt_number'] = $info->plateCardWithTrashed->shirt_number;
            $lineup['league_code'] = $info->plateCardWithTrashed->league_code;
            $lineup['nationality_code'] = $info->plateCardWithTrashed->nationality_code;

            foreach (config('commonFields.team') as $field) {
              $team[$field] =  $info->plateCardWithTrashed->team->{$field};
            }
            $lineup['team'] = $team;

            $stat = OptaPlayerDailyStat::where([['schedule_id', $info->schedule_id], ['player_id', $info->player_id]])->first();

            $lineup['status'] = $info->schedule->status;

            if (in_array($info->schedule->status, [ScheduleStatus::PLAYING, ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])) {
              if ($stat?->game_started || $stat?->total_sub_on) {
                $lineup['is_participation'] = true;
              }
              $lineup['core_stats']['goals'] = $stat?->goals;
              $lineup['core_stats']['goal_assist'] = $stat?->goal_assist;
              $lineup['core_stats']['own_goals'] = $stat?->own_goals;
              $lineup['core_stats']['yellow_card'] = $stat?->yellow_card;
              $lineup['core_stats']['red_card'] = $stat?->red_card;
            } else {
              $lineup['core_stats']['started_at'] = $info->schedule->started_at;
            }

            // substitution 체크
            $lineup['substitution'] = null;
            $targetPlayerId = $info->player_id;

            $inSubInfo = [];
            $outSubInfo = [];
            Substitution::with([
              'onPlateCardWithTrashed:player_id,match_name',
              'offPlateCardWithTrashed:player_id,match_name',
            ])
              ->whereNotNull('slot')
              ->where('schedule_id', $info->schedule_id)
              ->where(function ($query) use ($targetPlayerId) {
                $query->where('player_on_id', $targetPlayerId)
                  ->orWhere('player_off_id', $targetPlayerId);
              })
              ->get()->map(function ($item) use (&$inSubInfo, &$outSubInfo) {
                $inSubInfo[$item['player_on_id']] = $outSubInfo[$item['player_off_id']] = $item->toArray();
              });

            if (isset($inSubInfo[$targetPlayerId])) {
              $lineup['substitution'][$inSubInfo[$targetPlayerId]['slot']]['out']['period_id'] = $inSubInfo[$targetPlayerId]['period_id'];
              $lineup['substitution'][$inSubInfo[$targetPlayerId]['slot']]['out']['time'] = $inSubInfo[$targetPlayerId]['time_min'];
              $lineup['substitution'][$inSubInfo[$targetPlayerId]['slot']]['out']['player_id'] = $inSubInfo[$targetPlayerId]['player_off_id'];
              $lineup['substitution'][$inSubInfo[$targetPlayerId]['slot']]['out']['player_name'] = $inSubInfo[$targetPlayerId]['off_plate_card_with_trashed']['match_name'];
            }

            if (isset($outSubInfo[$targetPlayerId])) {
              $lineup['substitution'][$outSubInfo[$targetPlayerId]['slot']]['in']['period_id'] = $outSubInfo[$targetPlayerId]['period_id'];
              $lineup['substitution'][$outSubInfo[$targetPlayerId]['slot']]['in']['time'] = $outSubInfo[$targetPlayerId]['time_min'];
              $lineup['substitution'][$outSubInfo[$targetPlayerId]['slot']]['in']['player_id'] = $outSubInfo[$targetPlayerId]['player_on_id'];
              $lineup['substitution'][$inSubInfo[$targetPlayerId]['slot']]['out']['player_name'] = $inSubInfo[$targetPlayerId]['on_plate_card_with_trashed']['match_name'];
            }

            if (isset($inSubInfo[$targetPlayerId]) && !isset($lineup['substitution'][$inSubInfo[$targetPlayerId]['slot']]['in'])) {
              $lineup['substitution'][$inSubInfo[$targetPlayerId]['slot']]['in'] = null;
            }
            if (isset($outSubInfo[$targetPlayerId]) && !isset($lineup['substitution'][$outSubInfo[$targetPlayerId]['slot']]['out'])) {
              $lineup['substitution'][$outSubInfo[$targetPlayerId]['slot']]['out'] = null;
            }

            $result['data_updated'][$info->player_id] = $lineup;
          });
      }

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function getLineupDetail(LiveLineupDetailRequest $request)
  {
    $input = $request->only(['schedule_id', 'player_id']);
    try {
      $playerId = $input['player_id'];
      $scheduleId = $input['schedule_id'];

      /**
       * @var FantasyCalculator $fpCalculator
       */
      $fpCalculator = app(FantasyCalculatorType::FANTASY_POINT, [0]);

      $result = [
        'type' => 'lineup_detail',
        'player_id' => $playerId,
        'position' => null,
        'real_time_score' => null,
        'is_ingame_player' => true,
        'data_updated' => []
      ];

      $playerStats = OptaPlayerDailyStat::where([
        ['schedule_id', $scheduleId],
        ['player_id', $playerId],
      ])->first();
      // logger($playerStats->toArray());

      if ($playerStats === null || !((bool)($playerStats->total_sub_on) || (bool)($playerStats->game_started))) {
        $result['is_ingame_player'] = false;
      }

      $result['position'] = $playerStats['summary_position'] ?? null;

      if ($playerStats !== null && $playerStats->status !== ScheduleStatus::FIXTURE && $result['is_ingame_player'] === true) {
        $originWithPoints = ($fpCalculator->makePointSetWithRefName($playerStats->toArray(), true, true, true, true));
        [$totalPoint, $pointSet] = $originWithPoints;

        $result['real_time_score'] = $totalPoint;
        $result['data_updated'] = $pointSet;
      }
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function getMomentum(MomentumRequest $_request)
  {
    try {
      $result = [];
      $scheduleId = $_request->all()['schedule_id'];
      DailyStatTimeline::where('schedule_id', $scheduleId)
        ->orderBy('schedule_total_minute')
        ->get()->map(function ($item) use (&$result) {
          $item = $item->toArray();
          $result[$item['schedule_total_minute']] = (float)$item['momentum_y'];
        });

      if (!empty($result)) {
        $max = max(array_keys($result));

        for ($i = 0; $i <= $max; $i++) {
          if (!isset($result[$i])) {
            $result[$i] = 0;
          }
        }
      }

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function getCommentary(CommentaryRequest $_request)
  {
    try {
      $scheduleId = $_request->all()['schedule_id'];
      $scheduleStatus = (Schedule::withUnrealSchedule()->find($scheduleId)->status);
      $commonCols = [
        'schedule_id',
        'league_id',
        'season_id',
        'season_id',
        // 'home_team_id',
        // 'away_team_id',
      ];

      $baseCols = [
        'comment_id',
        'comment',
        'timestamp',
        'minute',
        'period',
        'second',
        'time_summary',
        'type',
        'last_modified',
      ];

      $result = ['type' => SocketChannelType::COMMENTARY];
      Commentary::where('schedule_id', $scheduleId)
        ->when(
          ($scheduleStatus === ScheduleStatus::PLAYED || $scheduleStatus === ScheduleStatus::AWARDED),
          function ($query) {
            $query->orderBy('comment_id', 'ASC');
          },
          function ($query) {
            $query->orderBy('comment_id', 'DESC');
          }
        )
        // ->whereIn('type', [...])
        ->with('playerRefOnMatch')
        ->with('playerRef1')
        ->with('playerRef2')
        ->with('homeTeam')
        ->with('awayTeam')
        ->get()->map(function ($comment) use ($commonCols, $baseCols, &$result) {
          if (!isset($result['schedule_id'])) {
            foreach ($commonCols as $colName) {
              $result[$colName] = $comment->{$colName};
            }
            // team 정보
            foreach (['home', 'away'] as $teamSide) {
              foreach (config('commonFields.team') as $colName) {
                $result[$teamSide . '_team'][$colName] = $comment->{$teamSide . 'Team'}[$colName];
              }
            }
          }
          //->
          $tempResult = [];
          $playerRefs = [];
          foreach ($baseCols as $colName) {
            $tempResult[$colName] = $comment->{$colName};
          }
          $tempResult['player_ref1'] = null;
          $tempResult['player_ref2'] = null;
          $eventTeam = 'common';
          if (isset($comment->playerRefOnMatch)) {
            $teamId = $comment->playerRefOnMatch->team_id;
            foreach (['home', 'away'] as $teamSide) {
              if ($comment->{$teamSide . '_team_id'} === $teamId) {
                $eventTeam = $teamSide;
              }
            }

            foreach (['1', '2'] as $playerRefNum) {
              if (isset($comment->{'playerRef' . $playerRefNum})) {
                $temp = $comment->{'playerRef' . $playerRefNum};
                unset($comment->{'playerRef' . $playerRefNum});
                foreach (config('commonFields.player') as $colName) {
                  $name = $temp->{$colName};
                  $playerRefs['player_ref' . $playerRefNum][$colName] = $name;
                }
                $playerRefs['player_ref' . $playerRefNum]['player_id'] = $temp->player_id;
                $playerRefs['player_ref' . $playerRefNum]['headshot_path'] = $temp->headshot_path;
              }
            }
          }
          $tempResult['event_team'] = $eventTeam;
          $tempResult = array_merge($tempResult, $playerRefs);
          $result['data_updated'][] = $tempResult;
        });
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }
}
