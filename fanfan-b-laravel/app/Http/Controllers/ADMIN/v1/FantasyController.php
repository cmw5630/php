<?php

namespace App\Http\Controllers\ADMIN\v1;

use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Fantasy\AllGameJoinListRequest;
use App\Http\Requests\Admin\Fantasy\LeaguesRequest;
use App\Http\Requests\Admin\Fantasy\GameJoinListRequest;
use App\Http\Requests\Admin\Fantasy\GameMakeRequest;
use App\Http\Requests\Admin\Fantasy\PossibleScheduleRequest;
use App\Http\Requests\Admin\Fantasy\QuestLogRequest;
use App\Http\Requests\Admin\Fantasy\QuestRequest;
use App\Http\Requests\Admin\Fantasy\ScheduleListRequest;
use App\Http\Requests\Admin\Fantasy\RefSeedReqeust;
use App\Jobs\JobPlateCardChangeUpdate;
use App\Jobs\JobUpdateRefSeed;
use ReturnData;
use Symfony\Component\HttpFoundation\Response;
use App\Libraries\Classes\Exception;
use App\Libraries\Traits\GameTrait;
use App\Models\data\Schedule;
use App\Models\data\SeasonTeam;
use App\Models\game\GameJoin;
use App\Models\game\GamePossibleSchedule;
use App\Models\game\PlateCard;
use App\Models\game\Quest;
use App\Models\game\QuestType;
use App\Models\game\QuestUserAchievement;
use App\Models\game\QuestUserLog;
use App\Models\meta\RefPlateGradePrice;
use App\Models\meta\RefPowerRankingQuantile;
use App\Models\meta\RefTeamTierBonus;
use App\Models\data\League;
use App\Services\Admin\FantasyService;
use App\Services\Data\DataService;
use Excel;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;

class FantasyController extends Controller
{
  use GameTrait;

  private FantasyService $fantasyService;
  private DataService $dataService;

  public function __construct(FantasyService $_fantasyService, DataService $_dataService)
  {
    $this->fantasyService = $_fantasyService;
    $this->dataService = $_dataService;
  }

  public function leagues()
  {
    try {
      $result = $this->dataService->leaguesQuery()
        ->withoutGlobalScopes()
        ->select('id', 'name', 'league_code', 'order_no', 'country', 'country_id', 'country_code', 'is_friendly', 'status', 'schedule_status')
        ->get()
        ->map(function ($info) {
          $info['seasons'] = $info->seasons;
          return $info;
        });

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  /**
   *  leagues 정보변경 - (순서/상태/스케줄상태)
   */
  public function leaguesUpdate(LeaguesRequest $request)
  {
    DB::beginTransaction();
    try {
      $input = $request->only('lists');

      $leaguesLists = json_decode($input['lists'], true);
      if (is_array($leaguesLists)) {
        foreach ($leaguesLists as $value) {
          League::withoutGlobalScopes()
            ->where('id', $value['id'])
            ->update(['order_no' => $value['order_no'], 'schedule_status' => $value['schedule_status'], 'status' => $value['status']]);
        }
      }

      DB::commit();
      return ReturnData::setData([])->send(Response::HTTP_OK);
    } catch (Exception $th) {
      DB::rollBack();
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function getLeagueWithTeams(Request $request)
  {
    $isCurrent = false;
    if (!empty($request->all())) {
      $isCurrent = $request->only(['is_current'])['is_current'];
    }

    try {
      $clubList = [];
      SeasonTeam::query()
        ->when($isCurrent, function ($query) {
          $query->currentSeason();
        })
        ->has('season.league')->get()->map(function ($item) use (&$clubList) {
          $team['id'] = $item->team_id;
          $team['name'] = $item->name;
          $team['code'] = $item->code;
          $team['short_name'] = $item->short_name;
          $clubList[$item->season->league->id][] = $team;
        });

      $result = $this->dataService->leaguesQuery()
        ->select('id', 'name', 'league_code', 'order_no', 'country', 'country_id', 'country_code', 'is_friendly', 'status')
        ->get()
        ->map(
          function ($item) use ($clubList) {
            $item->club = $clubList[$item->id];
            return $item;
          }
        )
        ->toArray();
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function schedules(ScheduleListRequest $request)
  {
    $input = $request->only([
      'season',
      'status',
      'sort',
      'order',
      'round'
    ]);

    try {
      $result = $this->fantasyService->getScheduleList($input);
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function scheduleDetail(ScheduleListRequest $request)
  {
    $input = $request->only([
      'season',
      'round',
      'sort',
      'order',
    ]);

    try {
      $result = $this->fantasyService->getScheduleDetail($input);
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  // 생성된 게임 리스트
  public function games(ScheduleListRequest $request)
  {
    $input = $request->only([
      'season',
      'status',
    ]);

    try {
      $result = $this->fantasyService->getGameList($input);
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function gameDetail($_gameId)
  {
    try {
      $result = $this->fantasyService->getGameDetail($_gameId);
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function allGameJoinList(AllGameJoinListRequest $request)
  {
    $input = $request->only([
      'season',
      'q',
      'per_page',
      'page'
    ]);

    try {
      $list = tap(
        GameJoin::with('user')
          ->when($input['season'], function ($query, $season) {
            $query->whereHas('game', function ($game) use ($season) {
              $game->where('season_id', $season);
            });
          })->when($input['q'], function ($query, $keyword) {
            $query->whereHas('user', function ($user) use ($keyword) {
              $user->whereLike(['email', 'name'], $keyword);
            });
          })
          ->latest()
          ->paginate($input['per_page'], ['*'], 'page', $input['page'])
      )->map(function ($item) {
        $item->user_id = $item->user->id;
        $item->user_email = $item->user->email;
        $item->user_name = $item->user->name;
        $item->league_code = $item->game->season->league->league_code;
        $item->round = $item->game->ga_round;
        $item->joined_at = $item->created_at;
        $item->game_status = $this->getStatusCount($item->game_id)['status'];

        unset($item->user);
        unset($item->game);
      })->toArray();
      return ReturnData::setData(__setPaginateData($list, []), $request)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function gameJoins(GameJoinListRequest $request)
  {
    $input = $request->only([
      'game_id',
      'q',
      'per_page',
      'page'
    ]);

    try {
      $result = $this->fantasyService->getGameJoins($input);
      return ReturnData::setData(__setPaginateData($result, []), $request)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function gameJoinDetail($_gameJoinId)
  {
    try {
      $result = $this->fantasyService->getGameJoinDetail($_gameJoinId);
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function checkGameJoin($_gameId)
  {
    try {
      $result = $this->fantasyService->checkGameJoin($_gameId);
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  // game에 묶을 schedule List
  public function schedulesForGame(ScheduleListRequest $request)
  {
    $input = $request->only([
      'season',
    ]);

    try {
      $list = [];
      $list['schedules'] = Schedule::with([
        'home:' . implode(',', config('commonFields.team')),
        'away:' . implode(',', config('commonFields.team')),
      ])
        ->has('home')
        ->has('away')
        ->where([
          ['started_at', '>', now()],
          ['season_id', $input['season']],
          ['status', ScheduleStatus::FIXTURE],
        ])
        ->whereNotNull('ga_round')
        ->select('id', 'league_id', 'round', 'ga_round', 'started_at', 'home_team_id', 'away_team_id', 'status')
        ->orderBy('ga_round')
        ->get()
        ->toArray();
      return ReturnData::setData($list)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function makeGame(GameMakeRequest $request)
  {
    $input = $request->only([
      'game_id',
      'season',
      'schedules',
      'mode',
      'rewards',
      'prize_rate',
      'is_popular',
      'banner',
      'reservation_time',
      'user_id'
    ]);
    if (!empty($banner = $request->file('banner'))) {
      $input['banner'] = $banner;
    }

    try {
      // $result = $this->fantasyService->makeGame($input);
      $result = $this->fantasyService->makeGame2($input);
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function cancelGame($_gameId)
  {
    try {
      $this->fantasyService->cancelGame($_gameId);
      return ReturnData::send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function getQuestCodes()
  {
    try {
      $result = Quest::select('id', 'code', 'name', 'period')->get()->toArray();
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function getQuests()
  {
    try {
      // 날짜 계산
      $dateStringSet = $this->getDateStringSet();

      $questBoards = [];
      QuestType::where(function ($query) use ($dateStringSet) {
        $query->where([
          'start_date' => $dateStringSet['this_week']['start'],
          'end_date' => $dateStringSet['this_week']['end']
        ]);
      })
        ->orderBy('order_no')
        ->get()
        ->map(function ($info) use (&$questBoards) {
          $board['id'] = $info->id;
          $board['quest_id'] = $info->quest_id;
          $board['order_no'] = $info->order_no;
          $board['code'] = $info->quest->code;
          $board['name'] = $info->quest->name;
          $board['rewards'] = $info->rewards;
          $board['achieve_count'] = $info->achieve_count;
          $board['period'] = $info->period;
          $board['start_date'] = $info->start_date;
          $board['end_date'] = $info->end_date;
          $board['updated_at'] = $info->updated_at;

          $questBoards['current'][] = $board;
        });

      // reservation
      Quest::get()->map(function ($info) use (&$questBoards, $dateStringSet) {
        $nextQuest = QuestType::where('quest_id', $info->id)
          ->where(function ($query) use ($dateStringSet) {
            $query->where([
              'start_date' => $dateStringSet['next_week']['start'],
              'end_date' => $dateStringSet['next_week']['end']
            ]);
          })
          ->first();
        $board['id'] = $nextQuest?->id;
        $board['quest_id'] = $info->id;
        $board['order_no'] = $nextQuest?->order_no;
        $board['code'] = $info->code;
        $board['name'] = $info->name;
        $board['rewards'] = $nextQuest?->rewards;
        $board['achieve_count'] = $nextQuest?->achieve_count;
        $board['period'] = $info->period;
        $board['start_date'] = $nextQuest?->start_date;
        $board['end_date'] = $nextQuest?->end_date;
        $board['updated_at'] = $nextQuest?->updated_at;

        $questBoards['reservation'][] = $board;
      });

      $result['current'] = $questBoards['current'] ?? [];
      $result['reservation'] = $questBoards['reservation'] ?? [];
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function saveQuest(QuestRequest $request)
  {
    $input = $request->only(['quests', 'active']);
    try {
      $result = $this->fantasyService->saveQuest($input);
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  /** 
   * 퀘스트 회원 진행상태 반환     
   * [진행중 - keep] 종료시간내에 미성공 . 진행중
   * [성공 - success] 종료일시내에 성공 카운트수 확인   
   * [실패 - fail] 종료시간초과, 성공 카운트 부족
   */
  private function setQuestTypeStatus($item)
  {
    $typeStatus = ''; //진행상태값 세팅
    $toDate = now();
    $startDate = Carbon::parse($item->start_date)->startOfDay();
    $endDate = Carbon::parse($item->end_date)->endOfDay();

    if ($toDate->isBetween($startDate, $endDate)) {
      $typeStatus = 'keep';
    }
    if ($item->my_count >= $item->achieve_count) {
      $typeStatus = 'success';
    }
    if ($item->my_count < $item->achieve_count && $toDate > $endDate) {
      $typeStatus = 'fail';
    }

    return $typeStatus;
  }

  /** 
   * 회원 퀘스트 참여로그 리스트
   *  - period : main-분류 [weekly,monthly]
   *  - quest_id : sub-구분(no)
   */
  public function getQuestLogs(QuestLogRequest $request)
  {
    $input = $request->only([
      'q',
      'per_page',
      'page',
      'quest_id', //필터 2 : 구분 (퀘스트타입)
      'period' //필터 1 : 분류 (weekly/monthly)
    ]);

    try {
      $questIdLists = []; // 분류에 따른 퀘스트 ID리스트        
      /** ---------------------------------------------------------------------------  
       *  [분류] 퀘스트 ID 추출 : 특정 퀘스트타입 선택 OR 분류별 퀘스트전체조회 
       * --------------------------------------------------------------------------- */
      if ($request['quest_id']) {
        array_push($questIdLists, $input['quest_id']);
      } else {
        if ($request['period']) {
          $questIdLists = Quest::where('period', $request['period'])
            ->pluck('id')
            ->toArray();
        }
      }

      /** ---------------------------------------------------------------------------  
       *  [퀘스트구분] 분류 > 구분 선택후 퀘스트타입ID 추출 : Array          
       * --------------------------------------------------------------------------- */
      $questTypeLists = [];

      if (!empty($questIdLists)) {
        $questTypeLists = QuestType::whereIn('quest_id', $questIdLists)
          ->pluck('id')
          ->toArray();
      }

      /** ---------------------------------------------------------------------------  
       * [로그 리스트] 회원::퀘스트 통합데이터 
       * --------------------------------------------------------------------------- */
      $list = tap(
        QuestUserLog::with([
          'questType.quest',
          'user' => function ($query) {
            $query->withoutGlobalScope('excludeWithdraw');
          }
        ])
          ->when($questTypeLists, function ($query, $keylist) {
            $query->whereIn('quest_type_id', $keylist);
          })
          ->when($input['q'], function ($query, $keyword) {
            $query->whereHas('user', function ($user) use ($keyword) {
              $user->withoutGlobalScope('excludeWithdraw')->whereLike(['email', 'name'], $keyword);
            });
          })
          // 퀘스트최근 도전 일시 추가 
          ->selectRaw('user_id, quest_type_id, MAX(updated_at)  as updated_at')
          ->groupBy('quest_type_id', 'user_id')
          ->orderByDesc('quest_type_id')
          ->paginate($input['per_page'], ['*'], 'page', $input['page'])
      )->map(function ($item) {
        $item->user_id = $item->user->id;
        $item->user_email = $item->user->email;
        $item->user_name = $item->user->name;
        $item->period = $item->questType->period;
        $item->quest_code = $item->questType->quest->code;
        $item->quest_name = $item->questType->quest->name;
        $item->achieve_count = $item->questType->achieve_count;
        $item->start_date = $item->questType->start_date;
        $item->end_date = $item->questType->end_date;

        $item->my_count = QuestUserLog::where([
          ['user_id', $item->user->id],
          ['quest_type_id', $item->quest_type_id]
        ])->count();
        $item->rewards = $item->questType->rewards;

        $item->started_at = QuestUserLog::where([
          ['user_id', $item->user->id],
          ['quest_type_id', $item->quest_type_id]
        ])->oldest()->limit(1)->value('created_at');

        $item->completed_at = QuestUserAchievement::where([
          ['user_id', $item->user->id],
          ['quest_type_id', $item->quest_type_id]
        ])->value('created_at');
        $item->last_updated_at = $item->updated_at;

        $item->is_claimed = QuestUserAchievement::where([ // 보상수령 여부
          ['user_id', $item->user->id],
          ['quest_type_id', $item->quest_type_id]
        ])->value('is_claimed');

        $item->type_status = $this->setQuestTypeStatus($item); // 진행상태          
        unset($item->user);
        unset($item->questType);
      })->toArray();
      return ReturnData::setData(__setPaginateData($list, []), $request)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  /**
   * [Modal] 퀘스트 로그 상세보기  
   * 회원ID, 퀘스트타입ID 의  퀘스트 완료까지의 로그 리스트
   */

  public function getQuestLogDetail(QuestLogRequest $request)
  {
    $input = $request->only([
      'user_id',
      'quest_type_id',
      'completed_at'
    ]);

    try {
      $list = QuestUserLog::with('user')
        ->where('user_id', $input['user_id'])
        ->where('quest_type_id', $input['quest_type_id'])
        ->when($request->completed_at, function ($query, $completedAt) {
          $query->where('created_at', '<=', $completedAt);
        })
        ->orderByDesc('id')
        ->get()
        ->map(function ($item) {
          $item->my_count = QuestUserLog::where([
            ['user_id', $item->user_id],
            ['quest_type_id', $item->quest_type_id]
          ])->where('id', '<=', $item->id)->count();
          $item->user_id = $item->user->id;
          $item->user_created_at =  $item->created_at;
          $item->user_updated_at =  $item->updated_at;
          unset($item->user);
          unset($item->questType);
          return $item;
        })
        ->toArray();
      return ReturnData::setData(compact('list'))->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function refSeedUpdate(RefSeedReqeust $request)
  {
    $plateGradePriceTable = RefPlateGradePrice::get()->toArray();

    $conditionMap = [
      PlateCard::class =>  ['league_id', 'price_init_season_id', 'team_id', 'player_id'],
      RefPowerRankingQuantile::class => ['map_identification_id', 'league_id'],
      RefTeamTierBonus::class => ['rank', 'league_id', 'season_id', 'team_id'],
    ];

    $file = ($request->file('ref_seed'));
    // $excelDatas = Excel::toArray(new HImport1, $file);
    $excelDatas = Excel::toArray([], $file);
    $sheets = [
      PlateCard::class =>  $excelDatas[0],
      RefPowerRankingQuantile::class => $excelDatas[1] ?? null,
      RefTeamTierBonus::class => $excelDatas[2] ?? null
    ];

    try {
      logger('a');
      JobUpdateRefSeed::dispatch($plateGradePriceTable, $conditionMap, $sheets);
      logger('b');
      JobPlateCardChangeUpdate::dispatch();
      logger('c');
    } catch (\Exception $e) {
      logger($e);
      logger('카드 가격 초기화 실패');
    }
    return ReturnData::setData([])->send(Response::HTTP_OK);
  }
}
