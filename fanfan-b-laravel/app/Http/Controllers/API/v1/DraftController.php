<?php

namespace App\Http\Controllers\API\v1;

use App\Enums\AuctionBidStatus;
use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\FantasyCalculator\FantasyDraftCategoryType;
use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Card\DraftCardStatus;
use App\Enums\Opta\Card\PlateCardStatus;
use App\Enums\Opta\Player\PlayerSubPosition;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Player\PlayerSuspension;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Season\SeasonNameType;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\PlateCardActionType;
use App\Enums\PointRefType;
use App\Enums\PointType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\QuestCollectionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Draft\DraftRulesRequest;
use App\Http\Requests\Api\Draft\MatchStatDetailsRequest;
use App\Http\Requests\Api\Draft\StoreSelectionRequest;
use App\Http\Requests\Api\Draft\UserCardBurnRequest;
use App\Http\Requests\Api\Draft\UserCardSkillRequest;
use App\Http\Requests\Api\Draft\UserGradeCardDetailRequest;
use App\Http\Requests\Api\PlateCard\CartAddRequest;
use App\Http\Requests\Api\PlateCard\CartModifyRequest;
use App\Http\Requests\Api\PlateCard\PlateCardListRequest;
use App\Http\Requests\Api\PlateCard\PlateCardOrderRequest;
use App\Http\Requests\Api\PlateCard\PlayerInfoRequest;
use App\Http\Requests\Api\PlateCard\UserCardCountRequest;
use App\Http\Requests\Api\PlateCard\UserCardHistoryRequest;
use App\Http\Requests\Api\PlateCard\UserCardListRequest;
use App\Http\Requests\Api\PlateCard\MyCardOpenRequest;
use App\Http\Requests\Api\Stat\PlayerDetailRequest;
use App\Http\Requests\SeasonRequest;
use App\Libraries\Classes\FantasyCalculator;
use App\Libraries\Classes\QuestRecorder;
use App\Libraries\Traits\CommonTrait;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\data\Suspension;
use App\Models\game\AuctionBid;
use App\Models\game\DraftSelection;
use App\Models\game\PlateCard;
use App\Models\game\PlayerDailyStat;
use App\Models\log\DraftLog;
use App\Models\log\DraftSelectionLog;
use App\Models\meta\RefPlateCardRank;
use App\Models\meta\RefPlayerCurrentMeta;
use App\Models\meta\RefPlayerOverallHistory;
use App\Models\order\DraftOrder;
use App\Models\PlayerOverallAvgView;
use App\Models\simulation\SimulationOverall;
use App\Models\user\UserPlateCard;
use App\Services\Data\DataService;
use App\Services\Data\StatService;
use App\Services\Game\DraftService;
use App\Services\Market\MarketService;
use App\Services\Order\OrderService;
use App\Services\User\UserPointService;
use App\Services\User\UserService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DB;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use ReturnData;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Exception;
use Str;

class DraftController extends Controller
{
  use CommonTrait;

  protected DataService $dataService;
  protected DraftService $draftService;
  protected OrderService $orderService;
  protected UserPointService $userPointService;
  protected StatService $statService;
  protected UserService $userService;
  protected MarketService $marketService;

  protected int $limit = 35;

  public function __construct(
    DraftService $_draftService,
    DataService $_dataService,
    OrderService $_orderService,
    UserPointService $_userPointService,
    StatService $_statService,
    UserService $_userService,
    MarketService $_marketService,
  ) {
    $this->draftService = $_draftService;
    $this->dataService = $_dataService;
    $this->orderService = $_orderService;
    $this->userPointService = $_userPointService;
    $this->statService = $_statService;
    $this->userService = $_userService;
    $this->marketService = $_marketService;
  }

  /**
   * @OA\Parameter(
   *      parameter="card-platecards--league",
   *      in="query",
   *      name="league",
   *      required=true,
   *      description="league parameter",
   *      @OA\Schema(
   *          type="array",
   *          @OA\Items(type="string", default="EPL"),
   *      )
   * ),
   * @OA\Parameter(
   *      parameter="card-platecards--club",
   *      in="query",
   *      name="club",
   *      description="club(team_club_name) parameters(없으면 모든 클럽)",
   *      @OA\Schema(
   *          type="array",
   *          @OA\Items(type="string", default="ALL"),
   *      )
   * ),
   * @OA\Parameter(
   *      parameter="card-platecards--position",
   *      in="query",
   *      name="position",
   *      description="position paremeters(없으면 모든 포지션)",
   *      @OA\Schema(
   *          type="array",
   *          @OA\Items(type="string", enum={"ALL","FW","MF","DF","GK"}),
   *      )
   * ),
   * @OA\Parameter(
   *      parameter="card-platecards--grade",
   *      in="query",
   *      name="grade",
   *      description="grade parameters(없으면 모든 등급(현재 임시 등급이름))",
   *      @OA\Schema(
   *          type="array",
   *          @OA\Items(type="string", enum={"A","B","C","D","E"}),
   *      )
   * ),
   * @OA\Parameter(
   *      parameter="card-platecards--player_name",
   *      in="query",
   *      name="player_name",
   *      description="player 이름(검색) parameter(없으면 이름 검색 안함)",
   *      @OA\Schema(
   *          type="string",
   *      )
   * ),
   * @OA\Parameter(
   *      parameter="card-platecards--sort",
   *      in="query",
   *      name="sort",
   *      required=false,
   *      description="sorting 방법(없으면 기본 POPULAR 로 정렬)",
   *      @OA\Schema(
   *          type="string",
   *          enum={"POPULAR", "NEW", "HPRICE", "LPRICE"}
   *      )
   * ),
   * @OA\Get(
   *   tags={"card"},
   *   path="/api/v1/card/platecards",
   *   summary="플레이트 카드 리스트",
   *   description="플레이트 카드 리스트",
   *   operationId="getPlateCards",
   *   @OA\Parameter(ref="#/components/parameters/card-platecards--league"),
   *   @OA\Parameter(ref="#/components/parameters/card-platecards--club"),
   *   @OA\Parameter(ref="#/components/parameters/card-platecards--position"),
   *   @OA\Parameter(ref="#/components/parameters/card-platecards--grade"),
   *   @OA\Parameter(ref="#/components/parameters/card-platecards--player_name"),
   *   @OA\Parameter(ref="#/components/parameters/card-platecards--sort"),
   *   @OA\Response(
   *    response=200,
   *    description="successful operation",
   *   )
   *  )
   * )
   */
  public function plateCards(PlateCardListRequest $request)
  {
    $input = $request->only([
      'league',
      'club',
      'position',
      'player_name',
      'page',
      'per_page',
      'sort',
      'order'
    ]);

    try {
      $userCards = $this->dataService->getUserCardsCountByStatus();
      $input['userCards'] = $userCards;
      $result = $this->draftService->getPlateCardList($input);

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {

      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }
  /**
   * @OA\Get(
   *   tags={"card"},
   *   path="/api/v1/card/leagues",
   *   summary="리그 리스트",
   *   description="리그 리스트",
   *   operationId="getLeagues",
   *   @OA\Response(
   *    response=200,
   *    description="successful operation",
   *   )
   *  )
   * )
   */
  public function leagues(SeasonRequest $request)
  {
    $input = $request->only('season');

    try {
      $result = $this->dataService->getLeagueWithTeams($input);
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }


  /**
   * @OA\Get(
   *   tags={"card"},
   *   path="/api/v1/card/{league}/clubs",
   *   summary="(팀)클럽 리스트",
   *   description="(팀)클럽 리스트",
   *   operationId="getClubs",
   * @OA\Parameter(
   *      in="path",
   *      name="league",
   *      required=false,
   *      description="플레이트 카드 player 이름(검색)",
   *      @OA\Schema(
   *          type="string",
   *          default="EPL",
   *      )
   * ),
   *   @OA\Response(
   *    response=200,
   *    description="successful operation",
   *   )
   *  )
   * )
   */
  /*
  현재 사용되는 api 없음.
  */
  public function clubs()
  {
    $input = [
      'name' => null,
    ];
    $result = [];
    try {
      $this->dataService->getTeams(
        $input,
        ['season_id', 'team_id', 'short_name', 'name', 'code']
      )
        ->map(function ($item) use (&$result) {
          $team['id'] = $item->team_id;
          $team['short_name'] = $item->short_name;
          $team['name'] = $item->name;
          $team['code'] = $item->code;
          $result[$item->season->league->id][] = $team;
        });

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }
  public function plateCardInfos($_plateCardId)
  {
    try {
      $result = $this->dataService->getPlateCardInfo($_plateCardId);
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function fixtureSchedules($_teamId)
  {
    try {
      $result = $this->dataService->getFixtureSchedules($_teamId);
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function playerInfos(PlayerInfoRequest $request)
  {
    /**
     * @var FantasyCalculator $fpjCalculator
     */
    $fpjCalculator = app(FantasyCalculatorType::FANTASY_PROJECTION, [0]);

    $input = $request->only([
      'player',
      'season',
      'user_plate_card',
      'mode',
    ]);

    try {
      $plateCardInfo = PlateCard::with('refPlayerOverall')
        ->where('player_id', $input['player'])->withTrashed()->first();

      if ($input['user_plate_card'] && is_null($input['mode'])) {
        $userPlateCardPlayer = PlateCard::whereHas('userPlateCard', function ($query) use ($input) {
          $query->whereId($input['user_plate_card']);
        })->value('player_id');
        if ($input['player'] != $userPlateCardPlayer) {
          logger('바보 playerInfo >> player != userPlateCardId');
        }

        $projection = $fpjCalculator->calculate(['user_plate_card_id' => $input['user_plate_card'], 'plate_card_id' => null]);
      } else {
        $projection = $fpjCalculator->calculate(['user_plate_card_id' => null, 'plate_card_id' => $plateCardInfo['id']]);
      }

      $result = $this->draftService->getPlayerInfo([
        'player_id' => $input['player'],
        'season_id' => $input['season'] ?? null,
        'base_info' => $this->dataService->getPlayerBaseInfo($plateCardInfo),
        'user_plate_card_id' => $input['user_plate_card'],
        'mode' => $input['mode'],
        // 'season_stat' => $this->statService->getSeasonStatSummary($_playerId),
      ]);
      $result['projection'] = $projection;

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function playerDetail(PlayerDetailRequest $request)
  {
    $input = $request->only([
      'player'
    ]);

    $plateCardId = PlateCard::where('player_id', $input)
      ->value('id');

    try {
      $result = $this->draftService->getPlayerDetail($input);
      $result['like_info'] = [
        'total_count' => $this->statService->countPlateCardLikes($plateCardId),
        'is_like' => !is_null($this->statService->plateCardLikeMyLog($plateCardId)),
      ];

      DB::beginTransaction();
      $this->draftService->dailyActionUpdateOrCreate($result['id'], $result['season_id'], $result['position'], PlateCardActionType::STATS);
      DB::commit();

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      DB::rollBack();
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function plateCardUserLike(PlayerDetailRequest $request)
  {
    $input = $request->only('player');

    $plateCardId = PlateCard::where('player_id', $input)
      ->value('id');

    try {
      $result = $this->statService->upsertPlateCardUserLikeLog($plateCardId);
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function playerDetailStats(PlayerDetailRequest $request)
  {
    $input = $request->only([
      'season',
      'player'
    ]);

    try {
      $result = $this->draftService->playerDetailStats($input);
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  private function matchDetail($_playerId, $_scheduleId)
  {
    $teamId = PlateCard::where('player_id', $_playerId)->value('team_id');
    $list = [];
    Schedule::whereId($_scheduleId)
      ->with([
        'home:' . implode(',', config('commonFields.team')),
        'away:' . implode(',', config('commonFields.team')),
        'season'
      ])
      ->select('id', 'season_id', 'home_team_id', 'away_team_id', 'score_home', 'score_away', 'started_at', 'round')
      ->limit(1)
      ->get()
      ->map(function ($info) use (&$list, $teamId) {
        $list['season'] = $info->season;
        foreach (['home', 'away'] as $teamSide) {
          $list['schedule'][$teamSide] = $info->$teamSide;
          $list['schedule'][$teamSide]['is_player_team'] = $info->$teamSide->id === $teamId;
        }

        unset($info->season);
        $list['schedule'] = $info;

        return $list;
      });

    return ReturnData::setData(
      [
        'more' => false,
        'stats' => $list
      ]
    )->send(Response::HTTP_OK);
  }

  public function matchStatDetails($_playerId, MatchStatDetailsRequest $request)
  {
    /**
     * PLAYED된 경기만
     * 참여하지 않은 경기도 포함
     */
    $input = $request->only(
      ['player_id', 'season_id', 'limit', 'end_date', 'user_plate_card']
    );

    $input['season_id'] = PlayerDailyStat::getStatConditionalSeason($input['season_id'], $_playerId);

    try {
      $currentMeta = RefPlayerCurrentMeta::withWhereHas('plateCard', function ($query) {
        $query->withTrashed()->select('id', 'team_id');
      })->currentSeason()->where('player_id', $_playerId)->first();

      $leagueId = PlateCard::withTrashed()->where('player_id', $_playerId)->value('league_id');
      $currentSeason = Season::currentSeasons()->where('league_id', $leagueId)->first();
      $seasonStat['season_id'] = $currentMeta?->target_season_id ?? $currentSeason->id;
      $seasonStat['season_name'] = $currentMeta?->target_season_name ?? $currentSeason->name;
      $seasonStat['team_matches'] = Schedule::where('season_id', $seasonStat['season_id'])
        ->where(function ($query) use ($currentMeta) {
          $query->where('home_team_id', $currentMeta?->plateCard[0]->team_id)
            ->orWhere('away_team_id', $currentMeta?->plateCard[0]->team_id);
        })->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
        ->count();
      $seasonStat['matches'] = $currentMeta?->matches;
      $seasonStat['ratings'] = $currentMeta?->rating;
      $seasonStat['goals'] = $currentMeta?->goals;
      $seasonStat['assists'] = $currentMeta?->assists;

      /**
       * @var FantasyCalculator $draftExtraCalculator
       */
      $draftExtraCalculator = app(FantasyCalculatorType::FANTASY_DRAFT_EXTRA, [0]);

      $userScheduleId = null;
      $cardStatus = null;
      if (!is_null($input['user_plate_card'])) {
        // upgrading / complete 확인
        $cardStatus = UserPlateCard::whereId($input['user_plate_card'])->value('status');
        $userScheduleId = DraftSelection::where('user_plate_card_id', $input['user_plate_card'])->value('schedule_id');

        if ($cardStatus === DraftCardStatus::UPGRADING) {
          return $this->matchDetail($_playerId, $userScheduleId);
        }
      }

      $result = OptaPlayerDailyStat::select(array_merge(['schedule_id', 'rating', 'sub_position', 'team_id', 'season_id'], array_values($draftExtraCalculator->getCombRepresentationNames())))
        ->where([
          'player_id' => $_playerId,
          'status' => ScheduleStatus::PLAYED
        ])
        ->when($cardStatus === DraftCardStatus::COMPLETE, function ($userQuery) use ($userScheduleId) {
          $userQuery->where('schedule_id', $userScheduleId);
        }, function ($query) use ($input) {
          $query->where('season_id', $input['season_id']);
        })->gameParticipantPlayer()
        ->withWhereHas('schedule', function (Builder $query) use ($input) {
          $query
            ->select(['id', 'round', 'home_team_id', 'away_team_id', 'score_home', 'score_away', 'started_at'])
            ->where('started_at', '<', $input['end_date'])
            ->with([
              'home:' . implode(',', config('commonFields.team')),
              'away:' . implode(',', config('commonFields.team')),
            ])
            ->has('home')
            ->has('away');
        })
        ->with('season')
        ->get()
        ->map(function ($_info)  use ($draftExtraCalculator) {
          $info = $_info->toArray();
          foreach (['home', 'away'] as $teamSide) {
            $info['schedule'][$teamSide]['is_player_team'] = $info['schedule'][$teamSide]['id'] === $info['team_id'];
          }
          $info['position'] = $draftExtraCalculator->getPositionSummary($info);
          $temp = [];
          foreach ($draftExtraCalculator->getCombsWithCategoryTable() as $cate => $statNames) {
            if ($info['position'] === PlayerPosition::GOALKEEPER && $cate === FantasyDraftCategoryType::ATTACKING) {
              continue;
            } else if ($info['position'] !== PlayerPosition::GOALKEEPER && $cate === FantasyDraftCategoryType::GOALKEEPING) {
              continue;
            }
            foreach ($statNames as $stat) {
              $temp[$cate][$stat] = $info[$stat];
            }
          }
          $temp['schedule'] = $info['schedule'];

          $temp['season'] = $info['season'];
          return $temp;
        })->sortByDesc('schedule.started_at')->toarray();

      if (is_null($currentMeta)) {
        $currentMeta = RefPlayerCurrentMeta::withWhereHas('plateCard', function ($query) {
          $query->withTrashed()->select('id', 'team_id');
        })->where('player_id', $_playerId)
          ->when($input['season_id'], function ($query, $seasonId) {
            $query->where('target_season_id', $seasonId);
          })
          ->orderByDesc('season_start_date')
          ->first();

        $seasonStat['season_id'] = $currentMeta?->target_season_id;
        $seasonStat['season_name'] = $currentMeta?->target_season_name;
        $seasonStat['team_matches'] = Schedule::where('season_id', $seasonStat['season_id'])
          ->where(function ($query) use ($currentMeta) {
            $query->where('home_team_id', $currentMeta?->plateCard[0]->team_id)
              ->orWhere('away_team_id', $currentMeta?->plateCard[0]->team_id);
          })->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
          ->count();
        $seasonStat['matches'] = $currentMeta?->matches;
        $seasonStat['ratings'] = $currentMeta?->rating;
        $seasonStat['goals'] = $currentMeta?->goals;
        $seasonStat['assists'] = $currentMeta?->assists;
      }
      $last5Matches = $currentMeta?->last_5_matches;
      $pointAvgs = null;
      if (!is_null($last5Matches)) {
        $pointAvgs = $this->draftService->getPointAvgs(collect($last5Matches), $_playerId);
      }

      $stats = array_slice(array_values($result), 0, $input['limit']);
      if (count($result) === 1) {
        $stats = $stats[0];
      }

      return ReturnData::setData(
        [
          'point_avgs' => $pointAvgs,
          'last_5_schedules' => $last5Matches,
          'season_stat' => $seasonStat,
          'more' => (count($result) - $input['limit']) > 0,
          'stats' =>  $stats
        ]
      )->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function addCart(CartAddRequest $request)
  {
    $input = $request->only([
      'plate_card_id',
      'quantity',
    ]);

    try {
      $this->orderService->addCart($input);
      return ReturnData::send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function carts()
  {
    try {
      $result = $this->orderService->getCarts();
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function deleteCart($_cartId)
  {
    try {
      $this->orderService->deleteCart($_cartId);
      return ReturnData::send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function updateCart($_cartId, CartModifyRequest $request)
  {
    $input = $request->only([
      'value'
    ]);
    $input['cart_id'] = $_cartId;

    try {
      $this->orderService->updateCart($input);
      return ReturnData::send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function orderPlateCards(PlateCardOrderRequest $request)
  {
    $input = $request->only([
      'type',
      'cart',
      'plate_card_id',
      'quantity',
      'point_type',
      'total_price'
    ]);

    DB::beginTransaction();
    try {
      [$order, $userPlateCard] = $this->orderService->saveOrder($input);
      $totalPrice = $order->total_price;
      // point 차감
      $this->userPointService->minusUserPointWithLog(
        $totalPrice,
        $input['point_type'],
        PointRefType::ORDER
      );

      $plateCardInfo = PlateCard::find($input['plate_card_id']);
      $this->draftService->dailyActionUpdateOrCreate($plateCardInfo->player_id, $plateCardInfo->season_id, $plateCardInfo->position, PlateCardActionType::PLATE_ORDER);

      DB::commit();
      return ReturnData::setData(['result' => 'success', 'id' => $userPlateCard->id])->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      DB::rollBack();
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function userCardsByLeague(UserCardListRequest $request)
  {
    $input = $request->only([
      'league',
      'club',
      'position',
      'grade',
      'player_name',
      'type',
      'other',
      'page',
      'per_page',
      'sort',
      'order'
    ]);

    $this->limit = $input['per_page'];

    try {
      $allList = [];
      $etcList = [];
      $totalCount = 0;
      if ($input['other']) {
        $etcList = $this->draftService->getUserCardsByLeague($input);
        $totalCount = $etcList->count();
        if (is_null($input['club'])) {
          $allList = $etcList?->toArray();
        } else {
          $input['other'] = false;
        }
      }

      if (empty($allList)) {
        $cardList = $this->draftService->getUserCardsByLeague($input);
        $totalCount += $cardList->count();
        if (!empty($etcList)) {
          $allList = $cardList->merge($etcList);
          $allList = $allList->keyBy('id')->toArray();
          // sorting
          if ($input['type'] === 'grade') {
            $allList = __sortByKeys($allList, ['keys' => ['grade_order_no', 'draft_level', 'draft_completed_at', 'player_name'], 'hows' => ['asc', 'desc', 'desc', 'asc']]);
          }
        } else {
          $allList = $cardList->toArray();
        }
      }

      // pagination

      // $result = __setPaginateData($allList->paginate($this->limit, null, $input['page'])->toArray(), []);
      $startOffset = ($input['page'] - 1) * $this->limit;
      $allList = array_slice($allList, $startOffset, $this->limit);

      $result = ['total_count' => $totalCount, 'list' => $allList];
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
    return ReturnData::setData($result)->send(Response::HTTP_OK);
  }

  public function userCardsCount(UserCardCountRequest $request)
  {
    $input = $request->only([
      'league'
    ]);

    $input = [
      ...$input,
      'club' => null,
      'position' => null,
      'grade' => null,
      'player_name' => null,
      'type' => null,
      'other' => null
    ];

    try {
      $list = $this->draftService->getUserCardsCountByGrade($input);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }

    return ReturnData::setData($list)->send(Response::HTTP_OK);
  }

  public function userCardHistoryLeagues()
  {
    try {
      $leagueQuery = $this->dataService->leaguesQuery();
      $result = $this->draftService->getRoundsBySeason($leagueQuery);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }

    return ReturnData::setData($result)->send(Response::HTTP_OK);
  }

  public function userCardsHistory(UserCardHistoryRequest $request)
  {
    $input = $request->only([
      'season',
      'round',
      'schedule',
      'limit',
      'end_id',
      'index',
      'status'
    ]);

    try {
      $result = $this->draftService->getUserCardsHistory($input);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }

    return ReturnData::setData($result)->send(Response::HTTP_OK);
  }

  // 마이페이지
  public function userGradeCardDetail(UserGradeCardDetailRequest $request)
  {
    /**
     * @var FantasyCalculator $fpjCalculator
     */
    $fpjCalculator = app(FantasyCalculatorType::FANTASY_PROJECTION, [0]);

    $filter = $request->only([
      'user_plate_card',
      'mode',
    ]);

    $projection = $fpjCalculator->calculate(['user_plate_card_id' => $filter['user_plate_card'], 'plate_card_id' => null]);

    try {
      // 선수 기본정보
      $userPlateCard = UserPlateCard::with([
        'plateCard',
        'draftTeam:' . implode(',', config('commonFields.team')),
        'draftSeason:id,name,league_id',
        'draftSeason.league' => function ($query) {
          $query->withoutGlobalScope('serviceLeague')
            ->select('id', 'league_code');
        },
        'simulationOverall'
      ])->find($filter['user_plate_card']);
      [, $playerBaseInfo] = $this->dataService->getPlayerBaseInfo($userPlateCard);

      // 접근제한 풀음
      // // 마켓 접근일 때
      // if ($filter['mode'] === 'market') {
      //   // 내가 소유한 적이 있거나, 판매중이거나
      //   $owned = UserPlateCardLog::where([
      //     ['user_id', $this->userService->getUser()->id],
      //     ['user_plate_card_id', $filter['user_plate_card']]
      //   ])
      //     ->exists();
      //   $selling = Auction::where('user_plate_card_id', $filter['user_plate_card'])
      //     ->isSelling()
      //     ->exists();
      //   if (!$selling && !$owned) {
      //     throw new Exception('not owned or selling card');
      //   }
      // } else {
      //   // 일반 접근
      //   if ($playerBaseInfo['user_id'] !== $this->userService->getUser()->id) {
      //     throw new Exception('not my card');
      //   }
      // }

      $result = $playerBaseInfo;
      $result['projection'] = $projection;

      // 1열
      if (!$playerBaseInfo['is_free']) {
        if (!isset($playerBaseInfo['draft_season']['id'])) {
          throw new Exception('wrong card');
        }

        $selectionData = DraftSelection::where('user_plate_card_id', $filter['user_plate_card'])
          ->with([
            'schedule:id,home_team_id,away_team_id,score_home,score_away',
            'schedule.home:' . implode(',', config('commonFields.team')),
            'schedule.away:' . implode(',', config('commonFields.team')),
            'schedule.onePlayerDailyStat' => function ($query) use ($playerBaseInfo) {
              $query->where('player_id', $playerBaseInfo['player_id'])
                ->select(['schedule_id', 'player_id', 'fantasy_point']);
            }
          ])
          ->has('schedule')
          ->first();

        $draftSchedule = $selectionData?->schedule;

        foreach (['home', 'away'] as $teamSide) {
          $draftSchedule[$teamSide]['is_player_team'] = $draftSchedule[$teamSide]['id'] === $playerBaseInfo['draft_team']['id'];
        }

        $result['draft_schedule'] = $draftSchedule?->toArray();
      }

      if ($filter['mode'] === 'ingame') {
        $result['level_weight'] = $playerBaseInfo['level_weight'];
        $result['grade_weight'] = config('fantasyingamepoint')['FANTASYINGAMEPOINT_REFERENCE_TABLE_V0']['GradeWeightRate'][$playerBaseInfo['card_grade']];
        $stats = [];
        // 1. current_round
        // Fixture가 아닌 MaxRound 부터 5경기
        $currentRound = Schedule::whereHas('season', function ($query) {
          $query->currentSeasons();
        })
          ->where('league_id', $playerBaseInfo['draft_season']['league']['id'])
          ->whereNotIn('status', [ScheduleStatus::FIXTURE, ScheduleStatus::POSTPONED])
          ->where('started_at', '<', Carbon::now())
          ->selectRaw('MAX(round) as currentRound')
          ->get()
          ->value('currentRound');

        // dd($currentRound);
        // 현재시즌
        Schedule::whereHas('season', function ($query) {
          $query->currentSeasons();
        })
          ->where([
            ['league_id', $playerBaseInfo['draft_season']['league']['id']],
            ['round', '<=', $currentRound]
          ])
          ->where(function ($query) use ($playerBaseInfo) {
            $query->where('home_team_id', $playerBaseInfo['team']['id'])
              ->orWhere('away_team_id', $playerBaseInfo['team']['id']);
          })
          ->orderByDesc('round')
          ->limit(5)
          ->get()
          ->map(function ($info) use (&$stats) {
            $stats[$info->id] = [
              'season' => $info->season->name,
              'round' => $info->round,
              'schedule_status' => $info->status,
              'fantasy_point' => null,
              'suspension' => null
            ];
          });

        $remainCnt = 5 - count($stats);
        if ($remainCnt > 0) { // 이전시즌도 봐야함
          $beforeSeason = Season::getBeforeFuture([SeasonWhenType::BEFORE], $playerBaseInfo['draft_season']['league']['id'])[$playerBaseInfo['draft_season']['league']['id']]['before'][0];
          Schedule::where([
            ['season_id', $beforeSeason->id],
            ['stage_name', 'Regular Season']
          ])
            ->where(function ($query) use ($playerBaseInfo) {
              $query->where('home_team_id', $playerBaseInfo['team']['id'])
                ->orWhere('away_team_id', $playerBaseInfo['team']['id']);
            })
            ->orderByDesc('round')
            ->latest('started_at')
            ->limit($remainCnt)
            ->get()
            ->map(function ($info) use (&$stats) {
              $stats[$info->id] = [
                'season' => $info->season->name,
                'round' => $info->round,
                'schedule_status' => $info->status,
                'fantasy_point' => null,
                'suspension' => null
              ];
            });
        }

        $remainCnt = 5 - count($stats);
        if ($remainCnt > 0) {
          // 해당 리그의 이전시즌의 maxRound
          $maxRound = Schedule::where([
            ['season_id', $beforeSeason->id],
            ['stage_name', 'Regular Season']
          ])
            ->selectRaw('MAX(round) AS maxRound')->value('maxRound');

          for ($i = 0; $i < $remainCnt; $i++) {
            $nullArr['season'] = $beforeSeason->name;
            $nullArr['round'] = $maxRound;
            $nullArr['schedule_status'] = ScheduleStatus::PLAYED;
            $nullArr['fantasy_point'] = null;
            $nullArr['suspension'] = PlayerSuspension::ETC;
            $maxRound--;
            array_push($stats, $nullArr);
          }
        }


        $susPattern = sprintf('/.*(%s|%s|%s).*/', ...array_diff(PlayerSuspension::getValues(), [PlayerSuspension::ETC]));

        // 2. player_daily_stat-schedule where round, team


        foreach (array_keys($stats) as $scheduleId) {
          $scheduleStatredAt = Schedule::whereId($scheduleId)->get()->value('started_at');
          $suspension = Suspension::where([
            ['player_id', $playerBaseInfo['player_id']],
          ])->where([
            ['suspension_start_date', '<=', Carbon::parse($scheduleStatredAt)],
            ['suspension_end_date', '>=', Carbon::parse($scheduleStatredAt)->subDay()],
          ])->orWhere([
            ['suspension_start_date', '<=', Carbon::parse($scheduleStatredAt)],
            ['suspension_end_date', null],
          ])
            ->get()->value('description');

          if ($suspension !== null) {
            if (preg_match($susPattern, $suspension, $matches)) {
              $suspension = $matches[1];
            } else {
              $suspension = PlayerSuspension::ETC;
            }
          }

          $fpStats = PlayerDailyStat::where([
            ['schedule_id', $scheduleId],
            ['player_id', $playerBaseInfo['player_id']],
          ])->first()->toArray();

          $originStats = OptaPlayerDailyStat::where([
            ['schedule_id', $scheduleId],
            ['player_id', $playerBaseInfo['player_id']],
          ])->first()->toArray();


          // $stats[$info->schedule_id]['fantasy_point'] = $info->fantasy_point;
          // 등급포인트 계산
          /**
           * @var FantasyCalculator $fipCalculator
           */
          $fipCalculator = app(FantasyCalculatorType::FANTASY_INGAME_POINT, [0]);
          $userPlateCard = UserPlateCard::where([['id', $playerBaseInfo['id']], ['status', DraftCardStatus::COMPLETE]])->first()->toArray();
          if ($fpStats) {
            $stats[$scheduleId]['fantasy_point'] = $fipCalculator->calculate([
              'user_card_attrs' => $userPlateCard,
              'fantasy_point' => $fpStats['fantasy_point'],
              'is_mom' => $originStats['is_mom'],
              'schedule_id' => $scheduleId,
              'origin_stats' => $originStats,
              'fp_stats' => $fpStats,
            ]);
          } else {
            $stats[$scheduleId]['fantasy_point'] = null;
          }

          $stats[$scheduleId]['suspension'] = $suspension;
        }

        $result['last_5_points'] = array_reverse(array_values($stats));

        // 현재 시즌 진행중 여부로 avg 계산
        $seasonAvg = RefPlayerCurrentMeta::whereHas('lastSeason', function ($query) {
          $query->currentSeasons();
        })->limit(1)->get()->value('player_fantasy_point_avg');
        $seasonAvg = null;

        if (is_null($seasonAvg)) {
          $cardInfo = PlateCard::where('player_id', $playerBaseInfo['player_id'])->first()->toArray();
          $cardBeforeSeasonId = Season::whereHas('league', function ($query) use ($cardInfo) {
            $query->where('league_id', $cardInfo['league_id']);
          })->idsOf([SeasonWhenType::BEFORE], SeasonNameType::ALL, $_baseOffsetYear = 1);

          $redisKeyName = $cardBeforeSeasonId[0] . '_avg';

          if (Redis::exists($redisKeyName)) {
            $seasonAvg = json_decode(Redis::get($redisKeyName), true)['avg'];
          } else {
            $seasonAvg = OptaPlayerDailyStat::gameParticipantPlayer()
              ->where('season_id', $cardBeforeSeasonId[0])
              ->selectRaw('season_id, AVG(fantasy_point) as player_fantasy_point_avg')
              ->groupBy('season_id')
              ->get()
              ->value('player_fantasy_point_avg');
            Redis::set($redisKeyName, json_encode(['avg' => $seasonAvg]), 'EX', 86400);
          }
        }
        $result['player_fantasy_point_avg'] = $seasonAvg;
      }
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
    return ReturnData::setData($result)->send(Response::HTTP_OK);
  }

  public function userCardSkill($_userPlateCardId, UserCardSkillRequest $request)
  {
    try {
      $userDrafts = UserPlateCard::where([
        'id' => $_userPlateCardId,
      ])
        ->has('draftSelection')
        ->with('draftComplete')
        ->first();
      $draftMetaTable = [];
      if (!is_null($userDrafts)) {
        $draftMetaTable = $this->draftService->getDraftSelections($userDrafts->draftSelection);
      }
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }

    return ReturnData::setData($draftMetaTable, $request)->send(200);
  }

  public function myCardOpen(MyCardOpenRequest $request)
  {
    $input = $request->only([
      'id',
      'schedule_id'
    ]);

    try {

      $userPlateCardBase = UserPlateCard::where([
        ['status', DraftCardStatus::COMPLETE],
        ['is_open', false]
      ]);

      if (is_null($input['schedule_id'])) {
        $userPlateCard = $userPlateCardBase->whereId($input['id']);
      } else {
        $userPlateCard = $userPlateCardBase->where('user_id', $request->user()->id)
          ->whereHas('draftSelection', function ($query) use ($input) {
            $query->where('schedule_id', $input['schedule_id']);
          });
      }

      if (!$userPlateCard->exists()) {
        throw new Exception('오픈할 수 없는 카드');
      }

      $userPlateCard->update(['is_open' => true]);
      return ReturnData::setData(['success' => true])->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  private function checkDraftCountLimitRule(string $_schedule_id, string $_player_id, array $_user)
  {
    /**
     * 드래프트 개수 제한 규칙
     * 한 사용자 기준
     * 1. 선수당 3개 초과 금지,
     * 2. 한 스케쥴에서는 같은 선수의 드래프트를 중복 금지
     */
    $count1 = UserPlateCard::where(
      [
        ['user_id', $_user['id']],
        ['status', DraftCardStatus::UPGRADING],
      ]
    )->whereHas('plateCard', function (Builder $query) use ($_player_id) {
      $query->where('player_id', $_player_id);
    })->count();

    $count2 = DraftSelection::where('schedule_id', $_schedule_id)
      ->whereHas('userPlateCard', function (Builder $query) use ($_user, $_player_id) {
        $query->where([
          ['status', DraftCardStatus::UPGRADING],
          ['user_id', $_user['id']],
        ])->whereHas('plateCard', function (Builder $query) use ($_player_id) {
          $query->where('player_id', $_player_id);
        });
      })->count();

    $result = [
      'isOk' => true,
      'message' => '',
    ];

    if ($count1 >= 3) {
      $result['isOk'] = false;
      $result['message'] = '한 선수의 강화는 동시에 3개를 초과할 수 없음';
    } else if ($count2 >= 1) {
      $result['isOk'] = false;
      $result['message'] = '경기 스케쥴당 한 선수의 강화는 동시에 1개를 초과할 수 없음';
    }
    return $result;
  }

  public function draftRules(DraftRulesRequest $_request)
  {
    $input = $_request->all();
    $card = PlateCard::isOnSale()->where(
      [
        ['player_id', $input['player_id'],],
      ]
    )->first();
    if (is_null($card)) {
      return ReturnData::setError('player_id 또는 position이 유효하지 않음')->send(Response::HTTP_BAD_REQUEST);
    }
    /**
     * @var FantasyCalculator $draftCalculator
     */
    $draftCalculator = app(FantasyCalculatorType::FANTASY_DRAFT, [0]);

    $draftSelectionTable = $draftCalculator->getDraftCategoryMetaTable($card['position']);
    $draftPolicy = $draftCalculator->getDraftPolicy($card['grade']);

    return ReturnData::setData(['selection' => $draftSelectionTable, 'policy' => $draftPolicy])->send(Response::HTTP_OK);
  }

  public function storeSelections(StoreSelectionRequest $_request)
  {
    /**
     * draftRuleService 만들어 정책(rule) 관련 메소드를 따로따로 분리할 것.
     * 드래프트 관련 정책(rule) 정리할 것.
     */
    /**
     * rules
     * 1. status가 Fixture인 shedule_id가 game_schedules에 존재하는지.(o)
     * 2. player_id가 plate_cards에 존재하는지.(o)
     * 추가 검사
     * a. player_id의 카드를 요청 사용자가 가지고 있는지.
     * b. player_id가 속한 팀이 shedule_id의 home 또는 away에 존재하는지.
     * c. position이 plate_cards의 position과 같은지.
     * d. 경기 시작 시작 30분 전까지만 드래프트 가능.
     * e. selections 검증
     */

    $input = $_request->all();
    $_user = $_request->user()->toArray();

    // 드래프트 rule 검사
    // dd(!($result = $this->checkDraftRule($input, $_user)['isOk']));

    if (Str::startsWith($input['schedule_id'], config('constant.UNREAL_SCHEDULE_PREFIX'))) {
      return ReturnData::setError('테스트 경기는 드래프트 불가!')->send(Response::HTTP_BAD_REQUEST);
    }

    $draftAvailableCard = UserPlateCard::with('plateCard')
      ->where([
        ['id', $input['user_plate_card_id']],
        ['status', PlateCardStatus::PLATE]
      ])->first();

    if (is_null($draftAvailableCard)) {
      return ReturnData::setError('선수의 플레이트 카드 구매 필요')->send(Response::HTTP_BAD_REQUEST);
    }

    $input['player_id'] = $draftAvailableCard->plateCard->player_id;
    if (!(($result = $this->checkDraftCountLimitRule($input['schedule_id'], $input['player_id'], $_user))['isOk'])) {
      return ReturnData::setError($result['message'])->send(Response::HTTP_BAD_REQUEST);
    }

    $plateCard = PlateCard::where('player_id', $input['player_id'])->first('team_id')->toArray();
    $teamId = $plateCard['team_id'];
    $priceGrade = $plateCard['grade'];
    $targetSchedule = Schedule::with(['home', 'away'])
      ->where('id', $input['schedule_id'])
      ->first()
      ->toArray();
    $seasonId = $targetSchedule['season_id'];

    /**
     * @var FantasyCalculator $draftCalculator
     */
    $draftCalculator = app(FantasyCalculatorType::FANTASY_DRAFT, [0]);

    $plateCardPosition = $draftAvailableCard->toArray()['plate_card']['position'];


    if (!in_array($teamId, [$targetSchedule['home']['id'], $targetSchedule['away']['id']])) {
      return ReturnData::setError('플레이어가 소속한 팀의 경기가 아님')->send(Response::HTTP_BAD_REQUEST);
    } else if ($plateCardPosition !== $input['position']) {
      return ReturnData::setError('드래프트 포지션이 일치하지 않습니다.')->send(Response::HTTP_BAD_REQUEST);
    } else if (
      $targetSchedule['status'] !== ScheduleStatus::FIXTURE ||
      Carbon::parse(now())->floatDiffInMinutes($targetSchedule['started_at'], false) < 30
    ) {
      return ReturnData::setError('강화 시도 가능한 경기 상태가 아니거나 강화 가능 시간(경기 시작 시간 30분 이전)이 지났습니다.')->send(Response::HTTP_BAD_REQUEST);
    }

    // -->> 임시처리
    // $validationResult = $draftCalculator->temporaryValidateDraftSelection($input);
    $validationResult = $draftCalculator->validateDraftSelection($input);
    // <<-- 임시처리

    if (!$validationResult['is_valid']) {
      return ReturnData::setError($validationResult['message'])->send(Response::HTTP_BAD_REQUEST);
    }

    DB::beginTransaction();
    try {
      // 1. 결제 user point 차감 draft

      $this->userPointService->minusUserPointWithLog(
        $validationResult['total_price'],
        $draftCalculator->getDraftPolicy($priceGrade)['price']['type'],
        PointRefType::UPGRADE,
        'draft',
      );

      // 2. draft_orders 기록
      DraftOrder::updateOrCreateEx(
        [
          'user_plate_card_id' => $draftAvailableCard['id'],
        ],
        [
          'user_id' => $_user['id'],
          'user_plate_card_id' => $draftAvailableCard['id'],
          'upgrade_point' => $validationResult['total_price'],
          'upgrade_point_type' => $validationResult['price_type'],
          'order_status' => PurchaseOrderStatus::COMPLETE,
        ]
      );

      // 3. draft_selections 저장
      $selections = [];
      foreach ($input['selections'] as $name => $level) {
        $selections[$name] = $level;
      }

      DraftSelection::create(
        array_merge([
          'user_id' => $_user['id'],
          'user_plate_card_id' => $draftAvailableCard['id'],
          'schedule_id' => $input['schedule_id'],
          'schedule_started_at' => $targetSchedule['started_at'],
          'player_id' => $input['player_id'],
          'summary_position' => $input['position'],
        ], $selections)
      );

      // +) draft_selection_logs 기록
      DraftSelectionLog::create(
        array_merge([
          'user_id' => $_user['id'],
          'user_plate_card_id' => $draftAvailableCard['id'],
          'league_id' => $plateCard['league_id'],
          'team_id' => $teamId,
          'schedule_id' => $input['schedule_id'],
          'schedule_started_at' => $targetSchedule['started_at'],
          'player_id' => $input['player_id'],
          'summary_position' => $input['position'],
          'user_name' => $_user['name'],
          'selection_level' => $input['level'],
          'selection_cost' => $input['cost'],
          'selection_point' => $input['totalPrice']
        ], $selections)
      );

      // 4. draft_logs 기록
      $this->draftService->recordLog(DraftLog::class, [
        'user_id' => $_user['id'],
        'user_plate_card_id' => $draftAvailableCard['id'],
        'draft_season_id' => $seasonId,
        'draft_team_id' => $teamId,
        'schedule_id' => $input['schedule_id'],
        // 'player_name' => $draftAvailableCards['player_name'],
        'origin_started_at' => $targetSchedule['started_at'],
        'schedule_status' => $targetSchedule['status'],
        'card_grade' => CardGrade::NONE,
        'status' => DraftCardStatus::UPGRADING,
        // 'draft_level' => null,
      ]);

      // 5. user plate card 상태 upgrading로 변경
      $draftAvailableCard->status = DraftCardStatus::UPGRADING;
      $draftAvailableCard->save();

      $this->draftService->dailyActionUpdateOrCreate($input['player_id'], $seasonId, $input['position'], PlateCardActionType::UPGRADE);

      // quest
      (new QuestRecorder())->act(QuestCollectionType::UPGRADE, $_user['id']);


      // 연속강화 및 count 갱신 위한 data
      $refOverallId = $draftAvailableCard->ref_player_overall_history_id;
      $remainUserCards = UserPlateCard::where([
        ['user_id', $_request->user()->id],
        ['ref_player_overall_history_id', $refOverallId],
      ]);
      $validationResult['count'] = $remainUserCards->clone()->selectRaw('status,COUNT(status) as cnt')->groupBy('status')->pluck('cnt', 'status')->toArray();
      $validationResult['next_user_card_id'] =  $remainUserCards->where('status', PlateCardStatus::PLATE)->orderBy('id')->value('id');

      DB::commit();
    } catch (\Exception $e) {
      DB::rollBack();
      return ReturnData::setError($e->getMessage())->send(Response::HTTP_BAD_REQUEST);
    }

    return ReturnData::setData($validationResult)->send(Response::HTTP_OK);
    //store draft selection
  }

  public function burnInfo(UserCardBurnRequest $request)
  {
    /**
     * @var FantasyCalculator $fbCalculator
     */
    $fbCalculator = app(FantasyCalculatorType::FANTASY_BURN, [0]);

    $filter = $request->only([
      'user_plate_card_id',
    ]);

    try {
      $userPlateCard = UserPlateCard::find($filter['user_plate_card_id']);

      if ($userPlateCard->card_grade === CardGrade::NONE) {
        throw new Exception('Not Grade Card');
      }

      $minMax = $fbCalculator->calculate($userPlateCard->toArray());

      // $randomPoint = BigDecimal::of(rand($minMax['min'],
      //   $minMax['max']))->multipliedBy(($userPlateCard->is_mom ? 1.2 : 1));
      // $randomPoint = (int) round($randomPoint->toFloat());
    } catch (Throwable $e) {
      return ReturnData::setError($e->getMessage())->send(Response::HTTP_BAD_REQUEST);
    }
    return ReturnData::setData($minMax)->send(Response::HTTP_OK);
  }


  public function burnExec(UserCardBurnRequest $request)
  {
    /**
     * @var FantasyCalculator $fbCalculator
     */
    $fbCalculator = app(FantasyCalculatorType::FANTASY_BURN, [0]);
    $filter = $request->only([
      'user_plate_card_id',
    ]);

    try {
      $userPlateCard = UserPlateCard::find($filter['user_plate_card_id']);

      if ($userPlateCard->card_grade === CardGrade::NONE) {
        throw new Exception('Not Grade Card');
      }

      if (!is_null($userPlateCard->lock_status)) {
        // 다른 곳에서 사용중인 카드
        throw new Exception('this card is locked.');
      }

      $minMax = $fbCalculator->calculate($userPlateCard->toArray());

      $randomPoint = BigDecimal::of(rand(
        $minMax['min'],
        $minMax['max']
      ))->multipliedBy(($userPlateCard->is_mom ? 1.2 : 1));
      $getPoint = (int) round($randomPoint->toFloat());
      $ownedFanPoint = $request->user()->userMeta->fan_point;
      DB::beginTransaction();
      $description = sprintf('User Plate Card Id : %d, Point : %d', $userPlateCard->id, $getPoint);
      $userPlateCard->burned_at = now();
      $userPlateCard->save();
      $this->userPointService->plusUserPointWithLog(
        $getPoint,
        PointType::FAN_POINT,
        PointRefType::BURN,
        $description
      );

      DB::commit();

      $result = [
        'owned' => $ownedFanPoint,
        'get' => $getPoint,
        'total' => $ownedFanPoint + $getPoint,
      ];
    } catch (Throwable $e) {
      DB::rollback();
      return ReturnData::setError($e->getMessage())->send(Response::HTTP_BAD_REQUEST);
    }
    return ReturnData::setData($result)->send(Response::HTTP_OK);
  }

  public function getPlayerSeasonStats(PlayerInfoRequest $request)
  {
    $playerId = $request->only(['player'])['player'];

    try {
      $result = [];
      // player Current Meta
      $currentMeta = RefPlayerCurrentMeta::withWhereHas('plateCard', function ($query) {
        $query->withTrashed()->select('id', 'team_id');
      })->currentSeason()->where('player_id', $playerId)->first();

      // $result['season_id'] = $currentMeta?->target_season_id;
      $result['season_id'] = $currentMeta?->currentSeason->id;
      $result['season_name'] = $currentMeta?->currentSeason->name;
      $result['team_matches'] = Schedule::where('season_id', $result['season_id'])
        ->where(function ($query) use ($currentMeta) {
          $query->where('home_team_id', $currentMeta?->plateCard[0]->team_id)
            ->orWhere('away_team_id', $currentMeta?->plateCard[0]->team_id);
        })->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
        ->count();
      $result['matches'] = $currentMeta?->matches;
      $result['ratings'] = $currentMeta?->rating;
      $result['goals'] = $currentMeta?->goals;
      $result['assists'] = $currentMeta?->assists;

      $last5Matches = $currentMeta?->last_5_matches;
      if (!is_null($last5Matches)) {
        $pointAvgs = $this->draftService->getPointAvgs(collect($last5Matches), $playerId);
        $result['point_avgs']['rating'] = $pointAvgs['rating_avg'];
        $result['point_avgs']['fantasy_point'] = $pointAvgs['fantasy_point_avg'];
        foreach ($last5Matches as $schedule) {
          $match['id'] = $schedule['id'];
          $match['home'] = $schedule['home'];
          $match['away'] = $schedule['away'];
          $match['rating'] = $schedule['rating'];
          $match['started_at'] = $schedule['started_at'];
          $result['last_5_ratings'][] = $match;
        }
      }
    } catch (Throwable $e) {
      return ReturnData::setError($e->getMessage())->send(Response::HTTP_BAD_REQUEST);
    }
    return ReturnData::setData($result)->send(Response::HTTP_OK);
  }

  public function getPlayerOverall(PlayerInfoRequest $request)
  {
    $input = $request->only([
      'player',
      'user_plate_card',
    ]);

    try {
      $result = [];
      $userCardInfo = null;
      if (!is_null($input['user_plate_card'])) {
        $userCardInfo = UserPlateCard::with('refPlayerOverall')->where('id', $input['user_plate_card'])->first();
        if ($userCardInfo->status === PlateCardStatus::COMPLETE) {
          // overall
          $overalls = SimulationOverall::where('user_plate_card_id', $input['user_plate_card'])->first();

          // 세부포지션(max 3)
          $playerPositions = [
            'sub_position' => $overalls->sub_position,
            'second_position' => $overalls->second_position,
            'third_position' => $overalls->third_position
          ];

          $columns = config('fantasyoverall.column');
          $categoryCntArr = array_count_values($columns);

          foreach ($playerPositions as $index => $position) {
            $minus = config('fantasyoverall.sub_position')[$index];
            if (!is_null($position)) {
              $total[$position]['final_overall'] = (int) $overalls->final_overall[$position];
              foreach ($columns as $column => $category) {
                if ($position === $overalls->sub_position) {
                  $mark = null;
                  if ($overalls->{$column} > 0) $mark = '+';
                  $total[$position][$category][$column] = ['overall' => $overalls->{$column}['overall'] + $minus, 'add' => $mark . $overalls->{$column}['add']];
                }
                $total[$position][$category]['avg'] = $overalls->{$category . '_overall'} + $minus;
              }
            }
          }
          $result['overall'] = $total;
        }
      }
      if (empty($result)) {
        if (!is_null($userCardInfo)) {
          $overalls = $userCardInfo->refPlayerOverall;
        } else {
          $overalls = RefPlayerOverallHistory::whereHas('season', function ($query) {
            $query->currentSeasons();
          })
            ->where('player_id', $input['player'])
            ->where('is_current', true)
            ->first();
        }
        $columns = config('fantasyoverall.column');
        $categoryCntArr = array_count_values($columns);
        $cloneArr = $categoryCntArr;

        $selectRaw = [];
        foreach ($columns as $column => $category) {
          if (!isset($selectRaw[$category])) $selectRaw[$category] = '';
          if ($cloneArr[$category] > 0) {
            $cloneArr[$category]--;
            if ($cloneArr[$category] !== 0) {
              $selectRaw[$category] .= $column . '+';
            } else {
              $selectRaw[$category] .= $column . ')/' . $categoryCntArr[$category] . '),1) AS Float) AS ' . $category . '_avg';
            }
          }
          if (!isset($total[$category]['total'])) $total[$category]['total'] = 0;
          $total[$category][$column] = ['overall' => $overalls->{$column}];
          $total[$category]['total'] += $overalls->{$column};
          $total[$category]['avg'] = BigDecimal::of($total[$category]['total'])->dividedBy(BigDecimal::of($categoryCntArr[$category]), 0, RoundingMode::HALF_UP)->toInt();
        }
        $total['final_overall'] = $overalls->final_overall;
        $result['overall']['my'] = $total;

        $positionAvg = RefPlayerOverallHistory::where('position', $overalls->position)
          ->selectRaw(
            'position,
            CAST(ROUND(AVG(final_overall),1) AS Float) AS overall_avg,
            CAST(ROUND(AVG((' . $selectRaw['attacking'] . ',' .
              'CAST(ROUND(AVG((' . $selectRaw['passing'] . ',' .
              'CAST(ROUND(AVG((' . $selectRaw['defensive'] . ',' .
              'CAST(ROUND(AVG((' . $selectRaw['duel'] . ',' .
              'CAST(ROUND(AVG((' . $selectRaw['goalkeeping'] . ',' .
              'CAST(ROUND(AVG((' . $selectRaw['physical']
          )->groupBy('position')->first();

        $avg['final_overall']  = $positionAvg->overall_avg;
        foreach ([...FantasyDraftCategoryType::getValues(), 'physical'] as $category) {
          if ($category !== 'summary') {
            $avg[$category]['avg'] = $positionAvg->{$category . '_avg'};
          }
        }

        $result['overall'][$overalls->position] = $avg;
      }
    } catch (Throwable $e) {
      return ReturnData::setError($e->getMessage())->send(Response::HTTP_BAD_REQUEST);
    }
    return ReturnData::setData($result)->send(Response::HTTP_OK);
  }

  /*  사용 안하는데 혹시나 싶어서 일단 남겨둠ㅎ */
  public function getPlayerOverallAvg(PlayerInfoRequest $request)
  {
    $input = $request->only([
      'player',
      'user_plate_card',
    ]);

    try {
      $result = [];

      // player Current Meta
      $currentMeta = RefPlayerCurrentMeta::whereHas('plateCard', function ($query) {
        $query->isOnSale()->currentSeason();
      })->currentSeason()->where('player_id', $input['player'])->first();

      // player 포지션 빈도
      if (!is_null($currentMeta)) {
        $majorFormation = $currentMeta->formation_aggr;
      } else {
        foreach (PlayerSubPosition::getValues() as $position) {
          $majorFormation[$position] = 0;
        }
        if ($input['user_plate_card']) {
          $majorFormation[SimulationOverall::where('user_plate_card_id', $input['user_plate_card'])->value('sub_position')] = 1;
        } else {
          $majorFormation[RefPlayerOverallHistory::where([['player_id', $input['player']], ['is_current', true]])->value('sub_position')] = 1;
        }
      }

      $columns = config('fantasyoverall.column');
      $categoryCntArr = array_count_values($columns);

      // overall
      $overalls = SimulationOverall::with('refPlayerOverall')
        ->when($input['user_plate_card'], function ($query, $userPlateCardId) {
          $query->where('user_plate_card_id', $userPlateCardId);
        }, function ($query) use ($input) {
          $query->where('player_id', $input['player']);
        })->first();

      // 세부 포지션 별 선수의 avg overall 
      $playerOverallAvg = PlayerOverallAvgView::withTrashed()
        ->whereHas('season', function ($query) {
          $query->currentSeasons();
        })->where('player_id', $input['player'])
        ->get()
        ->keyBy('sub_position')
        ->toArray();

      // 해당 position 으로 만들어진 카드가 없는 경우는 plateCard 값
      $playerOverall = RefPlayerOverallHistory::where([
        ['player_id', $input['player']],
        ['is_current', true]
      ])->first();

      foreach ($overalls->final_overall as $position => $overall) {
        if ($majorFormation[$position] > 0) {
          $result['overall'][$position]['card'] = (int) $overall;
          $result['overall'][$position]['avg'] = $playerOverall->final_overall;
          $result['overall'][$position]['season_avg'] = null;
          if (isset($playerOverallAvg[$position])) {
            $result['overall'][$position]['avg'] = $playerOverallAvg[$position][$position];
          }

          foreach ($columns as $column => $category) {
            if (!isset($result['overall'][$position]['season_avg'][$category]['total'])) $result['overall'][$position]['season_avg'][$category]['total'] = 0;

            if (!isset($playerOverallAvg[$position])) {
              $columnOverall = $playerOverall->$column;
            } else {
              $columnOverall = $playerOverallAvg[$position][$column];
            }
            $result['overall'][$position]['season_avg'][$category][$column] = $columnOverall;
            $result['overall'][$position]['season_avg'][$category]['total'] += $columnOverall;

            $result['overall'][$position]['season_avg'][$category]['avg'] = BigDecimal::of($result['overall'][$position]['season_avg'][$category]['total'])->dividedBy(BigDecimal::of($categoryCntArr[$category]), 0, RoundingMode::HALF_UP)->toInt();
          }
        }
      }
    } catch (Throwable $e) {
      return ReturnData::setError($e->getMessage())->send(Response::HTTP_BAD_REQUEST);
    }
    return ReturnData::setData($result)->send(Response::HTTP_OK);
  }

  public function getPlayerDetailInfo(PlayerInfoRequest $request)
  {
    $input = $request->only([
      'player',
      'user_plate_card',
    ]);

    try {
      $result = [];
      $userPlateCardInfo = null;

      // last match
      $lastMatch = Schedule::withWhereHas('oneOptaPlayerDailyStat', function ($query) use ($input) {
        $query->where('player_id', $input['player'])
          ->gameParticipantPlayer();
      })->withWhereHas('onePlayerDailyStat', function ($query) use ($input) {
        $query->where('player_id', $input['player'])
          ->gameParticipantPlayer();
      })
        ->with([
          'home:' . implode(',', config('commonFields.team')),
          'away:' . implode(',', config('commonFields.team')),
        ])->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
        ->select('id', 'league_id', 'season_id', 'home_team_id', 'away_team_id', 'round', 'score_home', 'score_away', 'winner')
        ->currentSeasonSchedules()
        ->orderByDesc('ended_at')
        ->first();

      $lastFP = $lastMatch?->oneOptaPlayerDailyStat->fantasy_point;

      if (!is_null($lastFP)) unset($lastMatch->oneOptaPlayerDailyStat);
      $result['last_match']['fantasy_point'] = $lastFP;

      if (!is_null($input['user_plate_card'])) {
        $userPlateCardInfo = UserPlateCard::with([
          'plateCard.Team',
          'refPlayerOverall:id,player_id,season_id,final_overall,sub_position',
          'plateCard.league:id,league_code',
          'refPlayerOverall.userPlateCard'
        ])->where('id', $input['user_plate_card'])
          ->first();

        if ($userPlateCardInfo->status === PlateCardStatus::COMPLETE) {

          // 동일 카드 rank 계산
          $sub = UserPlateCard::whereHas('plateCardWithTrashed', function ($plateCard) use ($input) {
            $plateCard->where('player_id', $input['player']);
          })->where('status', DraftCardStatus::COMPLETE)
            ->selectRaw('id, ROW_NUMBER() OVER(PARTITION BY plate_card_id ORDER BY draft_level DESC, card_grade ASC, ingame_fantasy_point DESC) AS nRank');

          $result['card_rank'] = DB::query()->fromSub($sub, 'sub')->where('id', $input['user_plate_card'])->value('nRank');

          /**
           * @var FantasyCalculator $fipCalculator
           */
          $fipCalculator = app(FantasyCalculatorType::FANTASY_INGAME_POINT, [0]);

          // 선수 이름 팀 발행당시 position
          UserPlateCard::where('id', $input['user_plate_card'])
            ->with([
              'plateCardWithTrashed:id,league_id,' . implode(',', config('commonFields.player')),
              'plateCardWithTrashed.league:id,league_code',
              'draftSelection.schedule:id,season_id,home_team_id,away_team_id,started_at,round,score_home,score_away,winner',
              'draftSelection.schedule.season:id,name',
              'draftSelection.schedule.home:' . implode(',', config('commonFields.team')),
              'draftSelection.schedule.away:' . implode(',', config('commonFields.team')),
              'draftTeam:' . implode(',', config('commonFields.team')),
              'simulationOverall:user_plate_card_id,sub_position,final_overall'
            ])->get()
            ->map(function ($info) use (&$result, $fipCalculator, $lastMatch) {
              $result['player_id'] = $info->plateCardWithTrashed->player_id;
              $result['plate_card_id'] = $info->plate_card_id;
              $result['league_id'] = $info->plateCardWithTrashed->league_id;
              $result['league_code'] = $info->plateCardWithTrashed->league->league_code;
              $result['user_plate_card_id'] = $info->id;
              $result['is_mom'] = $info->is_mom;
              $result['position'] = $info->position;
              $result['sub_position'] = $info->simulationOverall->sub_position;
              foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
                $result[$field] = $info->plateCardWithTrashed->{$field};
              }

              if (!$info->is_free) {
                $result['schedule'] = $info->draftSelection->schedule;
              }
              $result['team'] = $info->draftTeam;
              $result['card_grade'] = $info->card_grade;
              $result['draft_level'] = $info->draft_level;
              $result['overall'] = (int)$info->simulationOverall->final_overall[$info->simulationOverall->sub_position];

              $result['price'] = AuctionBid::whereHas('auction.userPlateCard', function ($query) use ($info) {
                $query->where([
                  ['draft_level', $info->draft_level],
                  ['card_grade', $info->card_grade]
                ])->withWhereHas('PlateCardWithTrashed', function ($plateCard) use ($info) {
                  $plateCard->where('id', $info->plate_card_id);
                });
              })->whereIn('status', [AuctionBidStatus::SUCCESS, AuctionBidStatus::PURCHASED])
                ->orderBy('price')
                ->limit(1)
                ->value('price');

              if (is_null($result['price'])) $result['price'] = $info->min_price;

              if (is_null($lastMatch?->id)) {
                $result['last_match']['ingame_fantasy_point']  = null;
              } else {
                $result['last_match']['ingame_fantasy_point'] = $fipCalculator->calculate([
                  'user_card_attrs' => $info->toArray(),
                  'fantasy_point' => $result['last_match']['fantasy_point'],
                  'is_mom' => $lastMatch?->oneOptaPlayerDailyStat->is_mom,
                  'schedule_id' => $lastMatch->id,
                  'origin_stats' => $lastMatch?->oneOptaPlayerDailyStat->toArray(),
                  'fp_stats' => $lastMatch?->onePlayerDailyStat->toArray(),
                ]);
              }
            });
        }
      }
      if (empty($result['card_rank'])) {
        // upgrade || plate
        if (!is_null($userPlateCardInfo)) {
          $countByStatus = $userPlateCardInfo->refPlayerOverall->userPlateCard->pluck('status')->toArray();

          $result['id'] = $userPlateCardInfo->plate_card_id;
          $result['position'] = $userPlateCardInfo->position;
          foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
            $result[$field] = $userPlateCardInfo->plateCard->{$field};
          }
          foreach (config('commonFields.team') as $field) {
            $result['team'][$field] = $userPlateCardInfo->plateCard->team->{$field};
          }
          $result['headshot_path'] = $userPlateCardInfo->plateCard->headshot_path;
          $result['count'] = array_count_values($countByStatus);

          $positions = PlayerDailyStat::whereHas('season', function ($query) {
            $query->currentSeasons();
          })->where('player_id', $userPlateCardInfo->plateCard->player_id)
            ->selectRaw('DISTINCT(summary_position)')->get()->pluck('summary_position');
          $result['position_list'] = $positions;

          $result['league_code'] = $userPlateCardInfo->plateCard->league->league_code;
          $result['overall'] = null;
          $result['sub_position'] = null;
          if ($userPlateCardInfo->refPlayerOverall) {
            $result['overall'] = $userPlateCardInfo->refPlayerOverall->final_overall ?? null;
            $result['sub_position'] = $userPlateCardInfo->refPlayerOverall->sub_position;
          }
        } else {
          PlateCard::with([
            'team',
            'refPlayerOverall:player_id,season_id,final_overall,sub_position',
            'league:id,league_code'
          ])->currentSeason()
            ->where('player_id', $input['player'])
            ->get()
            ->map(function ($info) use (&$result) {
              $result['id'] = $info->id;
              $result['position'] = $info->position;
              foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
                $result[$field] = $info->{$field};
              }
              $result['player_name'] = $info->player_name;
              $result['short_player_name'] = $info->short_player_name;
              foreach (config('commonFields.team') as $field) {
                $result['team'][$field] = $info->team->{$field};
              }

              // 실제 경기에서 뛰었던 모든 position
              $positions = PlayerDailyStat::whereHas('season', function ($query) {
                $query->currentSeasons();
              })->where('player_id', $info->player_id)
                ->selectRaw('DISTINCT(summary_position)')->get()->pluck('summary_position');
              $result['position_list'] = $positions;

              $result['league_code'] = $info->league->league_code;
              $result['overall'] = null;
              $result['sub_position'] = null;
              if ($info->refPlayerOverall) {
                foreach ($info->refPlayerOverall as $playerOverall) {
                  if ($playerOverall['season_id'] === $info['season_id']) {
                    $result['overall'] = $playerOverall['final_overall'] ?? null;
                    $result['sub_position'] = $playerOverall['sub_position'];
                  }
                }
              }
            });
        }

        $sub = RefPlateCardRank::whereHas('season', function ($query) {
          $query->currentSeasons();
        })
          ->where('position', $result['position'])
          ->selectRaw('id, player_id, ROW_NUMBER() OVER (ORDER BY overall DESC, grade ASC, fantasy_point DESC, match_name ASC) AS nRank');

        $result['card_rank'] = DB::query()->fromSub($sub, 'sub')->where('player_id', $input['player'])->value('nRank');

        // 예정 경기
        $fixtureMatch = Schedule::with([
          'home:' . implode(',', config('commonFields.team')),
          'away:' . implode(',', config('commonFields.team')),
          'season'
        ])->where(function ($query) use ($result) {
          $query->where('home_team_id', $result['team']['id'])
            ->orWhere('away_team_id', $result['team']['id']);
        })->where('status', ScheduleStatus::FIXTURE)
          ->select('id', 'season_id', 'round', 'started_at', 'home_team_id', 'away_team_id')->latest()->first();
        $result['schedule'] = ($fixtureMatch) ?: $lastMatch?->toArray();
        $cardOrderCount = $this->draftService->getPlateCardOrderCount($result['id']);
        $result['total_quantity'] = (!is_null($cardOrderCount)) ? $cardOrderCount['total_quantity'] : 0;
      }
    } catch (Throwable $e) {
      return ReturnData::setError($e->getMessage())->send(Response::HTTP_BAD_REQUEST);
    }
    return ReturnData::setData($result)->send(Response::HTTP_OK);
  }
}
