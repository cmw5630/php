<?php

namespace App\Services\Game;

use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\GameType;
use App\Enums\GradeCardLockStatus;
use App\Enums\Opta\Card\DraftCardStatus;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\QuestCollectionType;
use App\Exceptions\Custom\Api\Ingame\Lineup\CardsCountException;
use App\Exceptions\Custom\Api\Ingame\Lineup\CardStateException;
use App\Exceptions\Custom\Api\Ingame\Lineup\GameStateException;
use App\Exceptions\Custom\Api\Ingame\Lineup\PositionException;
use App\Exceptions\Custom\Api\Ingame\Lineup\UserCardsCountException;
use App\Exceptions\Custom\Api\Ingame\Lineup\UserCardStateException;
use App\Libraries\Classes\Exception;
use App\Libraries\Classes\QuestRecorder;
use App\Libraries\Traits\CommonTrait;
use App\Libraries\Traits\GameTrait;
use App\Models\data\BrSchedule;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\game\FreeCardMeta;
use App\Models\game\FreeGameLineup;
use App\Models\game\FreeGameLineupMemory;
use App\Models\game\Game;
use App\Models\game\GameJoin;
use App\Models\game\GameLineup;
use App\Models\game\GameSchedule;
use App\Models\game\PlateCard;
use App\Models\user\UserPlateCard;
use Carbon\Carbon;
use DB;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

interface GameServiceInterface
{
  public function getLineup($_gameId, $_userId);
  public function joinGame($input);
  public function joinFreeGame($input);
  // public function getStadiumHomeGames();
  public function getHomeGames();
  public function getGameList($_seasonId);
  public function getGameTopCard($_seasonId);
  public function getAllGameList($_seasonId);
  public function getGameInfo($_gameId);
  public function getGameDetail($_gameId);
  public function getUserRecord($_seasonId, $_userId = null);
}
class GameService implements GameServiceInterface
{
  use GameTrait, CommonTrait;

  protected ?Authenticatable $user;

  public function __construct(?Authenticatable $_user)
  {
    $this->user = $_user;
  }

  public function getLineup($_gameId, $_userId)
  {
    /**
     * @var FantasyCalculator $fpjCalculator
     */
    $fpjCalculator = app(FantasyCalculatorType::FANTASY_PROJECTION, [0]);

    $myFormation = GameJoin::where([
      ['game_id', $_gameId],
      ['user_id', $_userId]
    ])->value('formation');

    $teamIdsInGame = Schedule::whereHas('gamePossibleSchedule.gameSchedule.game', function ($query) use ($_gameId) {
      $query->where('id', $_gameId);
    })->select('home_team_id', 'away_team_id')
      ->get()
      ->flatMap(function ($info) {
        return [$info->home_team_id, $info->away_team_id];
      })->toArray();

    $list = [
      'formation_used' => null,
      'lineup' => null
    ];

    $baseJoin = GameLineup::whereHas('gameJoin', function ($query) use ($_gameId, $_userId) {
      $query->where([
        ['user_id', $_userId],
        ['game_id', $_gameId]
      ]);
    });
    if ($baseJoin->exists()) {
      $list['formation_used'] = $myFormation;

      $baseJoin->with([
        'gameJoin:id,user_id,formation',
        'gameJoin.game.gameSchedule.schedule:id,home_team_id,away_team_id',
        'userPlateCard.draftTeam:' . implode(',', config('commonFields.team')),
        'userPlateCard.draftSeason.league:id,league_code',
        'userPlateCard.plateCardWithTrashed:id,team_id,headshot_path,' . implode(',', config('commonFields.player')),
        'userPlateCard.simulationOverall:user_plate_card_id,final_overall,sub_position',
        'team:' . implode(',', config('commonFields.team')),
        'changeTeam:' . implode(',', config('commonFields.team'))
      ])
        ->whereHas('userPlateCard', function ($query) {
          $query->withoutGlobalScope('excludeBurned');
        })->orderByRaw("FIELD(place_index," . implode(',', config('formation-by-position.lineup_formation')[$myFormation]) . ")")
        ->get()
        ->map(function ($info) use (&$list, $fpjCalculator, $teamIdsInGame) {
          $lineup['id'] = $info->user_plate_card_id;
          $lineup['place_index'] = $info->place_index;
          $lineup['user_id'] = $info->gameJoin->user_id;
          $lineup['plate_card_id'] = $info->userPlateCard->plate_card_id;
          $lineup['player_id'] = $info->player_id;

          foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
            $lineup[$field] = $info->userPlateCard->plateCardWithTrashed->{$field};
          }
          $lineup['position'] = $info->userPlateCard->position;
          $lineup['sub_position'] = $info->userPlateCard->simulationOverall->sub_position;
          $lineup['draft_level'] = $info->userPlateCard->draft_level;
          $lineup['card_grade'] = $info->userPlateCard->card_grade;
          $lineup['is_mom'] = $info->userPlateCard->is_mom;
          $lineup['is_free'] = $info->userPlateCard->is_free;
          $lineup['special_skills'] = $info->userPlateCard->special_skills;
          $lineup['draft_completed_at'] = $info->userPlateCard->draft_completed_at;
          $lineup['grade_headshot'] = $info->userPlateCard->plateCardWithTrashed->headshot_path;
          $lineup['projection'] = $fpjCalculator->calculate(['user_plate_card_id' => $info->user_plate_card_id, 'plate_card_id' => null]);
          $lineup['m_fantasy_point'] = $info->m_fantasy_point;
          $lineup['team_id'] = $info->userPlateCard->plateCardWithTrashed->team_id;
          // $lineup['is_in_game'] = in_array($info->userPlateCard->plateCardWithTrashed->team_id, $teamIdsInGame);
          $lineup['is_team_changed'] = $info->is_team_changed;
          if ($info->is_team_changed) {
            foreach (config('commonFields.team') as $field) {
              $team[$field] = $info->team?->$field;
              $changeTeam[$field] = $info->changeTeam?->$field;
            }
            $lineup['before_team'] = $team;
            $lineup['after_team'] = $changeTeam;
          }

          $season['id'] = $info->userPlateCard->draft_season_id;
          $season['name'] = $info->userPlateCard->draft_season_name;
          $season['league_id'] = $info->userPlateCard->draftSeason->league_id;
          $season['league'] = $info->userPlateCard->draftSeason->league;
          $lineup['draft_season'] = $season;
          $lineup['draft_team'] = $info->userPlateCard->draftTeam;

          $list['lineup'][] = $lineup;

          return $list;
        });
    }

    return $list;
  }


  private function validJoinGameState($input)
  {
    if (!Game::where([['id', $input['game_id']], ['completed_at', null]])
      ->where(function ($query) {
        $query->where('reservation_time', '<', now())
          ->orWhere('reservation_time', null);
      })
      ->whereRaw("TIMESTAMPDIFF(MINUTE,'" . Carbon::now() . "',start_date) > " . config('constant.INGAME_POSSIBLE_TIME'))->exists()) {
      throw new Exception('참여할 수 없는 상태의 게임.(임시 텍스트)', Response::HTTP_BAD_REQUEST);
    }
  }

  private function validJoinGameState2($input)
  {
    if (!Game::where([['id', $input['game_id']], ['completed_at', null]])
      ->where(function ($query) {
        $query->where('reservation_time', '<', now())
          ->orWhere('reservation_time', null);
      })
      ->whereRaw("TIMESTAMPDIFF(MINUTE,'" . Carbon::now() . "',start_date) > " . config('constant.INGAME_POSSIBLE_TIME'))->exists()) {
      throw (new GameStateException('GAME STATE ERROR', [], null, [], ['status' => $this->getStatusCount($input['game_id'])['status']]));
    }
  }

  public function joinFreeGame($input)
  {
    /**
     * validation->
     */
    $formationMap = config('constant.FORMATION_PLACE_MAP.442');
    $this->validJoinGameState($input);
    if (GameJoin::where([['user_id', $this->user->id], ['game_id', $input['game_id']]])->exists()) {
      throw new Exception('이미 참여한 게임.(임시 텍스트)', Response::HTTP_BAD_REQUEST);
    }
    $fcmInst = FreeCardMeta::where([
      ['user_id', $this->user['id']],
      ['game_id', $input['game_id']],
    ])
      ->with('freeGameLineupMemory')
      ->first();
    $fglm = $fcmInst->freeGameLineupMemory->toArray();
    foreach ($fglm as $lineup) {
      $key = array_search($lineup['formation_place'], $formationMap[$lineup['position']]);
      unset($formationMap[$lineup['position']][$key]);
    }
    foreach ($formationMap as $item) {
      if (!empty($item)) {
        throw new Exception('라인업 구성 불완전', Response::HTTP_BAD_REQUEST);
      }
    }

    /**
     * <-validation
     */

    $gj = (new GameJoin);
    $gj->user_id = $this->user['id'];
    $gj->user_name = $this->user['name'];
    $gj->game_id = $fcmInst['game_id'];
    $gj->save();

    $freeCardMetaId = $fcmInst->id;

    $addColumn = [
      'final_overall' => [],
      'sub_position' => null,
      'second_position' => null,
      'third_position' => null
    ];

    $fglMemoryColumns = (new FreeGameLineupMemory)->getTableColumns();
    $fglMemoryColumns = array_merge($fglMemoryColumns, array_keys($addColumn));

    $fglColumns = (new FreeGameLineup)->getTableColumns();
    /**
     * @var FantasyCalculator $foCalculator
     */
    $foCalculator = app(FantasyCalculatorType::FANTASY_OVERALL, [0]);
    foreach ($fglm as $item) {
      $item = array_merge($item, $addColumn);
      // overall 계산
      $overallArr = $foCalculator->calculate($item['id'], false, true);

      $fgl = (new FreeGameLineup);
      $fgl->game_join_id = $gj->id;
      foreach ($item as $col => $val) {
        if (in_array($col, $fglColumns) && in_array($col, $fglMemoryColumns)) {
          if ($col === 'id') continue;
          if ($col === 'special_skills') {
            $fgl->{$col} = $val;
          } else if (in_array($col, array_keys($addColumn))) {
            $fgl->{$col} = $overallArr[$col];
          } else {
            $fgl->{$col} = $val;
          }
        }
      }

      $fgl->save();
    }
    FreeCardMeta::whereId($freeCardMetaId)->forceDelete();
  }

  private function validationLineup($_lineups, $_formation)
  {
    //index 별 position 이 맞는지?
    $correct = config('formation-by-position.formation_used')[$_formation];
    foreach ($_lineups as $lineup) {
      if ($correct[$lineup['place_index']] !== $lineup['position']) {
        throw new Exception('formation_place 와 position 불일치', Response::HTTP_BAD_REQUEST);
      }
    }
  }

  private function validationLineup2($_lineups, $_formation)
  {
    //index 별 position 이 맞는지?
    $correct = config('formation-by-position.formation_used')[$_formation];
    foreach ($_lineups as $lineup) {
      if ($correct[$lineup['place_index']] !== $lineup['position']) {
        throw (new PositionException('LINEUP POSITION ERROR', [], null, [], ['place_index' => $lineup['place_index']]));
      }
    }
  }

  // 라인업/라이브 변경-백업용
  public function joinGame($input)
  {
    try {
      $this->validJoinGameState($input);

      $lineup = json_decode($input['lineup'], true);
      $cardIds = array_column($lineup, 'id');

      if (count($lineup) != 11) {
        throw new Exception('라인업 구성이 완성되지 않았음.(임시 텍스트)', Response::HTTP_BAD_REQUEST);
      }

      // 라인업 validation
      $this->validationLineup($lineup, $input['formation']);

      $userCards = UserPlateCard::where('user_id', $this->user->id)
        ->whereIn('id', $cardIds)
        ->get();
      if ($userCards->count() != 11) {
        throw new Exception('유효하지 않는 카드 있음.(임시 텍스트)', Response::HTTP_BAD_REQUEST);
      }

      // 뺄 카드 lock status 처리 로직 추가(shion)
      $joinedLineups = GameLineup::whereHas('gameJoin', function ($gameJoinQuery) use ($input) {
        $gameJoinQuery->where([
          'game_id' => $input['game_id'],
          'user_id' => $this->user->id,
        ]);
      })
        ->get();

      // 참여된 라인업이 있는지(참여 했는지)
      if ($joinedLineups->count() > 0) {
        // 바뀐 라인업 감지
        $modified = $joinedLineups->reject(function ($item) use ($cardIds) {
          return in_array($item->user_plate_card_id, $cardIds);
        });

        if ($modified->count() < 1) {
          return [];
        }

        // 없어진 라인업이 있다면 lock_status 풀음
        $modified->map(function ($item) {
          __endUserPlateCardLock($item->user_plate_card_id, GradeCardLockStatus::INGAME, $item->schedule_id);
        });
      }

      $gameJoin = GameJoin::where([
        ['user_id', $this->user->id],
        ['game_id', $input['game_id']]
      ])->first();

      if (!is_null($gameJoin)) {
        $gameJoin->formation = $input['formation'];
        $gameJoin->save();
        $gameJoinId = $gameJoin->id;
      } else {
        $gameJoin = new GameJoin();
        $gameJoin->user_id = $this->user->id;
        $gameJoin->user_name = $this->user->name;
        $gameJoin->game_id = $input['game_id'];
        $gameJoin->formation = $input['formation'];
        $gameJoin->save();
        $gameJoinId = $gameJoin->id;
      }

      // 해당 게임 팀에 속한 플레이어인지 체크
      $teamsInGame = [];
      GameSchedule::withWhereHas('gamePossibleSchedule.schedule', function ($query) {
        $query->select('id', 'home_team_id', 'away_team_id');
      })->where('game_id', $input['game_id'])
        ->select('schedule_id')
        ->get()
        ->map(function ($info) use (&$teamsInGame) {
          $teamsInGame[$info->schedule_id] = [$info->gamePossibleSchedule->schedule->home_team_id, $info->gamePossibleSchedule->schedule->away_team_id];
          return $teamsInGame;
        });

      $cardSchedule = [];
      PlateCard::whereHas('userPlateCard', function ($query) use ($cardIds) {
        $query->whereIn('id', $cardIds);
      })->select('id', 'team_id')
        ->get()
        ->map(function ($info) use ($teamsInGame, &$cardSchedule) {
          foreach ($teamsInGame as $schedule_id => $teams) {
            if (in_array($info->team_id, $teams)) {
              $cardSchedule[$info->id] = $schedule_id;
            }
          }
        });

      foreach ($lineup as $card) {
        if (!__startUserPlateCardLock($card['id'], GradeCardLockStatus::INGAME)) {
          throw new Exception('마켓에 등록된 player.(임시 텍스트)', Response::HTTP_BAD_REQUEST);
        }
        $playerInfo = PlateCard::whereHas('userPlateCard', function ($query) use ($card) {
          $query->where('id', $card['id']);
        })->first()?->toArray();

        if (is_null($playerInfo)) {
          throw new Exception('제출할 수 없는 player.(임시 텍스트)', Response::HTTP_BAD_REQUEST);
        }

        if (!isset($cardSchedule[$playerInfo['id']])) {
          throw new Exception('게임에 속하지 않는 player.(임시 텍스트)', Response::HTTP_BAD_REQUEST);
        }

        GameLineup::updateOrCreateEx(
          [
            'game_join_id' => $gameJoinId,
            'place_index' => $card['place_index']
          ],
          [
            'game_join_id' => $gameJoinId,
            'schedule_id' => $cardSchedule[$playerInfo['id']],
            'user_plate_card_id' => $card['id'],
            'player_id' => $playerInfo['player_id'],
            'position' => $card['position'],
            'place_index' => $card['place_index'],
          ]
        );

        $lineupCards[] = [
          'player_id' => $playerInfo['player_id'],
          'season_id' => $playerInfo['season_id'],
          'position' => $card['position']
        ];
      }

      // gameList redis삭제
      $redisKeyName = $this->getRedisCachingKey(Game::find($input['game_id'])->first()->value('season_id') . '_gameList');
      if (Redis::exists($redisKeyName)) {
        Redis::del($redisKeyName);
      }

      // quest
      (new QuestRecorder())->act(QuestCollectionType::PARTICIPATION, $this->user->id);

      return $lineupCards;
    } catch (Throwable $th) {
      throw $th;
    }
  }

  public function joinGame2($input)
  {
    try {
      $this->validJoinGameState2($input);

      $lineup = json_decode($input['lineup'], true);
      $cardIds = array_column($lineup, 'id');

      if (count($lineup) != 11) {
        throw (new CardsCountException('LINEUP CARDS COUNT ERROR', [], null, [], ['count' => count($lineup)]));
      }

      // 라인업 validation
      $this->validationLineup2($lineup, $input['formation']);

      $userCards = UserPlateCard::where('user_id', $this->user->id)
        ->whereIn('id', $cardIds)
        ->whereNot('status', GradeCardLockStatus::MARKET)
        ->get();

      if ($userCards->count() != 11) {
        throw (new UserCardsCountException('LINEUP USER CARDS COUNT ERROR', [], null, [], ['count' => $userCards->count()]));
      }

      // 뺄 카드 lock status 처리 로직 추가(shion)
      $joinedLineups = GameLineup::whereHas('gameJoin', function ($gameJoinQuery) use ($input) {
        $gameJoinQuery->where([
          'game_id' => $input['game_id'],
          'user_id' => $this->user->id,
        ]);
      })->get();

      // 참여된 라인업이 있는지(참여 했는지)
      if ($joinedLineups->count() > 0) { //  lock_status 풀음
        $joinedLineups->map(function ($item) {
          __endUserPlateCardLock($item->user_plate_card_id, GradeCardLockStatus::INGAME, $item->schedule_id);
        });
      }

      $gameJoin = GameJoin::where([
        ['user_id', $this->user->id],
        ['game_id', $input['game_id']]
      ])->first();

      if (!is_null($gameJoin)) {
        $gameJoin->formation = $input['formation'];
        $gameJoin->save();
        $gameJoinId = $gameJoin->id;
      } else {
        $gameJoin = new GameJoin();
        $gameJoin->user_id = $this->user->id;
        $gameJoin->user_name = $this->user->name;
        $gameJoin->game_id = $input['game_id'];
        $gameJoin->formation = $input['formation'];
        $gameJoin->save();
        $gameJoinId = $gameJoin->id;
      }

      // 해당 게임 팀에 속한 플레이어인지 체크
      $teamsInGame = Schedule::whereHas('gamePossibleSchedule.gameSchedule', function ($query) use ($input) {
        $query->where('game_id', $input['game_id']);
      })->withUnrealSchedule()
        ->get()
        ->flatMap(function ($info) {
          return [$info->home_team_id => $info->id, $info->away_team_id => $info->id];
        })->toArray();

      foreach ($lineup as $card) {
        if (!__startUserPlateCardLock($card['id'], GradeCardLockStatus::INGAME)) {
          throw (new UserCardStateException('LINEUP USER CARD STATE ERROR', [], null, [], ['card_id' => $card['id']]));
        }

        $playerInfo = PlateCard::whereHas('userPlateCard', function ($query) use ($card) {
          $query->where('id', $card['id']);
        })->first();

        if (is_null($playerInfo)) {
          throw (new CardStateException('LINEUP CARD STATE ERROR', [], null, [], ['card_id' => $card['id'], 'empty' => 'plate_card']));
        }

        if (!isset($teamsInGame[$playerInfo->team_id])) {
          if (!$input['mode']) {
            throw (new CardStateException('LINEUP CARD STATE ERROR', [], null, [], ['card_id' => $card['id'], 'empty' => 'schedule']));
          } else {
            $scheduleId = null;
          }
        } else {
          $scheduleId = $teamsInGame[$playerInfo->team_id];
        }

        GameLineup::updateOrCreateEx(
          [
            'game_join_id' => $gameJoinId,
            'place_index' => $card['place_index']
          ],
          [
            'schedule_id' => $scheduleId,
            'team_id' => $playerInfo->team_id,
            'user_plate_card_id' => $card['id'],
            'player_id' => $playerInfo->player_id,
            'position' => $card['position'],
            'is_team_changed' => false
          ]
        );

        $lineupCards[] = [
          'player_id' => $playerInfo->player_id,
          'season_id' => $playerInfo->season_id,
          'position' => $card['position']
        ];
      }

      // gameList redis삭제
      $redisKeyName = $this->getRedisCachingKey(Game::find($input['game_id'])->first()->value('season_id') . '_gameList');
      if (Redis::exists($redisKeyName)) {
        Redis::del($redisKeyName);
      }

      // quest
      (new QuestRecorder())->act(QuestCollectionType::PARTICIPATION, $this->user->id);

      return $lineupCards;
    } catch (Throwable $th) {
      throw $th;
    }
  }

  private function getLatestRoundOfGames(): array
  {
    $sub = Game::whereNotNull('completed_at')
      ->selectRaw('season_id, game_round_no, row_number() over(PARTITION BY season_id order by completed_at) as rnum');

    return DB::query()->fromSub($sub, 'sub')->where('rnum', 1)
      ->get()
      ->keyBy('season_id')
      ->toArray();
  }

  // 3개만 노출
  public function getHomeGames()
  {
    $gameInfos = [];

    $latestRound = $this->getLatestRoundOfGames();

    $sub = Game::whereNull('completed_at')
      ->where([
        ['start_date', '>=', now()],
        ['is_popular', true]
      ])
      ->where(function ($query) {
        $query->where('reservation_time', '<', now())
          ->orWhere('reservation_time', null);
      })
      ->selectRaw('*, row_number() over(PARTITION BY season_id order by start_date) as rnum')
      ->withCount('gameJoin');

    DB::query()->fromSub($sub, 'sub')->where('rnum', 1)
      ->orderByDesc('game_join_count')
      ->orderBy('start_date')
      ->limit(3)
      ->get()
      ->map(function ($info) use (&$gameInfos, $latestRound) {
        $data['id'] = $info->id;
        $season = Season::find($info->season_id);

        if (is_null($season->league)) {
          return true;
        }

        $data['league_id'] = $season->league->id;
        $data['league_name'] = $season->league->name;
        $data['league_country'] = $season->league->country;
        $data['league_country_code'] = $season->league->country_code;
        $data['start_date'] = $info->start_date;
        $data['game_round_no'] = $info->game_round_no;

        if (isset($latestRound[$info->season_id])) {
          // 최근라운드 있을 때 - 해당 라운드 경기들의 기록
          $scheduleIds = GameSchedule::whereHas('game', function ($gameQuery) use ($latestRound, $info) {
            $gameQuery->where([
              // ['ga_round', $latestRound[$info->season_id]->ga_round],
              ['season_id', $info->season_id]
            ]);
          })->pluck('schedule_id');

          $topPlayers = OptaPlayerDailyStat::whereIn('schedule_id', $scheduleIds)
            ->selectRaw('player_id, sum(fantasy_point) as fantasy_point')
            ->groupBy('player_id')
            ->orderByDesc('fantasy_point')
            ->limit(3)
            ->get()
            ->map(function ($info) {
              $info->headshot_path = $info->plateCard->headshot_path;
              unset($info->plateCard);
              return $info;
            });
        } else {
          // 최근라운드 없을 때 - 이전 시즌 경기들의 기록
          $beforeSeasonId = Season::whereNot('id', $info->season_id)
            ->where('league_id', $data['league_id'])
            ->where('start_date', '<', now())
            ->orderByDesc('start_date')
            ->limit(1)
            ->value('id');

          $topPlayers = OptaPlayerDailyStat::where('season_id', $beforeSeasonId)
            ->selectRaw('player_id, sum(fantasy_point) as fantasy_point')
            ->groupBy('player_id')
            ->orderByDesc('fantasy_point')
            ->limit(3)
            ->get()
            ->map(function ($info) {
              $info->headshot_path = $info->plateCardWithTrashed->headshot_path;
              unset($info->plateCardWithTrashed);
              return $info;
            });
        }

        $data['top_players'] = $topPlayers;
        $gameInfos[] = $data;
      });

    return $gameInfos;
  }

  // 게임배너 3개 노출
  public function getGameList($_seasonId)
  {
    $list['normal'] = Game::with('gameJoin')
      ->where([
        ['season_id', $_seasonId],
        ['completed_at', null],
        ['mode', GameType::NORMAL]
      ])->where(function ($query) {
        $query->where('reservation_time', '<', now())
          ->orWhere('reservation_time', null);
      })
      ->whereNot('id', 142)
      ->select('id', 'mode', 'game_round_no', 'rewards', 'start_date', 'end_date')
      ->oldest()
      ->limit(3)
      ->get()
      ->map(function ($info) {
        $info->game_status = $this->getStatusCount($info->id)['status'];
        $info->join_count = count($info->gameJoin);
        $info->is_participation = GameJoin::where([['game_id', $info->id], ['user_id', $this->user->id]])->exists();

        unset($info->gameJoin);
        return $info;
      });

    // free , sponsor
    $list['free'] = Game::with('gameJoin')
      ->where([
        ['season_id', $_seasonId],
        ['mode', GameType::FREE]
      ])->where(function ($query) {
        $query->where('reservation_time', '<', now())
          ->orWhere('reservation_time', null);
      })->where(function ($dataQuery) {
        $dataQuery->where('completed_at', '>', Carbon::now()->subDays(1))
          ->orWhere('completed_at', null);
      })->select('id', 'mode', 'game_round_no', 'rewards', 'start_date', 'end_date', 'banner_path', 'completed_at')
      ->oldest()
      ->limit(1)
      ->get()
      ->map(function ($info) {
        $info->game_status = $this->getStatusCount($info->id)['status'];
        $info->join_count = count($info->gameJoin);
        $info->is_participation = GameJoin::where([['game_id', $info->id], ['user_id', $this->user->id]])->exists();

        unset($info->gameJoin);
        return $info;
      })->first();

    $list['sponsor'] = Game::with('gameJoin')
      ->where([
        ['season_id', $_seasonId],
        ['mode', GameType::SPONSOR]
      ])->where(function ($query) {
        $query->where('reservation_time', '<', now())
          ->orWhere('reservation_time', null);
      })->where(function ($dataQuery) {
        $dataQuery->where('completed_at', '>', Carbon::now()->subDays(1))
          ->orWhere('completed_at', null);
      })->select('id', 'mode', 'game_round_no', 'rewards', 'start_date', 'end_date', 'banner_path', 'completed_at')
      ->oldest()
      ->limit(1)
      ->get()
      ->map(function ($info) {
        $info->game_status = $this->getStatusCount($info->id)['status'];
        $info->join_count = count($info->gameJoin);
        $info->is_participation = GameJoin::where([['game_id', $info->id], ['user_id', $this->user->id]])->exists();

        unset($info->gameJoin);
        return $info;
      })->first();

    return $list;
  }

  public function getGameTopCard($_seasonId)
  {

    $result = [];

    UserPlateCard::withWhereHas('plateCard', function ($query) use ($_seasonId) {
      $query->isOnSale()
        ->selectRaw('id, league_id, headshot_path,' . implode(',', config('commonFields.player')))
        ->where('season_id', $_seasonId);
    })
      ->selectRaw('DISTINCT(plate_card_id), card_grade, draft_level, JSON_LENGTH(special_skills) as cnt, ROW_NUMBER() over(PARTITION BY plate_card_id order by draft_level DESC, card_grade ASC) as row_cnt')
      ->where('user_id', $this->user->id)
      ->where('is_open', true)
      ->where('status', 'complete')
      ->groupBy('plate_card_id', 'draft_level', 'card_grade', 'special_skills')
      ->get()
      ->sortBy('plateCard.last_name')
      ->sortBy('plateCard.first_name')
      ->sortByDesc('cnt')
      ->sortBy(function ($value) {
        return config('constant.DRAFT_CARD_GRADE_ORDER')[$value->card_grade];
      })
      ->sortByDesc('draft_level')
      ->each(function ($item) use (&$result) {

        if ($item->row_cnt == 1) {
          $temp = $item->toArray();
          $result[] = $temp;
        }

        if (count($result) == 3) {
          return false;
        }
      });
    return $result;
  }

  public function getAllGameList($_seasonId)
  {
    $list = [];
    $list['userRecord'] = $this->getUserRecord($_seasonId);

    $redisKeyName = $this->getRedisCachingKey($_seasonId . '_gameList');
    if (Redis::exists($redisKeyName)) {
      $list['games'] = json_decode(Redis::get($redisKeyName), true)[$_seasonId];
    } else {
      $upcomingMatch = config('constant.UPCOMING_MATCH_COUNT');

      Game::where('season_id', $_seasonId)
        ->where(function ($query) {
          $query->where('reservation_time', '<', now())
            ->orWhere('reservation_time', null);
        })
        ->whereNot('id', 142)
        ->withCount('gameJoin AS join_count')
        ->get()
        ->map(function ($info) use (&$list, &$upcomingMatch) {
          if ($this->getStatusCount($info->id, '')['status'] !== ScheduleStatus::CANCELLED && $upcomingMatch > 0) {
            $gameInfo['game_status'] = $this->getStatusCount($info->id)['status'];
            $gameInfo['game_id'] = $info->id;
            $gameInfo['game_round_no'] = $info->ga_round;
            $gameInfo['rewards'] = $info->rewards;
            $gameInfo['start_date'] = $info->start_date;
            $gameInfo['end_date'] = $info->end_date;
            $gameInfo['join_count'] = $info->join_count;

            $gameInfo['game_schedules'] = $this->getGameInfo($info->id)['game']['schedules'];

            $list['games'][] = $gameInfo;

            if ($this->getStatusCount($info->id, '')['status'] === ScheduleStatus::FIXTURE) {
              $upcomingMatch--;
            }
            return $list;
          }
        });

      if (isset($list['games'])) {
        Redis::set($redisKeyName, json_encode([$_seasonId => $list['games']]), 'EX', 86400);
      }
    }

    // 내가 참여한 게임의 lineup
    GameJoin::whereHas('game', function ($query) use ($_seasonId) {
      $query->where('season_id', $_seasonId);
    })->where('user_id', $this->user->id)
      ->get()
      ->map(function ($info) use (&$list) {
        $mine['game_id'] = $info->game_id;
        $mine['ranking'] = $info->ranking ?? $this->getRanking($this->user->id)[$info->game_id]['rnum'];
        $mine['point'] = $info->point;
        $mine['user_reward'] = $info->reward;

        $mine['lineups'] = $this->getLineup($info->game_id, $this->user->id);

        $list['mine'][$info->game_id] = $mine;
        return $list;
      });

    return $list;
  }

  public function getGameInfo($_gameId)
  {

    $gameInfo = Game::where('id', $_gameId)->first();
    $gameMode = $gameInfo->mode;
    $gameSeasonId = $gameInfo->season_id;

    $result = [];
    Season::whereId($gameSeasonId)->get()
      ->map(function ($info) use (&$result) {
        $result['season_name'] = $info->name;
        $result['league_id'] = $info->league->id;
        $result['league_code'] = $info->league->league_code;
        $result['country'] = $info->league->country;
        $result['country_code'] = $info->league->country_code;
        return $result;
      });
    $result['game_mode'] = $gameMode;

    $upcomingMatch = config('constant.UPCOMING_MATCH_COUNT');
    $result['game_list'] = Game::where('season_id', $gameSeasonId)
      ->where(function ($query) {
        $query->where('reservation_time', '<', now())
          ->orWhere('reservation_time', null);
      })->where('mode', $gameMode)
      ->with(['gameJoin' => function ($query) {
        $query->where('user_id', $this->user->id);
      }])->select(['id', 'game_round_no AS round', 'start_date', 'end_date'])
      ->get()
      ->map(function ($info) use (&$upcomingMatch) {
        $gameStatus = $this->getStatusCount($info->id)['status'];
        if ($gameStatus !== ScheduleStatus::CANCELLED && $upcomingMatch > 0) {
          $info->game_status = $gameStatus;
          if ($info->game_status === ScheduleStatus::FIXTURE) {
            $upcomingMatch--;
          }
          $info->is_participation = $info->gameJoin->count() > 0;
          unset($info->gameJoin);
          return $info;
        };
      });

    // gameInfo
    $result['game'] = $this->getGameDetail($_gameId);

    return $result;
  }

  public function getGameDetail($_gameId)
  {
    $game = [];
    $gameInfo = Game::with([
      'gameSchedule.gamePossibleSchedule.schedule' => function ($query) {
        $query->withUnrealSchedule()
          ->with([
            'home:venue_name,' . implode(',', config('commonFields.team')),
            'away:' . implode(',', config('commonFields.team')),
          ])->selectRaw('id,home_team_id,away_team_id,injury_one,injury_two,started_at,status,score_home,score_away,period_id,match_length_min,match_length_sec');
      }
    ])->withCount('gameJoin')
      ->find($_gameId);
    $game['id'] = $gameInfo->id;
    $game['round'] = $gameInfo->game_round_no;
    $game['start_date'] = $gameInfo->start_date;
    $game['end_date'] = $gameInfo->end_date;
    $game['rewards'] = $gameInfo->rewards;
    $game['join_count'] = $gameInfo->game_join_count;
    $game['status'] = $this->getStatusCount($gameInfo->id)['status'];
    $game['is_participation'] = GameJoin::where([['game_id', $gameInfo->id], ['user_id', $this->user->id]])->exists();
    foreach ($gameInfo->gameSchedule as $gameSchedule) {
      $schedule = $gameSchedule->gamePossibleSchedule->schedule;
      $schedule['is_ingame'] = true;
      if (is_null($gameSchedule->gamePossibleSchedule->br_schedule_id)) {
        $schedule['br_schedule_id'] = BrSchedule::where('opta_schedule_id', $gameSchedule->schedule_id)->value('sport_event_id');
      } else {
        $schedule['br_schedule_id'] = $gameSchedule->gamePossibleSchedule->br_schedule_id;
      }
      $schedule['injury_times'] = [
        'injury_one' => $schedule->injury_one,
        'injury_two' => $schedule->injury_two,
      ];
      $game['schedules'][] = $schedule;
    }

    return $game;
  }

  public function getUserRecord($_seasonId, $_userId = null)
  {
    if (is_null($_userId)) {
      $_userId = $this->user->id;
    }
    return GameJoin::where('user_id', $_userId)
      ->whereHas('game', function ($query) use ($_seasonId) {
        $query->where([
          ['season_id', $_seasonId],
          ['mode', gameType::NORMAL],
          ['completed_at', '!=', null],
          ['rewarded_at', '!=', null]
        ]);
      })
      ->selectRaw('COUNT(*) AS participation_count, COUNT(IF(reward>0, reward,NULL)) AS win_count,COUNT(IF(reward=0, reward,null)) AS lose_count, CAST(COUNT(IF(reward>0, reward,null)) / COUNT(*) AS float) AS win_rate, IFNULL(SUM(point),0) AS all_points, CAST(IFNULL(SUM(reward),0) AS float) AS all_rewards')
      ->first()->toArray();
  }
}
