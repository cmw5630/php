<?php

namespace App\Http\Controllers\API\v1;

use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\GameType;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\PointRefType;
use App\Enums\PointType;
use App\Enums\QuestRewardType;
use App\Http\Requests\Api\Game\StadiumGameLogRequest;
use App\Libraries\Classes\FantasyCalculator;
use App\Enums\GradeCardLockStatus;
use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Card\PlateCardStatus;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\PlateCardActionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Game\FreeCardRequest;
use App\Http\Requests\Api\Game\GameCardListRequest;
use App\Http\Requests\Api\Game\GameJoinRequest;
use App\Http\Requests\Api\Game\OpenFreeShuffleCardRequest;
use App\Http\Requests\Api\Game\PredictVoteRequest;
use App\Http\Requests\Api\Game\StadiumRankingRequest;
use App\Http\Requests\Api\Game\StadiumRequest;
use App\Http\Requests\Api\Game\UpdateLineupRequest;
use App\Libraries\Classes\Exception;
use App\Libraries\Traits\CommonTrait;
use App\Libraries\Traits\GameTrait;
use App\Libraries\Traits\LogTrait;
use App\Libraries\Traits\PlayerTrait;
use App\Models\data\Season;
use App\Models\game\FreeCardMeta;
use App\Models\game\FreeCardShuffleMemory;
use App\Models\game\FreeGameLineupMemory;
use App\Models\game\Game;
use App\Models\game\GameJoin;
use App\Models\game\GameLineup;
use App\Models\game\Quest;
use App\Models\game\QuestType;
use App\Models\game\QuestUserLog;
use App\Models\game\QuestUserAchievement;
use App\Models\log\PredictVote;
use App\Models\log\PredictVoteItem;
use App\Models\log\PredictVoteLog;
use App\Models\meta\RefTeamCurrentMeta;
use App\Models\user\UserPlateCard;
use App\Services\Data\DataService;
use App\Services\Game\DraftService;
use App\Services\Game\FreeGameService;
use App\Services\Game\GameService;
use Carbon\Carbon;
use DB;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Redis;
use ReturnData;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class StadiumController extends Controller
{
  use PlayerTrait, GameTrait, LogTrait, CommonTrait;

  protected ?Authenticatable $user;

  private GameService $gameService;
  private DataService $dataService;
  private DraftService $draftService;
  private FreeGameService $freeGameService;

  public function __construct(?Authenticatable $_user, GameService $_gameService, DataService $_dataService, DraftService $_draftService, FreeGameService $_freeGameService)
  {
    $this->user = $_user;
    $this->gameService = $_gameService;
    $this->dataService = $_dataService;
    $this->draftService = $_draftService;
    $this->freeGameService = $_freeGameService;
  }

  // 게임생성을 위한 스케쥴 가져오기
  public function scheduleList($param)
  {
    $result = $this->dataService->getSchedules($param);
    return ReturnData::setData($result)->send(Response::HTTP_OK);
  }

  public function lineups(StadiumRequest $request)
  {
    $gameId = $request->only(['id']);
    try {
      $result = $this->gameService->getLineup($gameId, $this->user->id);

      // 최근 5경기 전적
      $result['last5'] = GameJoin::withWhereHas('game', function ($query) {
        $query->whereNotNull('completed_at')
          ->where('mode', GameType::NORMAL)
          ->select('id', 'game_round_no');
      })->where('user_id', $request->user()->id)
        ->select('game_id', 'ranking', 'point', 'formation')
        ->orderByDesc('game_id')
        ->limit(5)
        ->get()
        ->map(function ($info) {
          return [
            'game_id' => $info->game_id,
            'game_round_no' => $info->game->game_round_no,
            'formation' => $info->formation,
            'ranking' => $info->ranking,
            'point' => $info->point
          ];
        });

      // 구매한 포메이션 개수
      $formationList = config('constant.LINEUP_FORMATION');
      $result['formation_list'] = array_fill_keys($formationList, 0);

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  // 라인업/라이브 변경-백업용
  public function joinGame(GameJoinRequest $request)
  {
    $input = $request->only([
      'game_id',
      'lineup',
      'formation',
    ]);

    DB::beginTransaction();
    try {
      $result = $this->gameService->joinGame($input);

      foreach ($result as $item) {
        $this->draftService->dailyActionUpdateOrCreate(
          $item['player_id'],
          $item['season_id'],
          $item['position'],
          PlateCardActionType::LINEUP
        );
      }

      DB::commit();

      return ReturnData::setData(['success' => true])->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      DB::rollBack();

      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function joinGame2(GameJoinRequest $request)
  {
    $input = $request->only([
      'game_id',
      'lineup',
      'formation',
      'mode'
    ]);

    DB::beginTransaction();
    try {
      if (!isset($input['mode'])) {
        $input['mode'] = false;
      }

      $result = $this->gameService->joinGame2($input);

      foreach ($result as $item) {
        $this->draftService->dailyActionUpdateOrCreate(
          $item['player_id'],
          $item['season_id'],
          $item['position'],
          PlateCardActionType::LINEUP
        );
      }
      DB::commit();
      return ReturnData::setData(['success' => true])->send(Response::HTTP_OK);
    } catch (Throwable $th) {
      $this->errorLog($th->getMessage());
      DB::rollBack();
      return ReturnData::setData([
        'success' => false,
        'message' => $th->getMessage(),
        'cause' => $th->getCause()
      ])->send($th->getStatusCode());
    }
  }

  public function seasonWithGames(StadiumRequest $request)
  {
    try {
      $leagueId = $request->only(['id'])['id'];

      $result = Season::where('league_id', $leagueId)
        ->select('id', 'name', 'start_date', 'end_date', 'active')
        ->with('game:id,season_id,ga_round')
        ->get()
        ->map(function ($info) {
          foreach ($info->game as $game) {
            $game->status = $this->getStatusCount($game->id)['status'];
          }
          return $info;
        });
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      DB::rollBack();
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  // 게임리스트
  public function main(StadiumRequest $request)
  {
    $seasonId = $request->only(['id'])['id'];

    // 게임배너 limit 4
    $result['game_list'] = $this->gameService->getGameList($seasonId);
    $result['top3_player'] = $this->gameService->getGameTopCard($seasonId);

    // player vote - admin
    $vote = PredictVote::with([
      'items' => function ($query) {
        $query->oldest();
      },
      'items.question',
      'items.option_1.currentRefPlayerOverall:player_id,final_overall',
      'items.option_2.currentRefPlayerOverall:player_id,final_overall',
      'items.oneLog' => function ($query) use ($request) {
        $query->where('user_id', $request->user()->id);
      },
    ])
      ->where(function ($query) {
        $query->where('ended_at', '<=', now()->addHours(12))
          ->orWhere('ended_at', '>=', now());
      })
      ->exclude('admin_id')
      ->where('started_at', '<=', now())
      ->oldest('ended_at')
      ->first();

    // 관계명 변경이 불가능해 이렇게 처리
    $vote?->items->map(function ($info) {
      $info->my_answer = $info->oneLog;
      $info->option_1->final_overall = $info->option_1->currentRefPlayerOverall->final_overall;
      $info->option_2->final_overall = $info->option_2->currentRefPlayerOverall->final_overall;
      unset($info->option_1->currentRefPlayerOverall, $info->option_2->currentRefPlayerOverall, $info->oneLog);
    });

    if ($vote === null) {
      $vote = [];
    } else if (now()->isBefore($vote->ended_at)) {
      $vote->items->map(function ($item) {
        unset($item->answer);
      });
      $vote = $vote->toArray();
    }
    $result['predict_vote'] = $vote;


    // 퀘스트
    $quests = [];
    $dateStringSet = $this->getDateStringSet();

    QuestType::with('quest')
      ->where([
        'start_date' => $dateStringSet['this_week']['start'],
        'end_date' => $dateStringSet['this_week']['end']
      ])->get()
      ->map(function ($info) use (&$quests) {
        $quest['quest_type_id'] = $info->id;
        $quest['order_no'] = $info->order_no;
        $quest['code'] = $info->quest->code;
        $quest['name'] = $info->quest->name;
        $quest['achieve_count'] = $info->quest->achieve_count;
        $quest['reward_type'] = $info->quest->reward_type;
        $quest['reward_amount'] = $info->quest->reward_amount;

        // 1. 달성tbl 먼저 확인
        $achievement = QuestUserAchievement::where([
          ['quest_type_id', $info->id],
          ['user_id', $this->user->id]
        ]);

        if ($achievement->clone()->exists()) {
          // 1. 달성 시 해당 퀘스트의 달성 카운트를 노출
          $quest['my_count'] = $info->quest->achieve_count;
          $quest['is_claimed'] = $achievement->value('is_claimed');
        } else {
          // 2. 없으면 log 에서 count
          $quest['my_count'] = QuestUserLog::where([
            ['quest_type_id', $info->id],
            ['user_id', $this->user->id]
          ])->count();
          $quest['is_claimed'] = false;
        }
        $quests[] = $quest;
      });

    $result['quest']['this_end'] = Carbon::parse($dateStringSet['this_week']['end'])->endOfDay();
    $result['quest']['weekly'] = $quests ?? [];

    $banners = $this->getBanners([20, 29]);

    $result['banner'] = $banners;

    return ReturnData::setData($result)->send(Response::HTTP_OK);
  }

  // 해당 시즌의 전체 게임List
  public function getGames(StadiumRequest $request)
  {
    $seasonId = $request->only(['id'])['id'];
    $result = $this->gameService->getAllGameList($seasonId);
    return ReturnData::setData($result)->send(Response::HTTP_OK);
  }

  // 해당 게임의 유저랭킹
  // 삭제예정
  public function getGameTopUser(StadiumRequest $request)
  {
    $gameId = $request->only(['id'])['id'];

    $result = [
      'game' => [],
      'topUser' => []
    ];

    // 참여 인원이 있는지 여부먼저 체크
    if (!GameJoin::where('game_id', $gameId)->exists()) { // 없을 때
      $result['game'] = Game::whereId($gameId)->selectRaw('ga_round AS game_round_no,start_date,end_date')->first()->toArray();
    } else { // 있을 때 
      GameJoin::where('game_id', $gameId)
        ->where(function ($query) {
          $query->where('ranking', '<', 4)
            ->orWhere('user_id', $this->user->id);
        })
        ->get()
        ->sortBy('user.name')
        ->sortBy('ranking')
        ->map(function ($info) use ($gameId, &$result) {
          if (empty($result['game'])) {
            $result['game']['ga_round'] = $info->game->ga_round;
            $result['game']['start_date'] = $info->game->start_date;
            $result['game']['end_date'] = $info->game->end_date;
          }

          $topUser['user_name'] = $info->user->name;
          $topUser['user_photo_path'] = $info->user->userMeta->photo_path;
          $topUser['user_ranking'] = $info->ranking;
          $topUser['user_point'] = $info->point;
          $topUser['user_reward'] = $info->reward;
          $topUser['lineups'] = $this->gameService->getLineup($gameId, $info->user_id);

          if ($info->user_id === $this->user->id) {
            $result['mine'] = $topUser;
          }

          // 게임이 끝나기 전에는 topUser []
          if (!is_null($info->game->completed_at)) {
            if (!isset($result['topUser']) || count($result['topUser']) < 3) {
              $result['topUser'][] = $topUser;
            }
          }
          return $result;
        });
    }
    return ReturnData::setData($result)->send(Response::HTTP_OK);
  }

  public function giveQuestReward(StadiumRequest $request)
  {
    $questTypeId = $request->only(['id'])['id'];

    DB::beginTransaction();
    try {
      $quAchieve = QuestUserAchievement::where([
        ['user_id', $request->user()->id],
        ['quest_type_id', $questTypeId]
      ])->lockForUpdate();

      if (!$quAchieve->clone()->exists()) {
        throw new Exception($questTypeId . ' 미달성 user');
      }

      if ($quAchieve->clone()->where('is_claimed', true)->exists()) {
        throw new Exception('Reward already claimed.');
      }

      $quest = Quest::whereHas('weekQuest', function ($query) use ($questTypeId) {
        $query->whereId($questTypeId);
      })
        ->first();

      if ($quest->reward_type === QuestRewardType::PLATE_CARD) {
        // todo:카드 보상 처리
        // $quest->reward_type, $quest->reward_amount
      } else {
        $this->plusUserPointWithLog(
          $quest->reward_amount,
          PointType::GOLD,
          PointRefType::REWARD,
          'quest reward',
          $request->user()->id,
        );
        // $eventPointLog = new EventPointLog();
        // $eventPointLog->user_id = $request->user()->id;
        // $eventPointLog->point = $quest->reward_amount;
        // $eventPointLog->point_type = EventPointType::QUEST;
        // $eventPointLog->description = 'questTypeId : ' . $questTypeId;
        // $eventPointLog->save();
      }

      $quAchieve->update(['is_claimed' => true]);
      DB::commit();

      return ReturnData::setData(['success' => true])->send(Response::HTTP_OK);
    } catch (Exception $th) {
      DB::rollBack();
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  // 게임입장 모달
  public function getGamedetail(StadiumRequest $request)
  {
    $gameId = $request->only(['id'])['id'];
    try {
      $result['game'] = $this->gameService->getGameDetail($gameId);
      $allPrize = $this->makePrize($gameId);

      $result['prize'] = [];
      if (count($allPrize) > 0) {
        // range 계산
        $previous = null;
        $lastIndexPrize = [];
        foreach ($allPrize as $i => $prize) {
          if ($previous === $prize) {
            unset($lastIndexPrize[$i - 1]);
          }
          $lastIndexPrize[$i] = $prize;
          $previous = $prize;
        }

        $min = 0;
        for ($i = 0; $i <= array_key_last($lastIndexPrize); $i++) {
          if (isset($lastIndexPrize[$i])) {
            if ($min === $i) {
              $rank = $i + 1;
            } else {
              $rank = sprintf('%d,%d', $min + 1, $i + 1);
            }
            $result['prize'][][$rank] = $lastIndexPrize[$i];
            $min = $i + 1;
          }
        }
      } else {
        $allPrize = GameJoin::where([
          ['game_id', $gameId],
          ['reward', '>', 0]
        ])->orderBy('ranking')
          ->get()
          ->map(function ($info) use (&$result) {
            $result['prize'][][$info->ranking] = $info->reward;
            return $result;
          });
      }

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  // 라인업/라이브 화면 첫 진입
  public function getGameInfo(StadiumRequest $request)
  {
    $gameId = $request->only(['id'])['id'];
    try {
      $result = $this->gameService->getGameInfo($gameId);
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }


  // 라인업/라이브 변경-백업용
  public function ingameCardList($_gameId, GameCardListRequest $request)
  {
    $input = $request->only([
      'game_id',
      'club',
      'grade',
      'position',
      'player_name',
      'sort',
      'order',
      'page',
      'per_page'
    ]);
    // dd("'" . implode('\', \'', $input['club']) . "'");
    try {
      $game = Game::where([
        ['id', $_gameId],
        ['completed_at', null]
      ])
        ->whereRaw("TIMESTAMPDIFF(MINUTE,'" . Carbon::now() . "',start_date) > " . config('constant.INGAME_POSSIBLE_TIME'));

      if (!$game->exists()) {
        throw new Exception('참여할 수 없는 상태의 게임');
      }

      /**
       * @var FantasyCalculator $fpjCalculator
       */
      $fpjCalculator = app(FantasyCalculatorType::FANTASY_PROJECTION, [0]);

      $list = [];
      UserPlateCard::whereHas('plateCard', function ($query) use ($input) {
        $query->whereIn('team_id', $input['club'])
          ->when($input['player_name'], function ($nameQuery, $name) {
            $nameQuery->nameFilterWhere($name);
          });
      })
        ->with([
          'draftSeason',
          'draftTeam',
          'plateCard'
        ])
        ->where([
          ['user_id', $this->user->id],
          ['status', PlateCardStatus::COMPLETE],
          ['position', $input['position']],
          ['is_open', true]
        ])
        ->when(count($input['grade']), function ($query) use ($input) {
          $query->whereIn('card_grade', $input['grade']);
        }, function ($query) {
          $query->where('card_grade', '!=', CardGrade::NONE);
        })
        ->orderBy('card_grade')
        ->orderByDesc('draft_level')
        ->orderBy('player_name')
        ->get()
        ->map(function ($item) use (&$list, $fpjCalculator) {
          if (__canAccessUserPlateCardWithLock($item->id, GradeCardLockStatus::INGAME)) {
            $card['projection'] = $fpjCalculator->calculate(['user_plate_card_id' => $item->id, 'plate_card_id' => null]);
            $card['id'] = $item->id;
            $card['draft_level'] = $item->draft_level;
            $card['plate_card_id'] = $item->plate_card_id;
            $card['card_grade'] = $item->card_grade;
            $card['position'] = $item->position;
            $card['is_mom'] = $item->is_mom;
            $card['player_id'] = $item->plateCard->player_id;

            foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
              $card[$field] = $item->plateCard->{$field};
            }
            $card['headshot_path'] = $item->plateCard->headshot_path;

            $team['id'] = $item->draftTeam->id;
            $team['code'] = $item->draftTeam->code;
            $team['name'] = $item->draftTeam->name;
            $team['short_name'] = $item->draftTeam->short_name;
            $card['draft_team'] = $team;

            $list[] = $card;
          }
        });

      return ReturnData::setData($list)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function ingameCardList2($_gameId, GameCardListRequest $request)
  {
    $input = $request->only([
      'game_id',
      'club',
      'grade',
      'position',
      'user_plate_card_id',
      'per_page',
      'page'
    ]);
    // dd("'" . implode('\', \'', $input['club']) . "'");
    try {
      $game = Game::where([
        ['id', $_gameId],
        ['completed_at', null]
      ])->whereRaw("TIMESTAMPDIFF(MINUTE,'" . Carbon::now() . "',start_date) > " . config('constant.INGAME_POSSIBLE_TIME'));

      if (!$game->exists()) {
        throw new Exception('참여할 수 없는 상태의 게임');
      }

      $userPlateCardId = $input['user_plate_card_id'];
      if (!is_null($input['user_plate_card_id'])) {
        $input['user_plate_card_id'] = null;
      }

      [$list, $myCard] = $this->getUserCardsInGame($input, $game, null);
      $result = __setPaginateData($list, []);

      if (!is_null($userPlateCardId) && is_null($myCard)) {
        [, $myCard] = $this->getUserCardsInGame($input, $game, $userPlateCardId);
      }
      $result['my_card'] = $myCard;

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  private function getUserCardsInGame($input, $game, $userPlateCardId)
  {
    // 팀별 다음 예정 경기
    $teamCurrentMeta = RefTeamCurrentMeta::where('season_id', $game->get()->value('season_id'))
      ->pluck('next_match_team', 'team_id')->toArray();

    $config = config('fantasyingamepoint.FANTASYINGAMEPOINT_REFERENCE_TABLE_V0');
    /**
     * @var FantasyCalculator $fpjCalculator
     */
    $fpjCalculator = app(FantasyCalculatorType::FANTASY_PROJECTION, [0]);

    $myCard = null;

    $list = UserPlateCard::with('plateCard.onePlayerCurrentMeta:player_id,last_5_matches,last_player_fantasy_point')
      ->selectRaw('id,plate_card_id,draft_level,card_grade,position,is_mom')
      ->when(!is_null($userPlateCardId), function ($query) use ($userPlateCardId) {
        $query->where('id', $userPlateCardId);
      }, function ($query) use ($input, $game) {
        $query->whereHas('plateCard', function ($query) use ($input) {
          $query->currentSeason()
            ->whereIn('team_id', $input['club']);
        })
          ->whereDoesntHave('gameLineup.gameJoin', function ($query) use ($game) {
            $query->where('game_id', $game->get()->value('id'));
          })
          ->where([
            ['user_id', $this->user->id],
            ['status', PlateCardStatus::COMPLETE],
            ['position', $input['position']],
            ['is_open', true]
          ])
          ->where(function ($query) {
            $query->where('lock_status', '!=', GradeCardLockStatus::MARKET)
              ->orWhereNull('lock_status');
          })
          ->when(count($input['grade']), function ($query) use ($input) {
            $query->whereIn('card_grade', $input['grade']);
          }, function ($query) {
            $query->where('card_grade', '!=', CardGrade::NONE);
          })
          ->orderBy('card_grade')
          ->orderByDesc('draft_level')
          ->orderBy('player_name');
      })
      ->paginate($input['per_page'], ['*'], 'page', $input['page'])
      ->through(function ($item) use ($fpjCalculator, $teamCurrentMeta, $userPlateCardId, &$myCard, $config) {
        $card['projection'] = $fpjCalculator->calculate([
          'user_plate_card_id' => $item->id,
          'plate_card_id' => null
        ]);
        $card['id'] = $item->id;
        $card['draft_level'] = $item->draft_level;
        $card['plate_card_id'] = $item->plate_card_id;
        $card['card_grade'] = $item->card_grade;
        $card['card_grade_weight'] = $config['GradeWeightRate'][$item->card_grade];
        $card['position'] = $item->position;
        $card['is_mom'] = $item->is_mom;
        $card['player_id'] = $item->plateCard->player_id;

        foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
          $card[$field] = $item->plateCard->{$field};
        }

        foreach (config('commonFields.team') as $field) {
          $team[$field] = $item->plateCard->team->$field;
        }
        $card['team'] = $team;
        $card['opposing_team'] = $teamCurrentMeta[$team['id']]['opposing_team'];

        // last fp
        $lastInfo = $item->plateCard->onePlayerCurrentMeta;

        $card['last_schedule'] = null;
        if (!is_null($lastInfo) && !empty($lastInfo->last_5_matches)) {
          $schedule['id'] = $lastInfo->last_5_matches[0]['id'];
          $schedule['home'] = $lastInfo->last_5_matches[0]['home'];
          $schedule['away'] = $lastInfo->last_5_matches[0]['away'];
          $schedule['fantasy_point'] = $lastInfo->last_player_fantasy_point;
          $card['last_schedule'] = $schedule;
        }

        $levelWeightMom = $levelWeightNoMom = 0;
        foreach ($config['LevelCate'] as $column) {
          if ($item->$column > 0) {
            $levelWeightNoMom += $config['MomTable']['mom_no'][$item->column];
            $levelWeightMom += $config['MomTable']['mom_yes'][$item->column];
          }
        }

        $card['level_weight'] = $levelWeightNoMom;
        $card['mom_weight'] = $levelWeightMom - $levelWeightNoMom;

        if ((int)$userPlateCardId === $card['id']) {
          $myCard = $card;
        }
        return $card;
      });

    return [$list->toArray(), $myCard];
  }

  public function userRanking(StadiumRankingRequest $request)
  {
    $input = $request->only([
      'season',
      'q',
      'page',
      'per_page'
    ]);

    try {
      $result = [];
      $list = [];
      if ($input['q']) {
        $result['is_me'] = false;
        if ($input['q'] === $request->user()->name) {
          $result['is_me'] = true;
        }

        $gameJoinQuery = GameJoin::whereHas('game', function ($query) use ($input) {
          $query->where([
            ['season_id', $input['season']],
            ['mode', GameType::NORMAL],
            ['completed_at', '!=', null],
            ['rewarded_at', '!=', null],
          ]);
        });

        $userGameJoin = $gameJoinQuery->clone()
          ->withWhereHas('user', function ($query) use ($input) {
            $query->where('name', $input['q'])
              ->with('userMeta');
          })->first();

        if (!is_null($userGameJoin)) {
          $userId = $userGameJoin->user->id;

          $totalPrize = $gameJoinQuery
            ->clone()
            ->where('user_id', $userId)
            ->selectRaw('SUM(reward) as total_prize')
            ->value('total_prize');

          $ranking = $gameJoinQuery
            ->clone()
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('SUM(reward) > ' . $totalPrize)
            ->count();

          $list = [
            'id' => $userGameJoin->user->id,
            'name' => $userGameJoin->user->name,
            'ranking' => $ranking + 1,
            'total_prize' => (float) $totalPrize,
            'photo_path' => $userGameJoin->user->userMeta->photo_path,
            'country' => $userGameJoin->user->country,
            'nation' => $userGameJoin->user->nation,
          ];

          $userRecord = $this->gameService->getUserRecord($input['season'], $list['id']);
          foreach ($userRecord as $key => $record) {
            $list[$key] = $record;
          }

          $list['avg_point'] = 0;
          if ($userRecord['participation_count'] > 0) {
            $list['avg_point'] = $userRecord['all_points'] / $userRecord['participation_count'];
          }

          $list['major_player'] = $this->majorPlayer($list['id'], $input['season']);

          $result['total_count'] = 1;
        }
      } else {
        $redisPageKey = 'user_rank_' . $input['season'];
        $redisPageKey .= '_' . $input['page'];

        if (Redis::exists($redisPageKey)) {
          return ReturnData::setData(json_decode(Redis::get($redisPageKey), true))->send(Response::HTTP_OK);
        }

        $list = tap(
          GameJoin::whereHas('game', function ($query) use ($input) {
            $query->where([
              ['season_id', $input['season']],
              ['mode', GameType::NORMAL],
              ['completed_at', '!=', null],
              ['rewarded_at', '!=', null]
            ]);
          })
            ->selectRaw('user_id, user_name, SUM(reward) AS total_prize, RANK() OVER (ORDER BY SUM(reward) DESC) AS ranking')
            ->groupBy(['user_id', 'user_name'])
            ->orderBy('ranking')
            ->orderBy('user_name')
            ->paginate($input['per_page'], ['*'], 'page', $input['page'])
        )->map(function ($info) use ($input) {
          $info->id = $info->user_id;
          $info->total_prize = (float) $info->total_prize;
          $info->name = $info->user_name;
          $info->photo_path = $info->user->userMeta->photo_path;
          $info->country = $info->user->country;
          $info->nation = $info->user->nation;
          $userRecord = $this->gameService->getUserRecord($input['season'], $info->user_id);
          foreach ($userRecord as $key => $record) {
            $info->{$key} = $record;
          }

          $info->avg_point = 0;
          if ($info->participation_count > 0) {
            $info->avg_point = $info->all_points / $info->participation_count;
          }
          $info->major_player = $this->majorPlayer($info->user_id, $input['season']);

          unset($info->user);
          unset($info->user_id);
          unset($info->user_name);

          return $info;
        })->toArray();

        Redis::set($redisPageKey, json_encode(__setPaginateData($list, [])), 'EX', 60 * 60 * 24);

        return ReturnData::setData(__setPaginateData($list, []), $request)->send(Response::HTTP_OK);
      }

      $result['list'] = $list;

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());

      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  private function saveScore($input)
  {
    GameJoin::whereHas('game', function ($query) use ($input) {
      $query->where('season_id', $input['season'])
        ->whereNotNull('completed_at')
        ->whereNotNull('rewarded_at');
    })
      ->selectRaw(' user_id, SUM(reward) AS total_prize')
      ->groupBy('user_id')->get()
      ->map(function ($item) use ($input) {
        $userName = $item->user->name;
        $userNation = $item->user->nation;
        $redisKey = 'user_score_' . $input['season'];
        $redisnationKey = $redisKey . '_' . $userNation;
        Redis::command('zadd', [$redisKey, $item->total_prize, 'user:' . $item->user_id]);
        Redis::command('zadd', [$redisnationKey, $item->total_prize, 'user:' . $item->user_id]);
      });
  }

  private function sameRanks($_redisKey, $_score)
  {
    $sameUsers = Redis::command('zrevrangebyscore', [$_redisKey, $_score, $_score]);

    if (count($sameUsers) > 1) {
      $userRanks = [];
      foreach ($sameUsers as $user) {
        array_push($userRanks, Redis::command('zrevrank', [$_redisKey, $user]) + 1);
      }
      return min($userRanks);
    }
    return;
  }

  // 1. 라인업에서 가장 많이 선택한 카드
  // 2. 게임 점수가 높은 선수(등급포함)
  // 3.그래도 동점자가 많은 경우 최신 라인업 순서
  // 4.경기 뛴 시간이 가장 긴 순서
  private function majorPlayer($_userId, $_seasonId)
  {
    // 시즌 종료 여부
    $seasonChk = false;
    if (Season::where([
      ['id', $_seasonId],
      ['end_date', '>', now()]
    ])->exists()) {
      $seasonChk = true;
    }

    return GameLineup::whereHas('gameJoin', function ($query) use ($_seasonId, $_userId) {
      $query->where('user_id', $_userId)
        ->whereHas('game', function ($game) use ($_seasonId) {
          $game->where([
            ['season_id', $_seasonId],
            ['completed_at', '!=', null],
            ['rewarded_at', '!=', null]
          ]);
        });
    })->whereHas('userPlateCard', function ($query) use ($_userId, $_seasonId, $seasonChk) {
      $query->where('user_id', $_userId)
        ->when($seasonChk, function ($seasonWhen)  use ($_seasonId) {
          $seasonWhen->whereHas('plateCard', function ($plateCard) use ($_seasonId) {
            $plateCard->where('season_id', $_seasonId);
          });
        });
    })->selectRaw('user_plate_card_id, COUNT(user_plate_card_id) AS cnt, SUM(m_fantasy_point) AS points, MAX(created_at) AS last_created, SUM(mins_played) AS total_played')
      ->orderByDesc('total_played')
      ->orderByDesc('last_created')
      ->orderByDesc('points')
      ->orderByDesc('cnt')
      ->limit(4)
      ->groupBy('user_plate_card_id')
      ->get()
      ->map(function ($info) {
        $userPlateCard = UserPlateCard::find($info->user_plate_card_id);
        $info->headshot_path = $userPlateCard->plateCardWithTrashed->headshot_path;
        $info->draft_level = $userPlateCard->draft_level;
        $info->card_Grade = $userPlateCard->card_grade;
        return $info;
      })->toArray();
  }

  // free game -->>
  public function addFreeLineup(UpdateLineupRequest $_request)
  {
    $nextPositionMap = [
      PlayerPosition::ATTACKER => PlayerPosition::MIDFIELDER,
      PlayerPosition::MIDFIELDER => PlayerPosition::DEFENDER,
      PlayerPosition::DEFENDER => PlayerPosition::GOALKEEPER,
      PlayerPosition::GOALKEEPER => null,
    ];

    DB::beginTransaction();
    try {
      $input = $_request->all();

      // 타겟 position validation
      $positionRemainCount = $this->freeGameService->validatePosition($input['game_id'], $input['position']);

      $cardMetaInst = FreeCardMeta::withWhereHas('freeCardShuffleMemory', function ($query) use ($input) {
        $query->with('plateCard')->where([
          ['id', $input['shuffle_id']],
          ['position', $input['position']],
        ]);
      })->where([
        ['user_id', $this->user['id']],
        ['game_id', $input['game_id']]
      ]);

      // 요청에 대한 validation(shuffle_id, position)
      $cardMeta = $cardMetaInst->first();

      if ($cardMeta === null) {
        throw new Exception('잘못된 요청 파라미터');
      }

      $selectCard = $cardMeta->toArray()['free_card_shuffle_memory'][0];

      $fglmInst = (new FreeGameLineupMemory);

      $fglmInst->player_id = $selectCard['plate_card']['player_id'];
      $fglmInst->formation_place = $selectCard['formation_place'];
      $fglmInst->mp = $selectCard['mp'];
      $fglmInst->goals = $selectCard['goals'];
      $fglmInst->assists = $selectCard['assists'];
      $fglmInst->season_id = $selectCard['season_id'];
      $fglmInst->team_id = $selectCard['team_id'];
      $fglmInst->headshot_path = $selectCard['headshot_path'];
      $fglmInst->projection = $selectCard['projection'];
      $fglmInst->special_skills = $selectCard['special_skills'];
      $fglmInst->draft_schedule_id = $selectCard['draft_schedule_id'];
      $fglmInst->plate_card_id = $selectCard['plate_card']['id'];
      $fglmInst->free_card_meta_id = $selectCard['free_card_meta_id'];
      $fglmInst->schedule_id = $selectCard['schedule_id'];
      $fglmInst->level_weight = $selectCard['level_weight'];
      $fglmInst->rating = $selectCard['rating'];
      $fglmInst->is_mom = $selectCard['is_mom'];
      $fglmInst->draft_level = $selectCard['draft_level'];
      $fglmInst->attacking_level = $selectCard['attacking_level'];
      $fglmInst->goalkeeping_level = $selectCard['goalkeeping_level'];
      $fglmInst->passing_level = $selectCard['passing_level'];
      $fglmInst->defensive_level = $selectCard['defensive_level'];
      $fglmInst->duel_level = $selectCard['duel_level'];
      $fglmInst->card_grade = $selectCard['card_grade'];
      $fglmInst->position = $selectCard['position'];
      $fglmInst->save();


      //   $item['player_name'] = $item['plate_card']['match_name'];
      // $item['season_name'] = $item['season']['name'];
      // $item['team_name'] = $item['team']['official_name'];
      // $item['league_code'] = $item['plate_card']['league']['league_code'];


      FreeCardShuffleMemory::where('free_card_meta_id', $cardMeta->id)->forceDelete();

      $result = [
        'shuffle_remained' => $this->freeGameService->getShuffleRemainCount($input['game_id'], $cardMeta->shuffle_count),
        'lineup_full' => false,
        'shuffle' => null,
        'lineup' => null,
      ];

      if ($positionRemainCount === 1) {
        $result['lineup_full'] = true;
      }
      // $this->freeGameService->makeShuffleCard($cardMeta->id, $input['game_id'], $targetPosition);

      // $cardSet = $this->freeGameService->getFreeCardMemories($input['game_id']);
      // $result['shuffle'] = $cardSet['shuffle'];
      // $result['lineup'] = $cardSet['lineup'];

      DB::commit();
      return ReturnData::setData([])->send(Response::HTTP_OK);
    } catch (Exception $th) {
      DB::rollBack();
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }



  public function getFreeLineup(FreeCardRequest $_request)
  {
    // 무료게임 페이지 진입
    $input = $_request->only([
      'game_id',
      'position',
      'formation_place'
    ]);

    DB::beginTransaction();
    try {
      $this->freeGameService->validateFormationPlaceInFreeLineupMemory($input);
      $result = $this->freeGameService->getFreeCardMemories($input['game_id'], $input['position'], $input['formation_place']);
      DB::commit();
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      DB::rollBack();
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function submitFreeLineup(FreeCardRequest $_request)
  {
    $input = $_request->only([
      'game_id',
    ]);


    DB::beginTransaction();
    try {

      $this->gameService->joinFreeGame($input);
      // $lineups = FreeCardMeta::where([
      //   ['user_id', $this->user->id],
      //   ['game_id', $input['game_id']],
      // ])->with('freeGameLineupMemory')->first();
      // foreach ($lineups->toArray()['free_game_lineup_memory'] as $idx => $lineup) {
      //   unset($lineup['free_card_meta_id']);
      //   $lineup['user_id'] = $this->user->id;
      //   FreeGameLineup::insert($lineup);
      // }

      // $freeCardMetaId = $lineups->id;

      // FreeGameLineupMemory::whereId($freeCardMetaId)->forceDelete();
      DB::commit();
      return ReturnData::setData([])->send(Response::HTTP_OK);
    } catch (Exception $th) {
      DB::rollBack();
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function openShuffleCard(OpenFreeShuffleCardRequest $_request)
  {
    $input = $_request->all();
    try {
      $freeMetaWithShuffle =  FreeCardMeta::where([
        ['game_id', $input['game_id']],
        ['user_id', $this->user['id']]
      ])->withWhereHas('freeCardShuffleMemory', function ($query) use ($input) {
        $query->where('is_open', false)->when($input['shuffle_id'], function ($query) use ($input) {
          $query->whereId($input['shuffle_id']);
        });
      })->first();
      if ($freeMetaWithShuffle === null) {
        throw new Exception('selected shuffle card is not available or aleady opened');
      }

      FreeCardShuffleMemory::when($input['shuffle_id'] !== null, function ($query) use ($input) {
        $query->whereId($input['shuffle_id']);
      })->where('free_card_meta_id', $freeMetaWithShuffle->id)->get()->map(function ($item) {
        $item->is_open = true;
        $item->save();
      });

      // $this->freeGameService->getFreeCardMemories($input['game_id']);
      return ReturnData::setData([])->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }


  public function getShuffleFreeCard(FreeCardRequest $_request)
  {
    $input = $_request->only([
      'game_id',
    ]);

    $result = ['shuffle_remained' => null, 'shuffle' => null];

    DB::beginTransaction();
    try {

      $freeCardMetas = $this->freeGameService->getFreeCardMetas($input['game_id'], true);

      $result['shuffle'] = FreeCardShuffleMemory::with('plateCard.refPlayerOverall:id,player_id,final_overall')
        ->where(
          'free_card_meta_id',
          $freeCardMetas['id']
        )->get()->map(function ($item) {
          $item->final_overall = $item->plateCard?->refPlayerOverall()->withWhereHas('schedule', function ($query) {
            $query->latest()->limit(1);
          })->first()->final_overall;
          unset($item->plateCard->currentRefPlayerOverall);
          return $item;
        });

      $result['shuffle_remained'] = $this->freeGameService->getShuffleRemainCount($input['game_id'], $freeCardMetas['shuffle_count']);

      DB::commit();
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      DB::rollBack();
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  // private function shuffleCommon($_gameId, $_useShuffleCount = true)
  // {


  //   $pF = $this->freeGameService->getCurrentShuffleInfo($_gameId);
  //   if ($pF === null) {
  //     return ReturnData::setError('There are no shuffle cards')->send(Response::HTTP_BAD_REQUEST);
  //   }
  //   $targetPosition = $pF['position'];
  //   $formationPlace = $pF['formation_place'];
  //   $freeCardMetaId = $this->freeGameService->getFreeCardMetas($_gameId)['id'];
  //   if ($_useShuffleCount && !$this->freeGameService->shuffleCountCheck($_gameId, $freeCardMetaId)) {
  //     throw new Exception('have no chance to shuffle!', Response::HTTP_BAD_REQUEST);
  //   } else {
  //     $fanPoint = UserMeta::where('user_id', $this->user['id'])->value('fan_point');


  //     // fanfan piont 체크
  //   }
  //   // $result = ['shuffle_remained' => $this->freeGameService->getShuffleRemainCount($input['game_id']) - 1];
  //   $this->freeGameService->makeShuffleCard($freeCardMetaId, $_gameId, $targetPosition, $formationPlace);
  //   $this->freeGameService->shuffleCountUp($freeCardMetaId);
  // }

  public function shuffleFreeCard(FreeCardRequest $_request)
  {
    $input = $_request->only([
      'game_id',
      'shuffle_type',
    ]);

    DB::beginTransaction();
    try {
      $this->freeGameService->shuffleCommon($input['game_id'], $input['shuffle_type']);
      // $pF = $this->freeGameService->getCurrentShuffleInfo($input['game_id']);
      // if ($pF === null) {
      //   return ReturnData::setError('There are no shuffle cards')->send(Response::HTTP_BAD_REQUEST);
      // }
      // $targetPosition = $pF['position'];
      // $formationPlace = $pF['formation_place'];
      // $freeCardMetaId = $this->freeGameService->getFreeCardMetas($input['game_id'])['id'];
      // if (!$this->freeGameService->shuffleCountCheck($input['game_id'], $freeCardMetaId)) {
      //   throw new Exception('have no chance to shuffle!', Response::HTTP_BAD_REQUEST);
      // }
      // $result = ['shuffle_remained' => $this->freeGameService->getShuffleRemainCount($input['game_id']) - 1];
      DB::commit();
      return ReturnData::setData([])->send(Response::HTTP_OK);
    } catch (Throwable $th) {
      DB::rollBack();
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function pointShuffleFreeCard() {}

  public function makeShuffleFreeCard(FreeCardRequest $_request)
  {
    $input = $_request->only([
      'game_id',
      'position',
      'formation_place'
    ]);

    DB::beginTransaction();
    try {
      $this->freeGameService->validateFormationPlaceInFreeLineupMemory($input);
      $this->freeGameService->getFreeCardMemories($input['game_id'], $input['position'], $input['formation_place']);
      DB::commit();
      return ReturnData::setData([])->send(Response::HTTP_OK);
    } catch (Exception $th) {
      DB::rollBack();
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }



  public function predictVote(PredictVoteRequest $request)
  {
    $input = $request->only([
      'vote_id',
      'item',
    ]);

    try {
      $vote = PredictVote::with('items')
        ->withCount([
          'logs' => function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
          }
        ])->find($input['vote_id']);

      // 이미 참여한 투표
      if ($vote->logs_count > 0) {
        throw new Exception('You\'ve already participated in the vote');
      }

      DB::beginTransaction();

      foreach ($input['item'] as $item) {
        $validAnswer = PredictVoteItem::where('id', $item['id'])
          ->where(function ($query) use ($item) {
            $query->where('option1', $item['answer'])
              ->orWhere('option2', $item['answer']);
          })
          ->exists();
        if (!$validAnswer) {
          throw new Exception('Invalid Answer');
        }
        $answerLog = new PredictVoteLog();
        $answerLog->predict_vote_item_id = $item['id'];
        $answerLog->answer = $item['answer'];
        $answerLog->user_id = $request->user()->id;
        $answerLog->save();
      }

      DB::commit();
    } catch (Throwable $th) {
      DB::rollback();
      return ReturnData::setError($th->getMessage())->send(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return ReturnData::send(Response::HTTP_OK);
  }

  public function gameLogPlayer(StadiumGameLogRequest $request)
  {
    $input = $request->only([
      'league',
    ]);

    try {
      $result = [];

      //summary
      $gameModes = ['real', 'sponsor', 'free'];

      foreach ($gameModes as $mode) {
        $result['summary'][$mode] = $this->getSummary($mode, $input['league']);
      }

      //avg_point
      $result['avg_point']['total'] = $this->getAvgPoint($input['league']);
      $result['avg_point']['user'] = $this->getAvgPoint($input['league'], $this->user->id);

      //best_players
      $result['best_player'] = $this->getBestPlayer($input['league']);

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());

      return ReturnData::setError($th->getMessageEx())->send($th->getCode());
    }
  }

  private function getSummary($_mode, $_leagueIds)
  {
    $_mode = ($_mode === 'real') ? GameType::NORMAL : $_mode;

    $summaryQuery = GameJoin::with(['game.gameSchedule.gamePossibleSchedule'])
      ->where('user_id', $this->user->id)
      ->whereHas('game', function ($gameQuery) use ($_mode) {
        $gameQuery->where('mode', $_mode)
          ->whereNotNull('completed_at');
      })
      ->when($_leagueIds, function ($gameQuery, $leagueIds) {
        $gameQuery->wherehas('game.season', function ($leagueQuery) use ($leagueIds) {
          $leagueQuery->whereIn('league_id', $leagueIds);
        });
      });

    $winCount = $summaryQuery->clone()->where('reward', '>', 0)->count();
    $joinCount = $summaryQuery->count();
    $totalPrize = $summaryQuery->clone()->where('reward', '>', 0)->sum('reward');

    $winningRate = $joinCount != 0 ? $winCount / $joinCount * 100 : 0;

    return [
      'winning_rate' => $winningRate,
      'win_count' => $winCount,
      'total_prize' => (int) $totalPrize,
    ];
  }

  private function getAvgPoint($_leagueIds, $_userId = null)
  {
    return GameLineup::query()
      ->selectRaw('position, CAST(ROUND(AVG(m_fantasy_point), 1) AS FLOAT) as average_point')
      ->whereHas('gamePossibleSchedule', function ($gameQuery) {
        $gameQuery->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED]);
      })
      ->when($_leagueIds, function ($gameJoinQuery, $leagueIds) {
        $gameJoinQuery->whereHas('gameJoin.game.season', function ($leagueQuery) use ($leagueIds) {
          $leagueQuery->whereIn('league_id', $leagueIds);
        });
      })
      ->when($_userId, function ($gameJoinQuery, $userId) {
        $gameJoinQuery->whereHas('gameJoin', function ($userQuery) use ($userId) {
          $userQuery->where('user_id', $userId);
        });
      })
      ->groupBy('position')
      ->pluck('average_point', 'position');
  }

  private function getBestPlayer($_leagueIds)
  {
    $sub = GameLineup::whereHas('gameJoin', function ($gameJoinQuery) {
      $gameJoinQuery->where('user_id', $this->user->id)
        ->whereHas('game', function ($gameQuery) {
          $gameQuery->where('mode', GameType::NORMAL);
        });
    })->has('schedule.league')
      ->when($_leagueIds, function ($seasonQuery, $leagueIds) {
        $seasonQuery->whereHas('gameJoin.game.season', function ($leagueQuery) use ($leagueIds) {
          $leagueQuery->whereIn('league_id', $leagueIds);
        });
      })
      ->selectRaw('rank() over (order by m_fantasy_point desc) as ranking, user_plate_card_id, schedule_id, player_id');

    $playerItem = DB::query()
      ->fromSub($sub, 'sub')
      ->where('ranking', 1)
      ->get()
      ->map(function ($info) use ($_leagueIds) {
        // 제출 횟수, 판타지포인트 평균
        $detail = GameLineup::with('userPlateCard')
          ->where('user_plate_card_id', $info->user_plate_card_id)
          ->whereHas('gameJoin', function ($gameJoinQuery) {
            $gameJoinQuery->where('user_id', $this->user->id)
              ->whereHas('game', function ($gameJoinQuery) {
                $gameJoinQuery->where('mode', GameType::NORMAL);
              });
          })
          ->when($_leagueIds, function ($gameJoinQuery, $leagueIds) {
            $gameJoinQuery->whereHas('gameJoin.game.season', function ($leagueQuery) use ($leagueIds) {
              $leagueQuery->whereIn('league_id', $leagueIds);
            });
          })
          ->groupBy('user_plate_card_id')
          ->selectRaw('COUNT(*) as count, CAST(ROUND(AVG(m_fantasy_point), 1) AS FLOAT) average_fantasy_point, user_plate_card_id')
          ->first();
        $info->count = $detail->count;
        $info->average_fantasy_point = $detail->average_fantasy_point;
        $info->draft_level = $detail->userPlateCard->draft_level;
        $info->card_grade = $detail->userPlateCard->card_grade;

        return $info;
      })
      ->sortByDesc('card_grade')
      ->sortByDesc('draft_level')
      ->sortByDesc('average_fantasy_point')
      ->sortByDesc('count')
      ->first();

    $bestPlayer = null;
    if (!is_null($playerItem)) {
      $bestGameLineup = gameLineup::with([
        'schedule:id,season_id,league_id,home_team_id,away_team_id,score_home,score_away,winner,started_at,round',
        'schedule.home:' . implode(',', config('commonFields.team')),
        'schedule.away:' . implode(',', config('commonFields.team')),
        'schedule.league',
        'schedule.season',
        'userPlateCard.plateCardWithTrashed',
        'gameJoin.game',
      ])->has('schedule.league')
        ->selectRaw('id, game_join_id, user_plate_card_id, schedule_id, m_fantasy_point')
        ->where([
          ['user_plate_card_id', $playerItem->user_plate_card_id],
          ['schedule_id', $playerItem->schedule_id]
        ])
        ->first();

      $bestPlayer = $bestGameLineup->toArray();

      $bestPlayer['best_game'] = array_merge($bestGameLineup->schedule->toArray(), [
        'game_round_no' => $bestGameLineup->gameJoin->game->game_round_no,
        'league_country_code' => $bestGameLineup->schedule->league->country_code,
        'league_code' => $bestGameLineup->schedule->league->league_code,
        'season_name' => $bestGameLineup->schedule->season->name,
        'game_participant_count' => $playerItem->count,
        'average_fantasy_point' => $playerItem->average_fantasy_point
      ]);

      foreach (['home', 'away'] as $teamSide) {
        $bestPlayer['best_game'][$teamSide]['is_player_team'] = $bestGameLineup['schedule'][$teamSide]['id'] === $bestGameLineup->userPlateCard->plateCardWithTrashed->team->id;
      }

      unset($bestPlayer['user_plate_card'], $bestPlayer['schedule'], $bestPlayer['best_game']['league'], $bestPlayer['best_game']['season'], $bestPlayer['game_join']);
    }

    return $bestPlayer;
  }

  public function gameLogList(StadiumGameLogRequest $request)
  {
    $input = $request->only([
      'league',
      'type',
      'start_date',
      'end_date',
      'page',
      'per_page',
    ]);
    try {
      $gameType = ($input['type'] === 'real') ? GameType::NORMAL : $input['type'];

      $game = Game::whereHas(
        'gameSchedule.gamePossibleSchedule',
        function ($gameQuery) use ($input) {
          $gameQuery->whereIn('status', ScheduleStatus::NORMAL)
            ->when($input['league'], function ($leagueQuery) use ($input) {
              $leagueQuery->whereIn('league_id', $input['league']);
            })->has('schedule');
        }
      )->has('gameJoin');
      $gameJoinTblName = GameJoin::getModel()->getTable();
      $gameStatusArr = [ScheduleStatus::PLAYING, ScheduleStatus::FIXTURE, ScheduleStatus::PLAYED];

      $gameList = tap(
        GameJoin::with('game.season.league')
          ->selectRaw('*,
	          #0: Playing / 1: Fixture / 2: Played
                      CASE
		          WHEN game.start_date <= now()
		          AND game.completed_at IS NULL THEN 0
		          WHEN game.start_date > now()
		          AND game.completed_at IS NULL THEN 1
		          WHEN game.completed_at IS NOT NULL THEN 2
	          END AS status_index')
          ->joinSub($game, 'game', function ($join) use ($gameJoinTblName) {
            $join->on($gameJoinTblName . '.game_id', 'game.id');
          })
          ->where('user_id', $this->user->id)
          ->whereBetween('game_joins.created_at', [
            Carbon::parse($input['start_date'])->startOfDay(),
            Carbon::parse($input['end_date'])->endOfDay()
          ])
          ->when($gameType, function ($gameTypeQuery, $gameType) {
            $gameTypeQuery->where('mode', $gameType);
          })
          ->orderBy('status_index')
          ->orderByDesc('game_joins.created_at')
          ->paginate($input['per_page'], ['*'], 'page', $input['page'])
      )->map(function ($item) use ($gameStatusArr) {
        $item->participants = $item->game->gameJoin->count();
        $item->prize_pool = $item->game->rewards;
        $item->start_in = $item->game->start_date;

        $item->status = $gameStatusArr[$item->status_index];
        $item->game_type = $item->game->mode === GameType::NORMAL ? 'real' : $item->game->mode;
        $item->league_code = $item->game->season->league->league_code;
        $item->country_code = $item->game->season->league->country_code;
        $item->game_no = $item->game->game_round_no;
        $item->game_start_date = $item->game->start_date;
        $item->game_end_date = $item->game->end_date;
        $item->submit_date = $item->created_at;

        unset($item->formation, $item->game);

        return $item;
      })->toArray();

      return ReturnData::setData(__setPaginateData($gameList, []), $request)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());

      return ReturnData::setError($th->getMessageEx())->send($th->getCode());
    }
  }
}
