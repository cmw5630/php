<?php

namespace App\Http\Controllers\API\v1;

use App\Enums\AuctionBidStatus;
use App\Enums\AuctionStatus;
use App\Enums\AuctionType;
use App\Enums\FantasyCalculator\FantasyDraftCategoryType;
use App\Enums\GradeCardLockStatus;
use App\Enums\Opta\Card\CardGrade;
use App\Enums\PointRefType;
use App\Enums\PointType;
use App\Enums\QuestCollectionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Market\AuctionCancelRequest;
use App\Http\Requests\Api\Market\BidHistoryRequest;
use App\Http\Requests\Api\Market\BidRequest;
use App\Http\Requests\Api\Market\BuyNowRequest;
use App\Http\Requests\Api\Market\CardListRequest;
use App\Http\Requests\Api\Market\ChooseBidRequest;
use App\Http\Requests\Api\Market\MarketListRequest;
use App\Http\Requests\Api\Market\MarketMyListRequest;
use App\Http\Requests\Api\Market\MarketStoreRequest;
use App\Http\Requests\Api\Market\UserPlateCardIdRequest;
use App\Libraries\Classes\QuestRecorder;
use App\Models\simulation\SimulationOverall;
use Exception;
use App\Models\game\Auction;
use App\Models\game\AuctionBid;
use App\Models\user\UserPlateCard;
use App\Services\Data\DataService;
use App\Services\Market\MarketService;
use App\Services\User\UserService;
use Auth;
use DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use ReturnData;
use Symfony\Component\HttpFoundation\Response;

class MarketController extends Controller
{
  protected int $limit = 20;
  protected UserService $userService;
  protected MarketService $marketService;
  protected DataService $dataService;

  public function __construct(UserService $_userService, MarketService $_marketService, DataService $_dataService)
  {
    $this->userService = $_userService;
    $this->marketService = $_marketService;
    $this->dataService = $_dataService;
  }

  public function list(MarketListRequest $request)
  {
    $filter = $request->only([
      'type',
      'league',
      'club',
      'position',
      'grade',
      'player_id',
      'q',
      'sort',
      'order',
      'page',
      'per_page',
    ]);

    $nowTime = now();
    $this->limit = $filter['per_page'];

    try {
      $sub = SimulationOverall::query()
        ->selectRaw("user_plate_card_id,
      sub_position,
      CAST(JSON_UNQUOTE(JSON_EXTRACT(final_overall, CONCAT('$.', sub_position))) as unsigned) as overall");

      $list = tap(
        Auction::query()
          ->when($filter['player_id'], function ($whenPlayerId, $playerId) {
            $whenPlayerId->whereHas('userPlateCard.plateCard', function ($query) use ($playerId) {
              $query->where('player_id', $playerId);
            });
          })
          ->with([
            'userPlateCard.simulationOverall',
            'userPlateCard.plateCard:id,player_id,headshot_path,' . implode(',', config('commonFields.player')),
          ])
          ->when($filter['type'], function ($whenType, $type) {
            $whenType->where('type', $type);
          })
          ->when($filter['league'], function ($whenLeague, $league) {
            $whenLeague->whereHas('userPlateCard.draftSeason', function ($draftSeason) use ($league) {
              $draftSeason->where('league_id', $league);
            });
          })
          // userPlateCard 조건
          ->whereHas('userPlateCard', function ($userPlateCard) use ($filter) {
            $userPlateCard->when($filter['position'], function ($whenPos, $position) {
              $whenPos->whereIn('position', $position);
            })
              ->when($filter['club'], function ($whenClub, $type) {
                $whenClub->whereIn('draft_team_id', $type);
              })
              ->when($filter['grade'], function ($whenGrade, $grade) {
                $whenGrade->whereIn('card_grade', $grade);
              });
          })
          ->when($filter['q'], function ($whenKeyword, $name) {
            $whenKeyword->whereHas('userPlateCard.plateCard', function ($query) use ($name) {
              $query->nameFilterWhere($name);
            });
          })
          ->rightJoinSub($sub, 'overall', function ($join) {
            $auctionTbl = Auction::getModel()->getTable();
            $join->on($auctionTbl . '.user_plate_card_id', '=', 'overall.user_plate_card_id');
          })
          ->where('expired_at', '>', $nowTime)
          ->whereNull('sold_at')
          ->whereNull('canceled_at')
          ->where('user_id', '<>', $request->user()->id)
          ->orderBy($filter['sort'], $filter['order'])
          ->paginate($this->limit, ['*'], 'page', $filter['page'])
      )->map(function ($info) {
        $info->sub_position = $info->userPlateCard->simulationOverall->sub_position;
        $info->participated = $info->auctionBid->where('user_id', Auth::user()->id)->count() > 0;
        if ($info->type === AuctionType::OPEN) {
          $info->highest_price = $info->auctionBid->max('price');
        } else {
          unset($info->auctionBid);
          unset($info->userPlateCard->simulationOverall);
        }
        return $info;
      })
        ->toArray();

      return ReturnData::setData(__setPaginateData($list, []), $request)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function myList(MarketMyListRequest $request)
  {
    $filter = $request->only([
      'type',
      'month',
      'page',
      'per_page'
    ]);

    $this->limit = $filter['per_page'];

    $sub = SimulationOverall::query()
      ->selectRaw("user_plate_card_id,
      sub_position,
      CAST(JSON_UNQUOTE(JSON_EXTRACT(final_overall, CONCAT('$.', sub_position))) as unsigned) as overall");

    $list = tap(
      Auction::query()
        ->with([
          'userPlateCard' => function ($query) {
            $query->withoutGlobalScope('excludeBurned');
          },
          'userPlateCard.plateCard:id,player_id,headshot_path,' . implode(
            ',',
            config('commonFields.player')
          ),
          'userPlateCard.simulationOverall:id,user_plate_card_id,sub_position,final_overall'
        ])->whereBetween('created_at', [Carbon::parse($filter['month']), Carbon::parse($filter['month'])->endOfMonth()])
        ->when($filter['type'] === 'buy', function ($query) use ($request) {
          $query->whereHas('auctionBid', function ($auctionBidQuery) use ($request) {
            $auctionBidQuery->where('user_id', $request->user()->id);
          });
        }, function ($query) use ($request) {
          $query->where('user_id', $request->user()->id);
        })
        ->rightJoinSub($sub, 'overall', function ($join) {
          $auctionTbl = Auction::getModel()->getTable();
          $join->on($auctionTbl . '.user_plate_card_id', '=', 'overall.user_plate_card_id');
        })
        // ->where(function ($query) use ($userId) {
        //   $query->where('user_id', $userId)
        //     ->orWhereHas('auctionBid', function ($auctionBidQuery) use ($userId) {
        //       $auctionBidQuery->where('user_id', $userId);
        //     });
        // })
        ->orderByRaw("FIELD(status, " . sprintf('\'%s\', \'%s\', \'%s\', \'%s\'', AuctionStatus::BIDDING, AuctionStatus::SOLD, AuctionStatus::EXPIRED, AuctionStatus::CANCELED) . ')')
        ->orderBy('expired_at')
        ->paginate($this->limit, ['*'], 'page', $filter['page'])
    )->map(function ($info) use ($filter, $request) {
      $info->sub_position = $info->userPlateCard->simulationOverall->sub_position;
      $info->participated = $info->auctionBid->where('user_id', Auth::user()->id)->count() > 0;
      if ($filter['type'] === 'buy') {
        if ($info->status === AuctionStatus::SOLD) {
          $info->my_status = AuctionBidStatus::FAILED;
          $myBid = $info->auctionBid->where('user_id', $request->user()->id)
            ->whereIn('status', [AuctionBidStatus::SUCCESS, AuctionBidStatus::PURCHASED])
            ->first();
          if (!is_null($myBid)) {
            $info->my_status = $myBid->status;
          }
        } else {
          $info->my_status = AuctionStatus::BIDDING;
        }
      }
      // 판매리스트 & 오픈 경매
      if ($info->type === AuctionType::OPEN && $filter['type'] === 'sell') {
        $info->highest_price = $info->auctionBid->max('price');
      }

      // 구매리스트 & 경매 타입 상관 없이
      if ($filter['type'] === 'buy') {
        $info->my_price = $info->auctionBid->where('user_id', $request->user()->id)
          ->value('price');
      }

      if ($filter['type'] === 'sell' && $info->status === AuctionStatus::EXPIRED) {
        // 같은 카드의 재등록 건이 존재한다면, 상태를 Disabled 로
        if (Auction::where('parent_auction_id', $info->id)->exists()) {
          $info->status = AuctionStatus::DISABLED;
        } else {
          $info->expired_count++;
          [
            $info->min_price,
            $info->period_options
          ] = $this->marketService->getMarketDataset(
            $info->userPlateCard,
            min($info->expired_count, 5)
          );
        }
      }
      unset($info->auctionBid);
      unset($info->userPlateCard->simulationOverall);
      return $info;
    })
      ->toArray();

    return ReturnData::setData(__setPaginateData($list, []), $request)->send(Response::HTTP_OK);
  }

  public function cardList(CardListRequest $request)
  {
    $filter = $request->only([
      'league',
      'club',
      'position',
      'grade',
      'sort',
      'order',
      'page',
      'per_page',
      'q',
    ]);

    $this->limit = $filter['per_page'];

    $sub = SimulationOverall::query()
      ->selectRaw("user_plate_card_id,
      sub_position,
      CAST(JSON_UNQUOTE(JSON_EXTRACT(final_overall, CONCAT('$.', sub_position))) as unsigned) as overall");

    $list = tap(
      UserPlateCard::whereDoesntHave('auction', function ($query) use ($request) {
        $query->where('user_id', $request->user()->id)->where('status', AuctionStatus::BIDDING);
      })
        ->has('plateCard')
        ->rightJoinSub($sub, 'overall', function ($join) {
          $userPlateCardTbl = UserPlateCard::getModel()->getTable();
          $join->on($userPlateCardTbl . '.id', '=', 'overall.user_plate_card_id');
        })
        ->when($filter['league'], function ($whenLeague, $league) {
          $whenLeague->whereHas('draftSeason', function ($draftSeason) use ($league) {
            $draftSeason->where('league_id', $league);
          });
        })
        ->when($filter['q'], function ($whenKeyword, $name) {
          $whenKeyword->whereHas('plateCard', function ($query) use ($name) {
            $query->nameFilterWhere($name);
          });
        })
        ->with('myAuction', function ($query) {
          $query->where('status', AuctionStatus::EXPIRED);
        })
        ->with(['simulationOverall:user_plate_card_id,sub_position'])
        // userPlateCard 조건
        ->when($filter['position'], function ($whenPos, $position) {
          $whenPos->whereIn('position', $position);
        })
        ->when($filter['club'], function ($whenClub, $type) {
          $whenClub->whereIn('draft_team_id', $type);
        })
        ->when($filter['grade'], function ($whenGrade, $grade) {
          $whenGrade->whereIn('card_grade', $grade);
        })
        ->where([
          ['card_grade', '<>', CardGrade::NONE],
          ['user_id', $request->user()->id],
          ['is_open', true],
          ['is_free', false]
        ])
        ->whereNull('lock_status')
        ->orderBy($filter['sort'], $filter['order'])
        ->orderBy('player_name')
        ->oldest()
        ->paginate($this->limit, ['*'], 'page', $filter['page'])
    )->map(function ($item) {
      $item->player_id = $item->plateCard->player_id;
      foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
        $item->{$field} = $item->plateCard->{$field};
      }
      $item->sub_position = $item->simulationOverall->sub_position;
      $item->headshot_path = $item->plateCard->headshot_path;

      $season['id'] = $item->draft_season_id;
      $season['name'] = $item->draft_season_name;
      $season['league_id'] = $item->draftSeason->league_id;
      $season['league']['id'] = $item->draftSeason->league_id;
      $season['league']['league_code'] = $item->draftSeason->league->league_code;
      $item->draft_season = $season;

      $team['id'] = $item->draftTeam->id;
      $team['code'] = $item->draftTeam->code;
      $team['name'] = $item->draftTeam->name;
      $team['short_name'] = $item->draftTeam->short_name;
      $item->draft_team = $team;

      $item->auction_id = null;
      $item->expired_count = 0;

      if (!is_null($item->myAuction)) {
        $item->expired_count = $item->myAuction->expired_count + 1;
        $item->auction_id = $item->myAuction->id;
      }

      unset($item->myAuction);
      // 최소 거래 금액
      [, $item->period_options] = $this->marketService->getMarketDataset($item, $item->expired_count);

      unset($item->draft_season_id);
      unset($item->draft_season_name);
      unset($item->draft_team_id);
      unset($item->plateCard);
      unset($item->draftTeam);
      unset($item->draftSeason);
      unset($item->simulationOverall);
      return $item;
    })
      ->toArray();
    return ReturnData::setData(__setPaginateData($list, []), $request)->send(Response::HTTP_OK);

    // draftService > UserCardsByLeague 와 구조 같게 하기 위해 대체
    //
    // $list = UserPlateCard::doesntHave('auction')
    //   ->when($filter['league'], function ($whenLeague, $league) {
    //     $whenLeague->whereHas('draftSeason', function ($draftSeason) use ($league) {
    //       $draftSeason->where('league_id', $league);
    //     });
    //   })
    //   // userPlateCard 조건
    //   ->when($filter['position'], function ($whenPos, $position) {
    //     $whenPos->whereIn('position', $position);
    //   })
    //   ->when($filter['club'], function ($whenClub, $type) {
    //     $whenClub->whereIn('draft_team_id', $type);
    //   })
    //   ->when($filter['grade'], function ($whenGrade, $grade) {
    //     $whenGrade->whereIn('card_grade', $grade);
    //   })
    //   ->where('user_id', $request->user()->id)
    //   ->orderBy($filter['sort'], $filter['order'])
    //   ->paginate($this->limit, ['*'], 'page', $filter['page'])
    //   ->toArray();
    //
    // return ReturnData::setData(__setPaginateData($list, []), $request)->send(Response::HTTP_OK);
  }

  public function store(MarketStoreRequest $request)
  {
    $filter = $request->only([
      'auction_id',
      'user_plate_card',
      'type',
      'start_price',
      'buynow_price',
      'period',
    ]);

    try {
      if (isset($filter['user_plate_card'])) {
        $userPlateCard = UserPlateCard::find($filter['user_plate_card']);

        if ($userPlateCard->user_id !== ($userId = $request->user()?->id)) {
          // 임시 텍스트(자신의 카드가 아닐 때)
          throw new Exception('not my card');
        }
        if ($filter['buynow_price'] < $filter['start_price']) {
          // 임시 텍스트(즉구가가 시작가보다 적을 때)
          throw new Exception('buynow price cannot be lower than starting price.');
        }

        DB::beginTransaction();

        if (!__startUserPlateCardLock($userPlateCard->id, GradeCardLockStatus::MARKET)) {
          // 다른 곳에서 사용중인 카드
          throw new Exception('this card is locked.');
        }

        // 최저 시작가 가져오기
        [$minGold,] = $this->marketService->getMarketDataset($userPlateCard);
        if ($minGold > $filter['start_price']) {
          throw new Exception('start price cannot be lower than minimum price.');
        }

        $auctionInsert = new Auction;
        $auctionInsert->user_id = $userId;

        $levels = FantasyDraftCategoryType::getValues();
        array_walk($levels, function ($val, $key) use (&$levels) {
          if ($val === 'summary') {
            unset($levels[$key]);
          } else {
            $levels[$key] .= '_level';
          }
        });
        $levels = [...$levels, 'draft_level'];

        foreach ($levels as $level) {
          $auctionInsert->{$level} = $userPlateCard->{$level};
        }

        foreach ($filter as $key => $val) {
          if (is_null($val)) {
            continue;
          }

          if ($key === 'user_plate_card') {
            $key .= '_id';
          }
          if ($key === 'period') {
            $auctionInsert->expired_at = now()->addHours($val);
          }
          $auctionInsert->{$key} = $val;
        }
      } else {
        $auction = Auction::find($filter['auction_id']);

        DB::beginTransaction();

        if (!__startUserPlateCardLock($auction->user_plate_card_id, GradeCardLockStatus::MARKET)) {
          // 다른 곳에서 사용중인 카드
          throw new Exception('this card is locked.');
        }
        $auctionInsert = $auction->replicate();
        $expiredCount = $auctionInsert->expired_count + 1;
        $auctionInsert->status = AuctionStatus::BIDDING;
        $auctionInsert->parent_auction_id = $auction->id;
        $auctionInsert->expired_count = $expiredCount;
        // 최저 시작가 가져오기
        [$minGold,] = $this->marketService->getMarketDataset($auction->userPlateCard, $expiredCount);

        if ($minGold > $filter['start_price']) {
          throw new Exception('start price cannot be lower than minimum price.');
        }

        $auctionInsert->start_price = $filter['start_price'];
        $auctionInsert->buynow_price = $filter['buynow_price'];
        $auctionInsert->period = $filter['period'];
        $auctionInsert->expired_at = now()->addHours($filter['period']);
        $auctionInsert->type = $filter['type'];
      }

      $auctionInsert->save();

      // quest
      (new QuestRecorder())->act(QuestCollectionType::TRANSFER, $request->user()->id);

      DB::commit();
      return ReturnData::send(Response::HTTP_OK);
    } catch (Exception $th) {
      DB::rollBack();
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function buyNow(BuyNowRequest $request)
  {
    $filter = $request->only([
      'auction_id'
    ]);

    Schema::connection('simulation')->disableForeignKeyConstraints();
    DB::beginTransaction();
    try {
      $auction = Auction::lockForUpdate()->find($filter['auction_id']);

      $user = $request->user();
      if ($user->gold < $auction->buynow_price) {
        throw new Exception(__('user.not_enough_money'));
      }
      if ($auction->user_id === $user->id) {
        throw new Exception(__('market.disallowed_participate_my_auction'));
      }

      $auction->sold_at = now();
      $auction->status = AuctionStatus::SOLD;
      $auction->save();

      foreach ($auction->auctionBid as $bid) {
        $bid->status = AuctionBidStatus::FAILED;
        $bid->save();
      }

      $auctionBid = new AuctionBid;
      $auctionBid->auction_id = $auction->id;
      $auctionBid->user_id = $request->user()->id;
      $auctionBid->price = $auction->buynow_price;
      $auctionBid->status = AuctionBidStatus::PURCHASED;
      $auctionBid->save();

      $auction->userPlateCard->user_id = $request->user()->id;

      // 최소 거래가격 update
      [$minPrice,] = $this->marketService->getMarketDataset($auction->userPlateCard);
      $auction->userPlateCard->min_price = $minPrice;
      $auction->userPlateCard->save();

      // 오버롤 테이블의 user_id도 변경
      $auction->userPlateCard->simulationOverall->user_id = $request->user()->id;
      $auction->userPlateCard->simulationOverall->save();

      $description = sprintf('Auction Id : %d, BuyNow', $auction->id);
      $this->userService->minusUserPointWithLog(
        $auction->buynow_price,
        PointType::GOLD,
        PointRefType::MARKET,
        $description
      );
      $this->userService->plusUserPointWithLog(
        $auction->buynow_price,
        PointType::GOLD,
        PointRefType::MARKET,
        $description,
        $auction->user_id
      );

      // 로그
      $this->marketService->userPlateCardLog($auction->user_plate_card_id, $request->user()->id);

      // 구매자 노티
      $socketData = [
        'template_id' => 'market-buy-complete',
        'target_user_id' => $request->user()->id,
        'dataset' => [
          'player' => $auction->userPlateCard->plateCard->toArray(),
        ],
      ];

      $alarm = app('alarm', ['id' => $socketData['template_id']]);
      $alarm->params($socketData['dataset'])->send([$socketData['target_user_id']]);

      // 판매자 노티
      $socketData = [
        'template_id' => 'market-sell-complete',
        'target_user_id' => $auction->user_id,
        'dataset' => [
          'player' => $auction->userPlateCard->plateCard->toArray(),
        ],
      ];
      $alarm = app('alarm', ['id' => $socketData['template_id']]);
      $alarm->params($socketData['dataset'])->send([$socketData['target_user_id']]);

      // quest
      (new QuestRecorder())->act(QuestCollectionType::TRANSFER, $request->user()->id);

      __endUserPlateCardLock($auction->user_plate_card_id, GradeCardLockStatus::MARKET);

      DB::commit();
      return ReturnData::send(Response::HTTP_OK);
    } catch (Exception $th) {
      DB::rollback();
      logger($th);

      return ReturnData::setError($th->getMessage())->send($th->getCode());
    } finally {
      Schema::connection('simulation')->enableForeignKeyConstraints();
    }
  }

  public function bid(BidRequest $request)
  {
    $filter = $request->only([
      'auction_id',
      'price'
    ]);

    DB::beginTransaction();
    try {
      $auction = Auction::lockForUpdate()->find($filter['auction_id']);

      // 내 경매 참여 불가
      if ($auction->user_id === $request->user()->id) {
        throw new Exception(__('market.disallowed_participate_my_auction'));
      }

      // 시작가보다 낮음
      if ($auction->auctionBid->count() < 1 && $auction->start_price > $filter['price']) {
        throw new Exception(__('market.bid_price_low', ['old' => $auction->start_price]), Response::HTTP_BAD_REQUEST);
      }

      $highest = $auction->auctionBid->sortByDesc('price')->first();
      // 최고 입찰자가 나임.
      if ($highest?->user_id === $request->user()->id) {
        throw new Exception(__('market.already_highest'), Response::HTTP_BAD_REQUEST);
      }

      // 오픈이면서 이전 경매가보다 낮음
      $highestPrice = $highest?->price ?? 0;
      if ($auction->type === AuctionType::OPEN && $highestPrice >= $filter['price']) {
        throw new Exception(__('market.bid_price_low', ['old' => $highestPrice]), Response::HTTP_BAD_REQUEST);
      }

      // 즉구가보다 높음
      if ($auction->buynow_price <= $filter['price']) {
        throw new Exception(__('market.bid_price_high', ['buynow_price' => $auction->buynow_price]), Response::HTTP_BAD_REQUEST);
      }

      // 이미 참여한 블라인드 경매
      if (
        $auction->type === AuctionType::BLIND &&
        $auction->auctionBid->where('user_id', $request->user()->id)->count() > 0
      ) {
        throw new Exception(__('market.blind_action_already_participated'), Response::HTTP_BAD_REQUEST);
      }

      // 골드 지불, 기존 최고 입찰자 골드 반환
      $description = sprintf('Auction Id : %d, Bid On', $auction->id);
      $this->userService->minusUserPointWithLog(
        $filter['price'],
        PointType::GOLD,
        PointRefType::MARKET,
        $description
      );
      if (!is_null($highest) && $auction->type === AuctionType::OPEN) {
        $socketData = [
          'template_id' => 'market-buy-failed',
          'target_user_id' => $highest->user_id,
          'dataset' => [
            'player_name' => $auction->userPlateCard->player_name,
          ],
        ];

        $alarm = app('alarm', ['id' => $socketData['template_id']]);
        $alarm->params($socketData['dataset'])->send([$socketData['target_user_id']]);

        $this->userService->plusUserPointWithLog(
          $highest->price,
          PointType::GOLD,
          PointRefType::MARKET,
          $description,
          $highest->user_id
        );
      }

      $bid = new AuctionBid();
      $bid->auction_id = $auction->id;
      $bid->user_id = $request->user()->id;
      $bid->price = $filter['price'];
      $bid->save();

      DB::commit();
      return ReturnData::send(Response::HTTP_OK);
    } catch (Exception $th) {
      DB::rollback();
      logger($th);

      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function bidHistory(BidHistoryRequest $request)
  {
    $filter = $request->only([
      'auction_id'
    ]);

    $auction = Auction::query()
    ->with([
      'userPlateCard',
      'auctionBid:id,auction_id,price,user_id,created_at,status',
      'user:id,name'
    ])
      ->select([
        'id',
        'user_plate_card_id',
        'type',
        'status',
        'user_id',
        'start_price',
        'buynow_price',
        'expired_at',
        'created_at',
        'sold_at',
      ])
      ->find($filter['auction_id'])
      ->makeVisible(['created_at']);//created_at 필드를 포함시키기 위해 makeVisible 메서드를 호출합니다.

    // 경매가 'SOLD' 상태일 때, 요청 사용자의 ID가 'FAILED' 상태가 아닌 입찰자 ID와 일치하는지 확인
    if ($auction->status === AuctionStatus::SOLD) {
      $bidUserId = $auction->auctionBid
        ->where('status', '!=', AuctionBidStatus::FAILED)
        ->value('user_id');

      if ($request->user()?->id !== $bidUserId) {
        return ReturnData::setError('Auction not found')->send(Response::HTTP_NOT_FOUND);
      }
    }

    $result = $auction->toArray();
    $result['user_name'] = $auction->user?->name; //경매의 user_name을 설정합니다.
    if ($auction->user && ($auction->user_id !== $request->user()?->id)) { //현재 사용자가 경매의 소유자(user_id)가 아니라면, 사용자의 이름을 마스킹하여 user_name을 업데이트합니다.
      $auction->user->maskingName();
      $result['user_name'] = $auction->user->name;
    }
    unset($result['user']);

    if ($auction->type === AuctionType::OPEN || $auction->user_id === $request->user()?->id) { //모든 입찰(auctionBid)의 사용자 이름을 마스킹한 후, is_me 필드를 추가하고, 사용자의 이름을 설정합니다.
      foreach ($auction->auctionBid as $idx => $bid) {
        $bid->user?->maskingName();
        $result['auction_bid'][$idx]['is_me'] = $bid->user_id === $request->user()?->id;
        $result['auction_bid'][$idx]['name'] = $bid->user->name;
        unset($result['auction_bid'][$idx]['user_id']);
      }
    } else {
      unset($result['auction_bid']); //auction_bid 필드를 결과에서 제거합니다.
      foreach ($auction->auctionBid as $bid) {
        $bid->user?->maskingName();
        if ($bid->user_id === $request->user()?->id) {
          $result['auction_bid'][] = $bid; //현재 사용자의 입찰 정보만 필터링하여 결과에 추가합니다.
          break;
        }
      }
      // $result['auction_bid'] = array_values($result['auction_bid']);
    }

    return ReturnData::setData($result, $request)->send(Response::HTTP_OK);
  }

  public function chooseBid(ChooseBidRequest $request)
  {
    $filter = $request->only([
      'auction_id',
      'bid_id',
    ]);
    $nowChosenTime = now();

    Schema::connection('simulation')->disableForeignKeyConstraints();

    DB::beginTransaction();
    try {
      $auction = Auction::lockForUpdate()->find($filter['auction_id']);

      // $diffMin = now()->diffInMinutes(Carbon::parse($auction->expired_at), false);
      $diffMin = Carbon::parse($auction->created_at)->diffInMinutes($nowChosenTime, false);

      // 내 경매 아님
      if ($auction->user_id !== $request->user()?->id) {
        throw new Exception('not my bid');
      }

      // 진행중인 경매 아님
      if (($auction->status !== AuctionStatus::BIDDING) && (Carbon::parse($auction->expired_at) <= $nowChosenTime)) {
        throw new Exception('not proceeding bid.');
      }

      // 경매 끝나기 한 시간 전
      // if ($diffMin > 60 || $diffMin < 1) {

      // 경매 시작 1시간 이후
      if ($diffMin < 60) {
        throw new Exception(__('market.no_choosing_time'));
      }

      // 경매 참가자 골드 반환
      $description = sprintf('Auction Id : %d, Blind Bid Refund.', $auction->id);
      foreach ($auction->auctionBid as $bid) {
        $this->userService->plusUserPointWithLog(
          $bid->price,
          PointType::GOLD,
          PointRefType::MARKET,
          $description
        );
      }
      $auctionBid = $auction->auctionBid->find($filter['bid_id']);
      $auction->sold_at = $nowChosenTime;
      $auction->status = AuctionStatus::SOLD;
      $auctionBid->status = AuctionBidStatus::SUCCESS;
      $auction->userPlateCard->user_id = $auctionBid->user_id;
      // 오버롤 테이블의 user_id도 변경
      $auction->userPlateCard->simulationOverall->user_id = $auctionBid->user_id;
      $auction->save();
      $auctionBid->save();
      $auction->userPlateCard->save();
      $auction->userPlateCard->simulationOverall->save();

      $description = sprintf('Auction Id : %d, Blind Bid Success.', $auction->id);
      $this->userService->plusUserPointWithLog(
        $auctionBid->price,
        PointType::GOLD,
        PointRefType::MARKET,
        $description
      );
      // $this->userService->minusUserPointWithLog($auctionBid->price, PointType::GOLD,
      //   PointRefType::MARKET, $description, $auctionBid->user_id);

      // quest
      (new QuestRecorder())->act(QuestCollectionType::TRANSFER, $request->user()->id);


      __endUserPlateCardLock($auction->user_plate_card_id, GradeCardLockStatus::MARKET);
      DB::commit();
      return ReturnData::send(Response::HTTP_OK);
    } catch (Exception $th) {
      DB::rollback();
      logger($th);

      return ReturnData::setError($th->getMessage())->send($th->getCode());
    } finally {
      Schema::connection('simulation')->enableForeignKeyConstraints();
    }
  }

  public function cancel(AuctionCancelRequest $request)
  {
    $filter = $request->only([
      'auction_id',
    ]);

    $nowTime = now();
    DB::beginTransaction();
    try {
      $auction = Auction::lockForUpdate()->find($filter['auction_id']);

      // 등록한 지 30분 이내
      if (Carbon::parse($auction->created_at)->diffInMinutes($nowTime, false) > 30) {
        throw new Exception('30 minutes');
      }

      // 참여자 있는지 체크
      if ($auction->auctionBid->count() > 0) {
        throw new Exception('bidders exists.');
      }

      $auction->status = AuctionStatus::CANCELED;
      $auction->canceled_at = $nowTime;
      $auction->save();

      __endUserPlateCardLock($auction->user_plate_card_id, GradeCardLockStatus::MARKET);
      DB::commit();
      return ReturnData::send(Response::HTTP_OK);
    } catch (Exception $th) {
      DB::rollback();
      logger($th);

      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function similar(UserPlateCardIdRequest $request)
  {
    $filter = $request->only([
      'user_plate_card_id'
    ]);
    $userPlateCard = UserPlateCard::find($filter['user_plate_card_id']);
    $result = [];
    Auction::whereHas('userPlateCard', function ($query) use ($userPlateCard) {
      $query->where([
        ['plate_card_id', $userPlateCard->plate_card_id]
      ]);
    })
      ->with([
        'userPlateCard.plateCard:id,team_id,player_id,headshot_path,' . implode(
          ',',
          config('commonFields.player')
        ),
        'userPlateCard.draftSeason:id,name,league_id',
        'userPlateCard.draftSeason.league:id,league_code',
        'userPlateCard.simulationOverall'
      ])
      ->where([
        ['user_plate_card_id', '<>', $filter['user_plate_card_id']],
        ['user_id', '<>', $request->user()?->id],
        ['status', AuctionStatus::BIDDING]
      ])
      ->inRandomOrder()
      ->limit(6)
      ->get()
      ->map(function ($info) use (&$result) {
        $dataSet = $info;
        [, $dataSet['user_plate_card']] = $this->dataService->getPlayerBaseInfo($info->userPlateCard);

        if ($info->type === AuctionType::OPEN) {
          $dataSet['highest_price'] = $info->auctionBid->max('price');
        }
        unset($info->auctionBid);
        $result[] = $info;
      });

    return ReturnData::setData($result, $request)->send(Response::HTTP_OK);
  }

  public function transaction(UserPlateCardIdRequest $request)
  {
    $filter = $request->only([
      'user_plate_card_id'
    ]);
    $userPlateCard = UserPlateCard::find($filter['user_plate_card_id']);
    $result = Auction::whereHas('userPlateCard', function ($query) use ($userPlateCard) {
      $query->withoutGlobalScope('excludeBurned')
        ->where([
          ['plate_card_id', $userPlateCard->plate_card_id],
          ['card_grade', $userPlateCard->card_grade]
        ]);
    })
      ->with([
        'userPlateCard',
        'userPlateCard.plateCard:id,player_id,headshot_path,' . implode(
          ',',
          config('commonFields.player')
        ),
      ])
      ->where([
        ['user_plate_card_id', '<>', $filter['user_plate_card_id']],
        ['status', AuctionStatus::SOLD],
      ])
      ->latest('sold_at')
      ->limit(15)
      ->get()
      ->map(function ($info) {
        $info->sold_price = $info->auctionBid->whereIn(
          'status',
          [AuctionBidStatus::PURCHASED, AuctionBidStatus::SUCCESS]
        )->max('price');
        unset($info->auctionBid);
        return $info;
      });

    return ReturnData::setData($result, $request)->send(Response::HTTP_OK);
  }
}
