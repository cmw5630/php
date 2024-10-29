<?php

namespace App\Services\Admin;

use App\Console\Commands\OptaParsers\MA8MatchPreviews;
use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\GameType;
use App\Enums\Opta\Card\PlateCardStatus;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\QuestActiveType;
use App\Libraries\Classes\Exception;
use App\Libraries\Classes\FantasyCalculator;
use App\Libraries\Traits\CommonTrait;
use App\Libraries\Traits\DraftTrait;
use App\Libraries\Traits\GameTrait;
use App\Libraries\Traits\LogTrait;
use App\Models\data\BrSchedule;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\game\DraftSelection;
use App\Models\game\Game;
use App\Models\game\GameJoin;
use App\Models\game\GameLineup;
use App\Models\game\GamePossibleSchedule;
use App\Models\game\GameSchedule;
use App\Models\game\Quest;
use App\Models\game\QuestType;
use App\Models\log\GameLog;
use App\Models\log\ScheduleStatusChangeLog;
use DB;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Storage;
use Throwable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Schema;

interface FantasyServiceInterface
{
  public function getScheduleList($input);
  public function getScheduleDetail($input);
  public function getGameList($input);
  public function getGameDetail($_gameId);
  public function getGameJoins($input);
  public function getGameJoinDetail($_gameJoinId);
  public function makeGame($input);
  public function cancelGame($_gameId);
}

class FantasyService implements FantasyServiceInterface
{
  use LogTrait, GameTrait, DraftTrait, CommonTrait;

  protected ?Authenticatable $admin;

  public function __construct()
  {
    $this->admin = Auth::guard('admin')?->user();
  }

  // 일정관리 -> week 로 묶은 schedule list
  public function getScheduleList($input)
  {
    $seasonInst = Season::whereId($input['season'])->first();
    $availableStageId = array_column($seasonInst->league->pValidScheduleStage->toArray(), 'stage_format_id');

    try {
      $list = [];
      Schedule::withWhereHas('league', function ($query) {
        $query->withoutGlobalScope('serviceLeague');
      })
        // ->whereIn('stage_format_id', $availableStageId)
        ->where('season_id', $input['season'])
        ->when($input['round'], function ($query, $round) {
          $query->where('round', $round);
        })
        ->orderBy($input['sort'], $input['order'])
        ->get()
        ->groupBy('round')
        ->map(function ($group) use (&$list, $input) {
          $round = $group[0]->round;
          $statusList = $this->getStatusCount($group->toArray());
          if (empty($input['status']) || $input['status'] === $statusList['status']) {
            $list[$round]['league_code'] = $group[0]->league->league_code;
            $list[$round]['round'] = $round;
            $list[$round]['start_date'] = $group->min('started_at');
            $list[$round]['end_date'] = $group->max('started_at');
            $list[$round]['match_count'] = $group->count();
            $list[$round]['status'] = $statusList['status'];
            /*
              $list[$round]['fixture'] = $statusList[ScheduleStatus::FIXTURE];
              $list[$round]['played'] = $statusList[ScheduleStatus::PLAYED];
              $list[$round]['cancelled'] = $statusList[ScheduleStatus::CANCELLED];
              $list[$round]['playing'] = $statusList[ScheduleStatus::PLAYING];
            */
            $list[$round]['updated_at'] = $group->max('updated_at');

            return $list;
          }
        });

      return array_values($list);
    } catch (Throwable $th) {
      throw new Exception($th->getMessage());
    }
  }

  public function getScheduleDetail($input)
  {
    try {
      $result['detail_info'] = $this->getScheduleList($input)[0];
      $result['schedules'] = Schedule::with([
        'home:' . implode(',', config('commonFields.team')),
        'away:' . implode(',', config('commonFields.team')),
      ])
        ->has('home')
        ->has('away')
        ->where([
          ['round', $input['round']],
          ['season_id', $input['season']]
        ])
        ->select('id', 'started_at', 'round', 'home_team_id', 'away_team_id', 'status', 'score_home', 'score_away')
        ->orderBy('started_at')
        ->get()
        ->toArray();

      $result['cancel_schedules'] = ScheduleStatusChangeLog::withWhereHas('schedule', function ($query) use ($input) {
        $query->with([
          'home:' . implode(',', config('commonFields.team')),
          'away:' . implode(',', config('commonFields.team')),
        ])
          ->has('home')
          ->has('away')
          ->where([
            ['round', $input['round']],
            ['season_id', $input['season']]
          ]);
      })->where([
        ['index_changed', '>', 0],
        ['old_status', '!=', 'new_status']
      ])
        ->whereNotIn('new_status', [ScheduleStatus::PLAYING, ScheduleStatus::PLAYED])
        ->get();
      return $result;
    } catch (Throwable $th) {
      throw new Exception($th->getMessage());
    }
  }

  public function getGameList($input, $_gameId = null)
  {
    try {
      $list = [];
      Game::with([
        'season.league:id,league_code',
        'gameSchedule.gamePossibleSchedule.schedule',
      ])
        ->has('gameSchedule.gamePossibleSchedule.schedule')
        ->when($_gameId, function ($query, $_gameId) {
          $query->where('id', $_gameId);
        })
        ->where('season_id', $input['season'])
        ->where(function ($query) {
          $query->where('reservation_time', '<', now())
            ->orWhere('reservation_time', null);
        })
        ->withCount('gameSchedule AS schedule_count')
        ->withCount('gameJoin AS join_count')
        ->orderByDesc('game_round_no')
        ->get()
        ->map(function ($info) use ($input, &$list) {
          // game_schedule 을 보고 game 상태 확인
          $gameStatus = $this->getStatusCount($info->id)['status'];

          if (is_null($input['status']) || $input['status'] === $gameStatus) {
            $gameList['id'] = $info->id;
            $gameList['league_id'] = $info->season->league_id;
            $gameList['league'] = $info->season->league->league_code;
            $gameList['season_id'] = $info->season_id;
            // 게임에 묶은 스케쥴의 모든 week 가 같다고 가정.
            $gameList['round'] = $info->gameSchedule[0]->gamePossibleSchedule->schedule->round;
            $gameList['game_round_no'] = $info->game_round_no;
            $gameList['ga_round'] = $info->gameSchedule[0]->gamePossibleSchedule->ga_round;
            $gameList['start_date'] =  $info->start_date;
            $gameList['end_date'] =  $info->end_date;
            $gameList['join_count'] = $info->join_count;
            // TODO : 취소(자동/수동)
            $gameList['reward'] = $info->rewards;
            $gameList['prize_rate'] = $info->prize_rate;
            $gameList['schedule_count'] = $info->schedule_count;
            $gameList['is_popular'] = ($info->is_popular) ? 'Y' : 'N';
            $gameList['game_status'] = $gameStatus;
            $gameList['banner_path'] = $info->banner_path;
            $gameList['reservation_time'] = $info->reservation_time;
            $gameList['created_at'] = $info->created_at;
            $gameList['updated_at'] = $info->updated_at;

            $list[] = $gameList;
          }
        });

      return $list;
    } catch (Throwable $th) {
      dd($th);
      throw new Exception($th->getMessage());
    }
  }

  public function getGameDetail($_gameId)
  {
    try {
      $schedules = GameSchedule::withWhereHas('gamePossibleSchedule.schedule', function ($query) {
        $query->with([
          'home:' . implode(',', config('commonFields.team')),
          'away:' . implode(',', config('commonFields.team')),
        ])
          ->has('home')
          ->has('away');
      })
        ->where('game_id', $_gameId)
        ->withCount('draft')
        ->withCount('gameLineup AS ingame_count')
        ->get()
        ->sortBy('schedule.started_at')
        ->values()
        ->map(function ($info) {
          $info->league_id = $info->gamePossibleSchedule->schedule->league_id;
          $info->season_id = $info->gamePossibleSchedule->schedule->season_id;
          $info->started_at = $info->gamePossibleSchedule->schedule->started_at;
          $info->round = $info->gamePossibleSchedule->schedule->round;
          $info->score_home = $info->gamePossibleSchedule->schedule->score_home;
          $info->score_away = $info->gamePossibleSchedule->schedule->score_away;
          $info->winner = $info->gamePossibleSchedule->schedule->winner;
          $info->home = $info->gamePossibleSchedule->schedule->home;
          $info->away = $info->gamePossibleSchedule->schedule->away;
          unset($info->gamePossibleSchedule->schedule);

          return $info;
        });

      $list['game_info'] = $this->getGameList([
        'season' => $schedules[0]['season_id'],
        'status' => null
      ], $_gameId);

      $gameStatus = ['status' => $this->getStatusCount($_gameId)['status']];
      $list['game_info'] = array_merge($list['game_info'][0], $gameStatus);

      $list['schedules'] = $schedules->toArray();
      return $list;
    } catch (Throwable $th) {
      throw new Exception($th->getMessage());
    }
  }

  public function getGameJoins($input)
  {
    try {
      return tap(
        GameJoin::withWhereHas('user', function ($query) use ($input) {
          $query->when($input['q'], function ($innerQuery, $keyword) {
            $innerQuery->whereLike(['email', 'name'], $keyword);
          })->select('id', 'email', 'name');
        })
          ->where('game_id', $input['game_id'])
          ->select('id', 'user_id', 'ranking', 'point', 'reward', 'created_at')
          ->orderByDesc('point')
          ->paginate($input['per_page'], ['*'], 'page', $input['page'])
      )
        ->map(function ($info) {
          $info->makeVisible(['created_at']);
          $info->game_join_id = $info->id;
          $info->user_id = $info->user->email;
          $info->nickname = $info->user->name;
          unset($info->id);
          unset($info->user);
          return $info;
        })->toArray();
    } catch (Throwable $th) {
      throw new Exception($th->getMessage());
    }
  }

  public function getGameJoinDetail($_gameJoinId)
  {
    try {
      /**
       * @var FantasyCalculator $fpCalculator
       */
      $fpCalculator = app(FantasyCalculatorType::FANTASY_POINT, [0]);

      $gameJoinInfo = GameJoin::withWhereHas('user', function ($query) {
        $query->withoutGlobalScope('excludeWithdraw')->select(['id', 'name', 'status']);
      })->withoutGlobalScope('excludeWithdraw')
        ->where('id', $_gameJoinId)
        ->select('user_id', 'point')
        ->first()
        ->toArray();

      $list['name'] = $gameJoinInfo['user']['name'];
      $list['point'] = $gameJoinInfo['point'];

      $list['lineups'] = GameLineup::with(['plateCardWithTrashed', 'userPlateCard.draftSeason'])->where('game_join_id', $_gameJoinId)
        ->select('game_join_id', 'schedule_id', 'player_id', 'user_plate_card_id', 'm_fantasy_point')->get()
        ->map(function ($info) use ($fpCalculator) {
          foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
            $name[$field] = $info->plateCardWithTrashed->{$field};
          }
          $name['player_name'] = $info->plateCardWithTrashed->player_name;
          $name['short_player_name'] = $info->plateCardWithTrashed->short_player_name;

          foreach (config('commonFields.team') as $field) {
            $team[$field] = $info->plateCardWithTrashed->team->{$field};
          }

          $info->name = $name;
          $info->position = $info->userPlateCard->position;
          $info->draft_league = $info->userPlateCard->draftSeason->league->league_code ?? null;

          $draftTeamNames = $info->userPlateCard->draft_team_names;
          $draftTeam['id'] = $info->userPlateCard->draft_team_id;
          $draftTeam['code'] = $draftTeamNames['team_code'];
          $draftTeam['name'] = $draftTeamNames['team_name'];
          $draftTeam['short_name'] = $draftTeamNames['team_short_name'];
          $info->draft_team = $draftTeam;

          $info->team = $team;
          $info->grade = $info->userPlateCard->card_grade;
          $info->level = $info->userPlateCard->draft_level;
          $info->attacking_level = $info->userPlateCard->attacking_level;
          $info->passing_level = $info->userPlateCard->passing_level;
          $info->defensive_level = $info->userPlateCard->defensive_level;
          $info->duel_level = $info->userPlateCard->duel_level;
          $info->goalkeeping_level = $info->userPlateCard->goalkeeping_level;
          $info->point = (float)$info->m_fantasy_point;
          unset($info->m_fantasy_point);
          unset($info->userPlateCard);
          unset($info->plateCardWithTrashed);

          $stats = [];
          $statObj = [];
          OptaPlayerDailyStat::where([
            ['schedule_id', $info->schedule_id],
            ['player_id', $info->player_id],
          ])->gameParticipantPlayer()
            ->get()
            ->map(function ($statInfo) use (&$stats, &$statObj, $fpCalculator) {
              $statArray = $fpCalculator->makePointSetWithRefName($statInfo->toArray(), true, true);
              foreach ($statArray as $stat => $value) {
                $statObj = array_merge($statObj, $statArray);
                $stats[] = $stat . ' ' . $value['origin'] . '(' . $value['fantasy'] . ')';
              }
            });
          $info->stats = implode(', ', $stats);
          $info->stat_obj = $statObj;

          return $info;
        });
      return $list;
    } catch (Throwable $th) {
      throw new Exception($th->getMessage());
    }
  }

  public function makeGame($input)
  {
    /*
    * 해당 season 의 가장 마지막 gameRound + 1
    * game 생성
    * game_schedule 생성
    */

    $mode = is_null($gameId = $input['game_id']) ? 'new' : 'update';

    $schedules = json_decode($input['schedules'], true);

    try {
      if (count($schedules) > 20 || count($schedules) < 1) {
        throw new Exception('경기가 없거나 경기 수가 너무 많아요.(임시 텍스트)', Response::HTTP_BAD_REQUEST);
      }

      if ($mode === 'update') {
        if ($this->getStatusCount($gameId)['status'] != ScheduleStatus::FIXTURE) {
          throw new Exception('이미 시작한 게임은 수정할 수 없습니다.(임시 텍스트)', Response::HTTP_BAD_REQUEST);
        }
      }

      $scheduleIds = array_column($schedules, 'id');

      $scheduleInfo = Schedule::where([
        'season_id' => $input['season'],
        'status' => ScheduleStatus::FIXTURE
      ])
        ->whereIn('id', $scheduleIds)
        ->get()
        ->keyBy('id');

      if (count($schedules) !== count($scheduleInfo)) {
        throw new Exception('올바르지 않은 경기가 포함돼 있어요.', Response::HTTP_BAD_REQUEST);
      }

      if ($mode === 'new') {
        // $isContainedSchedule = GameSchedule::query()
        //   ->whereIn('schedule_id', $scheduleIds)
        //   ->where('status', ScheduleStatus::FIXTURE)
        //   ->exists();

        // // TODO : 무료게임은 동일 경기로 게임 만들어져야 함
        // if ($isContainedSchedule) {
        // //   throw new Exception('이미 game에 속한 경기.(임시 텍스트)', Response::HTTP_BAD_REQUEST);
        // // }
      }

      // 경기 중 최초, 마지막 날짜
      [$firstDate, $lastDate] = array_values(Schedule::query()
        ->whereIn('id', $scheduleIds)
        ->selectRaw('MIN(started_at) AS start_date, MAX(started_at) AS end_date')
        ->first()
        ->toArray());

      // 다음 라운드
      $nextRound = Game::where('season_id', $input['season'])
        ->selectRaw('IFNULL(MAX(game_round_no), 0)+1 AS nextRound')
        ->value('nextRound');

      DB::beginTransaction();

      // 게임 생성 시작
      if ($mode === 'new') {
        $game = new Game();
        $game->season_id = $input['season'];
        $game->game_round_no = $nextRound;
        $game->user_id = $input['user_id'];
      } else {
        // 수정 모드 시 게임정보
        $game = Game::find($gameId);
      }

      // 등록, 수정시 공통으로 변경되어야 할 정보
      $game->mode = $input['mode'];
      $game->start_date = $firstDate;
      $game->end_date = $lastDate;
      $game->rewards = $input['rewards'];
      $game->prize_rate = $input['prize_rate'];
      $game->is_popular = $input['is_popular'];
      if (isset($input['reservation_time'])) {
        $game->reservation_time = $input['reservation_time'];
      }
      $game->save();
      $gameId = $game->id;

      if ($mode === 'update') {
        // 1. 기존 schedule 에만 있는 schedule 들 삭제
        $gameSchedules = GameSchedule::where('game_id', $gameId)->pluck('schedule_id')->toArray();
        $deleteSchedules = array_diff($gameSchedules, array_column($schedules, 'id'));
        if (count($deleteSchedules) > 0) {
          foreach ($deleteSchedules as $delSchedule) {
            GameSchedule::where('schedule_id', $delSchedule)->delete();

            // 강화 취소
            $draftSelection = DraftSelection::where('schedule_id', $delSchedule);
            if ($draftSelection->exists()) {
              $draftSelection->get()
                ->map(function ($info) {
                  $this->cancelDraftAllByScheduleId($info->schedule_id);
                });
            }

            // 인게임 취소
            $gameLineups = GameLineup::where('schedule_id', $delSchedule);
            if ($gameLineups->exists()) {
              // game_join
              $gameJoinIds = $gameLineups->selectRaw('DISTINCT(game_join_id) AS gameJoinId')->pluck('gameJoinId')->toArray();
              GameJoin::whereIn('id', $gameJoinIds)->forceDelete();
              // game_lineup
              $gameLinupIds = $gameLineups->pluck('id')->toArray();
              GameLineup::whereIn('id', $gameLinupIds)->forceDelete();
            }
          }
        }

        // 2. gameSchedule 의 기존 schedule 과 $schedules 를 비교=> 공통 schedule update 
        $commonSchedules = array_intersect($gameSchedules, array_column($schedules, 'id'));

        if (count($commonSchedules) > 0) {
          $commonScheduleInfos = GameSchedule::whereIn('schedule_id', $commonSchedules)->get()->keyBy('schedule_id')->toArray();
          foreach ($commonScheduleInfos as $comScheduleId => $comScheduleInfo) {
            $gameSchedule = GameSchedule::where('schedule_id', $comScheduleId)->first();
            foreach ($schedules as $schedule) {
              // 강화 비교 취소
              if ($comScheduleId === $schedule['id']) {
                // $gameSchedule->is_draft = $schedule['is_draft'];
                // $gameSchedule->is_ingame = $schedule['is_ingame'];

                if ($comScheduleInfo['is_draft'] === true && $schedule['is_draft'] === false) {
                  $draftSelection = DraftSelection::where('schedule_id', $comScheduleId);
                  if ($draftSelection->exists()) {
                    $draftSelection->get()
                      ->map(function ($info) {
                        $this->cancelDraftAllByScheduleId($info->schedule_id);
                      });
                  }
                }

                // ingame 비교 지우고
                if ($comScheduleInfo['is_ingame'] === true && $schedule['is_ingame'] === false) {

                  $gameLineups = GameLineup::where('schedule_id', $comScheduleId);
                  if ($gameLineups->exists()) {
                    // game_join
                    $gameJoinIds = $gameLineups->selectRaw('DISTINCT(game_join_id) AS gameJoinId')->pluck('gameJoinId')->toArray();
                    GameJoin::whereIn('id', $gameJoinIds)->forceDelete();
                    // game_lineup
                    $gameLinupIds = $gameLineups->pluck('id')->toArray();
                    GameLineup::whereIn('id', $gameLinupIds)->forceDelete();
                  }
                }
                $gameSchedule->save();
              }
            }
          }
        }

        // 3. 새로들어온 schedule 들 store
        $originScheduleCount = count($schedules);
        $saveScheduleIds = array_diff(array_column($schedules, 'id'), $gameSchedules);
        for ($i = 0; $i < $originScheduleCount; $i++) {
          if (!in_array($schedules[$i]['id'], $saveScheduleIds)) {
            unset($schedules[$i]);
          }
        }
      }

      if (count($schedules) > 0) {
        foreach ($schedules as $schedule) {
          // 저장만
          $gameSchedule = new GameSchedule();
          $gameSchedule->game_id = $gameId;
          $gameSchedule->schedule_id = $schedule['id'];
          $gameSchedule->br_schedule_id = BrSchedule::where('opta_schedule_id', $schedule['id'])->value('sport_event_id');
          $gameSchedule->status = $scheduleInfo[$schedule['id']]['status'];
          $gameSchedule->is_draft = $schedule['is_draft'];
          $gameSchedule->is_ingame = $schedule['is_ingame'];
          $gameSchedule->save();

          // match_preview 수집
          (new MA8MatchPreviews($schedule['id']))->start(true);
        }
      }

      if (!empty($input['banner'])) {
        $fileName = $this->uploadPhoto($gameId, $input['banner']);
      }

      // redis gameList 삭제
      $redisKeyName = $this->getRedisCachingKey($input['season'] . '_gameList');
      Redis::del($redisKeyName);

      DB::commit();

      return $game;
    } catch (Throwable $th) {
      // 저장된 file 삭제
      if (isset($fileName)) {
        Storage::disk()->delete('banner/game/' . $fileName);
      }
      DB::rollback();
      throw new Exception($th->getMessage());
    }
  }

  public function makeGame2($input)
  {
    /*
    * 해당 season 의 가장 마지막 gameRound + 1
    * game 생성
    * game_schedule 생성
    */

    $mode = is_null($gameId = $input['game_id']) ? 'new' : 'update';

    $schedules = json_decode($input['schedules'], true);

    try {
      if (count($schedules) > 20 || count($schedules) < 1) {
        throw new Exception('경기가 없거나 경기 수가 너무 많아요.(임시 텍스트)', Response::HTTP_BAD_REQUEST);
      }

      if ($mode === 'update') {
        if ($this->getStatusCount($gameId)['status'] != ScheduleStatus::FIXTURE) {
          throw new Exception('이미 시작한 게임은 수정할 수 없습니다.(임시 텍스트)', Response::HTTP_BAD_REQUEST);
        }
      }

      $scheduleIds = array_column($schedules, 'id');

      // schedule status 검사
      $scheduleInfos = Schedule::whereIn('id', $scheduleIds)->where('status', ScheduleStatus::FIXTURE);

      if ($scheduleInfos->clone()->count() !== count($scheduleIds)) {
        throw new Exception('올바르지 않은 경기가 포함돼 있어요.', Response::HTTP_BAD_REQUEST);
      }

      // 인플루언서 모드일때
      // $gaRound = $scheduleInfo[$scheduleIds[0]]['ga_round'];
      // if (Game::where([
      //   ['season_id', $input['season']],
      //   ['ga_round', $gaRound],
      //   ['user_id', $input['user_id']]
      // ])->exists()) {
      //   throw new Exception('이미 해당 Round 에 game 을 만들었어요.', Response::HTTP_BAD_REQUEST);
      // }

      // 경기 중 최초, 마지막 날짜
      [$firstDate, $lastDate] = array_values(Schedule::query()
        ->whereIn('id', $scheduleIds)
        ->selectRaw('MIN(started_at) AS start_date, MAX(started_at) AS end_date')
        ->first()
        ->toArray());

      if (!isset($input['mode'])) {
        $input['mode'] = GameType::NORMAL;
      }

      // 다음 라운드
      $nextRound = Game::where([
        ['season_id', $input['season']],
        ['mode', $input['mode']]
      ])
        ->selectRaw('IFNULL(MAX(game_round_no), 0)+1 AS nextRound')
        ->value('nextRound');

      DB::beginTransaction();

      // possible_schedule 에 insert
      $scheduleInfoArr = $scheduleInfos->get()->toArray();
      foreach ($scheduleInfoArr as $schedule) {
        GamePossibleSchedule::updateOrCreateEx(
          ['schedule_id' => $schedule['id']],
          [
            'league_id' => $schedule['league_id'],
            'season_id' => $schedule['season_id'],
            'schedule_id' => $schedule['id'],
            'round' => $schedule['round'],
            'ga_round' => $schedule['ga_round'],
            'br_schedule_id' => BrSchedule::where('opta_schedule_id', $schedule['id'])->value('sport_event_id'),
            'status' => $schedule['status']
          ]
        );
      }

      // 게임 생성 시작
      if ($mode === 'new') {
        $game = new Game();
        $game->season_id = $input['season'];
        $game->game_round_no = $nextRound;
      } else {
        // 수정 모드 시 게임정보
        $game = Game::find($gameId);
      }

      // 등록, 수정시 공통으로 변경되어야 할 정보
      // $game->user_id = $input['user_id'];
      $game->mode = $input['mode'];
      $game->start_date = $firstDate;
      $game->end_date = $lastDate;
      $game->rewards = $input['rewards'];
      $game->prize_rate = $input['prize_rate'];
      $game->is_popular = $input['is_popular'];
      if (isset($input['reservation_time'])) {
        $game->reservation_time = $input['reservation_time'];
      }
      $game->save();
      $gameId = $game->id;

      if ($mode === 'update') {
        // 1. 기존 schedule 에만 있는 schedule 들 삭제
        $gameSchedules = GameSchedule::where('game_id', $gameId)->pluck('schedule_id')->toArray();
        $deleteSchedules = array_diff($gameSchedules, array_column($schedules, 'id'));
        if (count($deleteSchedules) > 0) {
          foreach ($deleteSchedules as $delSchedule) {
            // possibleSchedule 삭제?
            if (!GameSchedule::where([['schedule_id', $delSchedule], ['game_id', '!=', $gameId]])->exists()) {
              GamePossibleSchedule::where('schedule_id', $delSchedule)->forceDelete();
              // GameSchedule::where('schedule_id', $delSchedule)->delete();
            }

            // 강화 취소
            $draftSelection = DraftSelection::where('schedule_id', $delSchedule);
            if ($draftSelection->exists()) {
              $draftSelection->get()
                ->map(function ($info) {
                  $this->cancelDraftAllByScheduleId($info->schedule_id);
                });
            }

            // 인게임 취소
            $gameLineups = GameLineup::where('schedule_id', $delSchedule);
            if ($gameLineups->exists()) {
              // game_join
              $gameJoinIds = $gameLineups->selectRaw('DISTINCT(game_join_id) AS gameJoinId')->pluck('gameJoinId')->toArray();
              GameJoin::whereIn('id', $gameJoinIds)->forceDelete();
              // game_lineup
              $gameLinupIds = $gameLineups->pluck('id')->toArray();
              GameLineup::whereIn('id', $gameLinupIds)->forceDelete();
            }
          }
        }

        // 2. gameSchedule 의 기존 schedule 과 $schedules 를 비교=> 공통 schedule update 
        $commonSchedules = array_intersect($gameSchedules, array_column($schedules, 'id'));

        if (count($commonSchedules) > 0) {
          $commonScheduleInfos = GameSchedule::whereIn('schedule_id', $commonSchedules)->get()->keyBy('schedule_id')->toArray();
          foreach ($commonScheduleInfos as $comScheduleId => $comScheduleInfo) {
            foreach ($schedules as $schedule) {
              // 강화 취소
              if ($comScheduleId === $schedule['id']) {
                $draftSelection = DraftSelection::where('schedule_id', $comScheduleId);
                if ($draftSelection->exists()) {
                  $draftSelection->get()
                    ->map(function ($info) {
                      $this->cancelDraftAllByScheduleId($info->schedule_id);
                    });
                }

                // ingame 삭제
                $gameLineups = GameLineup::where('schedule_id', $comScheduleId);
                if ($gameLineups->exists()) {
                  // game_join
                  $gameJoinIds = $gameLineups->selectRaw('DISTINCT(game_join_id) AS gameJoinId')->pluck('gameJoinId')->toArray();
                  GameJoin::whereIn('id', $gameJoinIds)->forceDelete();
                  // game_lineup
                  $gameLinupIds = $gameLineups->pluck('id')->toArray();
                  GameLineup::whereIn('id', $gameLinupIds)->forceDelete();
                }
              }
            }
          }
        }

        // 3. 새로들어온 schedule 들 store
        $originScheduleCount = count($schedules);
        $saveScheduleIds = array_diff(array_column($schedules, 'id'), $gameSchedules);
        for ($i = 0; $i < $originScheduleCount; $i++) {
          if (!in_array($schedules[$i]['id'], $saveScheduleIds)) {
            unset($schedules[$i]);
          }
        }
      }

      if (count($schedules) > 0) {
        foreach ($schedules as $schedule) {
          // 저장만
          $gameSchedule = new GameSchedule();
          $gameSchedule->game_id = $gameId;
          $gameSchedule->schedule_id = $schedule['id'];
          $gameSchedule->save();

          // match_preview 수집
          (new MA8MatchPreviews($schedule['id']))->start(true);
        }
      }

      if (!empty($input['banner'])) {
        $fileName = $this->uploadPhoto($gameId, $input['banner']);
      }

      // redis gameList 삭제
      $redisKeyName = $this->getRedisCachingKey($input['season'] . '_gameList');
      Redis::del($redisKeyName);

      DB::commit();

      return ['game_id' => $gameId];
    } catch (Throwable $th) {
      // 저장된 file 삭제
      if (isset($fileName)) {
        Storage::disk()->delete('banner/game/' . $fileName);
      }
      DB::rollback();
      throw new Exception($th->getMessage());
    }
  }

  public function checkGameJoin($_gameId)
  {
    try {
      $isDraft = false;
      $isIngame = false;

      // complete 되지 않은 draft 걸려있는지 확인
      if (DraftSelection::whereHas('schedule.gameSchedule', function ($query) use ($_gameId) {
        $query->where('game_id', $_gameId);
      })->whereHas('userPlateCard', function ($query) {
        $query->where('status', PlateCardStatus::UPGRADING);
      })->exists()) {
        $isDraft = true;
      }

      // 라인업 제출한 사람 확인
      if (gameJoin::where('game_id', $_gameId)->exists()) {
        $isIngame = true;
      }

      return ['is_draft' => $isDraft, 'is_ingame' => $isIngame];
    } catch (Throwable $th) {
      throw new Exception($th->getMessage());
    }
  }

  // Todo : admin level 확인 로직 필요
  public function cancelGame($_gameId)
  {
    try {
      if ($this->getStatusCount($_gameId)['status'] != ScheduleStatus::FIXTURE) {
        throw new Exception('삭제할 수 없는 게임의 상태.(임시 텍스트)', Response::HTTP_BAD_REQUEST);
      }
      // 게임 취소 전 드래프트/인게임 참여자 확인
      $checkResult = $this->checkGameJoin($_gameId);

      DB::beginTransaction();
      Schema::connection('log')->disableForeignKeyConstraints();
      Schema::connection('api')->disableForeignKeyConstraints();

      if ($checkResult['is_draft']) {
        $gameScheduleIds = GameSchedule::where('game_id', $_gameId)->pluck('schedule_id')->toArray();
        DraftSelection::whereIn('schedule_id', $gameScheduleIds)->get()
          ->map(function ($info) {
            $this->cancelDraftAllByScheduleId($info->schedule_id);
          });
      }

      // 게임 취소
      if ($checkResult['is_ingame']) {
        // game_join
        $gameJoin = GameJoin::where('game_id', $_gameId);
        $gameJoinId = $gameJoin->value('id');
        $gameJoin->forceDelete();
        // game_lineup
        GameLineup::where('game_join_id', $gameJoinId)->forceDelete();
      }
      // game_schedule
      GameSchedule::where('game_id', $_gameId)->forceDelete();
      // game
      $game = Game::find($_gameId);
      Storage::disk()->delete($game->banner_path);
      $game->forceDelete();

      // 취소 log
      $this->recordLog(
        GameLog::class,
        [
          'admin_id' => $this->admin->id,
          'game_id' => $_gameId,
          'canceled_at' => now(),
        ]
      );

      DB::commit();
    } catch (Throwable $th) {
      DB::rollBack();
      throw new Exception($th->getMessage());
    } finally {
      Schema::connection('log')->enableForeignKeyConstraints();
      Schema::connection('api')->enableForeignKeyConstraints();
    }
  }

  public function saveQuest($input)
  {
    try {
      DB::beginTransaction();

      // 날짜 계산
      $dateStringSet = $this->getDateStringSet();

      $activeType = QuestActiveType::CURRENT;

      if (QuestType::count() > 0) {
        $activeType = QuestActiveType::RESERVATION;

        $baseQuery = QuestType::where(function ($query) use ($dateStringSet) {
          $query->where([
            'start_date' => $dateStringSet['next_week']['start'],
            'end_date' => $dateStringSet['next_week']['end']
          ]);
        });
      }

      $questBoards = json_decode($input['quests']);

      // reservation 기존 quest array
      $originNextQuest = [];
      $baseQuery->clone()->with('quest')->get()
        ->map(function ($info) use (&$originNextQuest) {
          $originNextQuest[] = $info->quest->code . '_' . $info->period;
        });

      $dupleCodeChk = [];
      $dupleOrderChk = [];

      foreach ($questBoards as $quest) {
        if (in_array($quest->code . '_' . $quest->period, $dupleCodeChk)) {
          throw new Exception('quest 중복.', Response::HTTP_BAD_REQUEST);
        }
        $dupleCodeChk[] = $quest->code . '_' . $quest->period;

        if (in_array($quest->order_no . '_' . $quest->period, $dupleOrderChk)) {
          throw new Exception('order_no 중복.', Response::HTTP_BAD_REQUEST);
        }
        $dupleOrderChk[] = $quest->order_no . '_' . $quest->period;

        if ($activeType === QuestActiveType::CURRENT) {
          $questType = new QuestType();
        } else {
          $questType = $baseQuery->clone()->withTrashed()->where([
            ['period', $quest->period],
            ['order_no', $quest->order_no],
          ])->first();

          // reservation 에 없다가 추가된 quest
          if (is_null($questType)) {
            $questType = new QuestType();
          }
        }

        $questType->quest_id = Quest::where([['code', $quest->code], ['period', $quest->period]])->value('id');
        $questType->order_no = $quest->order_no;
        $questType->rewards = $quest->rewards;
        $questType->achieve_count = $quest->achieve_count;
        $questType->period = $quest->period;

        if ($activeType === QuestActiveType::CURRENT) {
          $questType->start_date = $dateStringSet['this_week']['start'];
          $questType->end_date = $dateStringSet['this_week']['end'];
        } else {
          $questType->start_date = $dateStringSet['next_week']['start'];
          $questType->end_date = $dateStringSet['next_week']['end'];
          if (!is_null($questType->deleted_at)) {
            $questType->deleted_at = null;
          }
        }

        $questType->save();
      }

      // reservation 에 있다가 빠진 quest 삭제
      if ($activeType === QuestActiveType::RESERVATION) {
        $deleteQuest = array_diff($originNextQuest, $dupleCodeChk);
        if (count($deleteQuest) > 0) {
          foreach ($deleteQuest as $quest) {
            $questCode = explode('_', $quest)[0];
            $questPeriod = explode('_', $quest)[1];
            $baseQuery->clone()->whereHas('quest', function ($query) use ($questCode) {
              $query->where('code', $questCode);
            })->where('period', $questPeriod)->delete();
          }
        }
      }

      DB::commit();
    } catch (Throwable $th) {
      throw new Exception($th->getMessage());
    }
  }

  private function uploadPhoto($_gameId, UploadedFile $_file)
  {
    try {
      $game = Game::find($_gameId);
      $oldPhoto = $game->banner_path;
      $storage = Storage::disk();

      $newName = $_gameId . '_' . $_file->hashName();
      $path = $storage->putFileAs('banner/game', $_file, $newName);

      $game->banner_path = $path;
      $game->save();
      // 기존 사진 있으면 삭제
      if (!is_null($oldPhoto)) {
        $storage->delete($oldPhoto);
      }

      return $newName;
    } catch (Exception $th) {
      throw $th;
    }
  }
}
