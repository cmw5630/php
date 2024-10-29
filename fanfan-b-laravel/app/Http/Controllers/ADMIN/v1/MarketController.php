<?php

namespace App\Http\Controllers\ADMIN\v1;

use App\Enums\AuctionBidStatus;
use App\Enums\AuctionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Market\AuctionDetailRequest;
use App\Http\Requests\Admin\Market\AuctionListRequest;
use App\Models\game\Auction;
use App\Models\game\AuctionBid;
use ReturnData;
use Symfony\Component\HttpFoundation\Response;


class MarketController extends Controller
{
  protected int $limit = 20;
  public function __construct()
  {
  }

  public function list(AuctionListRequest $request)
  {
    $filter = $request->only([
      'type',
      'status',
      'q',
      'page',
      'per_page',
    ]);

    $this->limit = $filter['per_page'];

    $list = tap(Auction::query()
      ->with([
        'successAuctionBid.user' => function ($query) {
          $query->withoutGlobalScope('excludeWithdraw')->select(['id', 'name', 'status']);
        },
        'userPlateCard' => function ($query) {
          $query->withoutGlobalScope('excludeBurned');
        },
        'userPlateCard.plateCard' => function ($query) {
          $query->select([
            'id',
            'team_id',
            'player_id',
            'headshot_path',
            ...config('commonFields.player')
          ])->withTrashed();
        },
        'userPlateCard.draftSeason:id,name,league_id',
        'userPlateCard.draftSeason.league:id,league_code',
        'user' => function ($query) {
          $query->select(['id', 'name'])->withoutGlobalScope('excludeWithdraw');
        }
      ])
      ->when($filter['type'], function ($whenType, $type) {
        $whenType->where('type', $type);
      })
      ->when($filter['status'], function ($whenStatus, $status) {
        if ($status === AuctionStatus::CANCELED) {
          $whenStatus->whereIn('status',
            [AuctionStatus::EXPIRED, AuctionStatus::DISABLED, AuctionStatus::CANCELED]);
        } else {
          $whenStatus->where('status', $status);
        }
      })
      ->when($filter['q'], function ($whenQuery, $q) {
        $whenQuery->where(function ($query) use ($q) {
          $query->whereHas('user', function ($query) use ($q) {
            $query->whereLike('name', $q);
          })
            ->orWhereHas('userPlateCard.plateCard', function ($whereUserPlateCard) use ($q) {
              $whereUserPlateCard->nameFilterWhere($q);
            });
        });
      })
      ->latest()
      ->paginate($this->limit, ['*'], 'page', $filter['page'])
    )->map(function ($info) {
      $info->makeVisible(['created_at']);
      $startPrice = $info->start_price;
      $info->start_price = null;
      if ($info->status === AuctionStatus::SOLD) {
        // 즉구 아닐 때
        if ($info->auctionBid->where('status', AuctionBidStatus::PURCHASED)->count() < 1) {
          $info->start_price = $info->auctionBid->where('status',
            AuctionBidStatus::SUCCESS)->value('price');
          $info->buynow_price = null;
        }
      } else if ($info->status === AuctionStatus::BIDDING) {
        $info->start_price = $info->auctionBid->max('price') ?? $startPrice;
      } else {
        $info->start_price = $startPrice;
      }

      return $info;
    })
      ->toArray();

    return ReturnData::setData(__setPaginateData($list, []), $request)->send(Response::HTTP_OK);

  }

  public function auctionDetail(AuctionDetailRequest $request)
  {
    $filter = $request->only([
      'auction_id',
      'page'
    ]);

    $auction = Auction::query()
      ->with([
        'user' => function ($query) {
          $query->select(['id', 'name'])->withoutGlobalScope('excludeWithdraw');
        }
      ])
      ->find($filter['auction_id'])
      ->makeVisible('created_at');

    // $auction['current_user_id'] = $auction['user_plate_card']['user_id'];

    return ReturnData::setData($auction)->send(Response::HTTP_OK);
  }

  public function auctionDetailHistory(AuctionDetailRequest $request)
  {
    $filter = $request->only([
      'auction_id',
      'page'
    ]);

    $auctionBids = AuctionBid::query()
      ->with([
        'user' => function ($query) {
          $query->select(['id', 'name'])->withoutGlobalScope('excludeWithdraw');
        }
      ])
      ->where('auction_id', $filter['auction_id'])
      ->latest()
      ->paginate($this->limit, ['*'], 'page', $filter['page'])
      ->toArray();

    // $auctionBids = tap(AuctionBid::query()
    //   ->with('user:id,name')
    //   ->where('auction_id', $filter['auction_id'])
    //   ->latest()
    //   ->paginate($this->limit, ['*'], 'page', $filter['page'])
    // )
    // ->map(function ($item) {
    //   $rebid = AuctionBid::query()
    //     ->where('id', '<', $item->id)
    //     ->where('user_id', $item->user_id)
    //     ->exists();
    //   if (in_array($item->status, [AuctionBidStatus::SUCCESS, AuctionBidStatus::FAILED])) {
    //     $item->status = ($rebid ? 're' : '') .'bid';
    //   }
    //   return $item;
    // })
    // ->toArray();

    return ReturnData::setData(__setPaginateData($auctionBids, []), $request)->send(Response::HTTP_OK);
  }
}
