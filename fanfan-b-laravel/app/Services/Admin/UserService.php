<?php

namespace App\Services\Admin;

use App\Enums\AuctionBidStatus;
use App\Enums\AuctionStatus;
use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Card\PlateCardStatus;
use App\Libraries\Classes\Exception;
use App\Libraries\Traits\GameTrait;
use App\Models\BlockedIp;
use App\Models\game\Auction;
use App\Models\game\GameJoin;
use App\Models\log\UserPointLog;
use App\Models\user\User;
use App\Models\user\UserLoginLog;
use App\Models\user\UserPlateCard;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Contracts\Auth\Authenticatable;
use Throwable;

interface UserServiceInterface
{
  public function userList(array $_filter): array;
}

class UserService implements UserServiceInterface
{
  use GameTrait;

  protected ?Authenticatable $admin;
  protected $limit;

  public function __construct()
  {
    $this->admin = Auth::guard('admin')->user();
    $this->limit = 20;
  }

  public function userList(array $_filter): array
  {
    $this->limit = $_filter['limit'];

    $user = tap(
      User::withoutGlobalScope('excludeWithdraw')
        ->with([
          'latestUserLoginLogs',
          'restriction' => function ($query) {
            $query->whereDate('until_at', '>=', now());
          },
        ])
        ->when($_filter['q'], function ($query, $keyword) {
          $query->whereLike(['email', 'name'], $keyword);
        })
        ->when($_filter['status'], function ($query, $status) {
          $query->whereIn('status', $status);
        })
        ->when($_filter['provider'], function ($query, $provider) {
          $query->where(function ($providerQuery) use ($provider) {
            // 일반
            if (in_array('direct', $provider)) {
              $providerQuery->doesntHave('linkedSocialAccounts');
              unset($provider[array_search('direct', $provider)]);
            }
            // 구글, 페이스북
            if (count($provider) > 0) {
              $providerQuery->orWhereHas(
                'linkedSocialAccounts',
                function ($socialQuery) use ($provider) {
                  $socialQuery->whereIn('provider_name', $provider);
                }
              );
            }
          });
        })
        ->withTrashed()
        ->latest()
        ->paginate($this->limit, ['*'], 'page', $_filter['page'])
    )->map(function ($item) {
      if (is_null($item->linkedSocialAccounts)) {
        $item->provider = 'direct';
      } else {
        $item->provider = $item->linkedSocialAccounts->provider_name;
      }

      $item->latest_login = $item->latestUserLoginLogs?->created_at->toDateTimeString();
      unset($item->latestUserLoginLogs, $item->linkedSocialAccounts);

      return $item;
    })
      ->toArray();

    return __setPaginateData($user, []);
  }

  public function detail(array $_data)
  {
    $list = [];

    switch ($_data['mode']) {
      case 'point':
        $list = $this->userPointHistory($_data['user_id'], $_data['page'], $_data['sub_mode']);
        break;
      case 'join':
        $extra = $this->userJoinSum($_data['user_id']);
        $list = $this->userJoinHistory($_data['user_id'], $_data['page']);
        break;
      case 'card':
        $extra = ['count' => $this->userCardCount($_data['user_id'])];
        $list = $this->userCardHistory($_data['user_id'], $_data['page'], $_data['sub_mode'], $_data['q']);
        break;
      case 'trade':
        $list = $this->userTradeHistory($_data['user_id'], $_data['page'], $_data['sub_mode']);
        break;
      case 'login':
        $list = $this->userLoginHistory($_data['user_id'], $_data['page']);
    }

    return array_merge($extra ?? [], __setPaginateData($list, []));
  }

  public function userLoginHistory($_userId, $_page)
  {
    return tap(UserLoginLog::where('user_id', $_userId)
      ->latest()
      ->paginate($this->limit, ['*'], 'page', $_page)
    )->map(function ($info) {
      $info->browser = __getBrowserName($info->agent);
      unset($info->agent);
      return $info;
    })
      ->toArray();
  }

  public function loginHistory(array $_filter)
  {
    $sub = UserLoginLog::query()
      ->when($_filter['q'], function ($whenQuery, $q) {
        $whenQuery->where(function ($query) use ($q) {
          $query->where('ip_address', $q)
            ->orWhereHas('user', function ($query) use ($q) {
              $query->select('id', 'email', 'name')
                ->whereLike(['email', 'name'], $q);
            });
        });
      })
      ->whereBetween('created_at', [
        Carbon::parse($_filter['start_date'])->startOfDay(),
        Carbon::parse($_filter['end_date'])->endOfDay()
      ]);

    $ips = [];
    $loginLog = tap(
      DB::query()->fromSub($sub, 'sub')
        ->groupBy('sub.ip_address')
        ->selectRaw('sub.ip_address, count(sub.id) as login_count, count(distinct(sub.user_id)) as user_count, max(sub.created_at) as latest_login')
        ->orderByDesc('latest_login')
        ->paginate($this->limit, ['*'], 'page', $_filter['page'])
    )
      ->map(function ($item) use (&$ips) {
        $ips[] = $item->ip_address;
      })
      ->toArray();

    DB::enableQueryLog();
    $blockedIps = BlockedIp::whereIn('ip_address', $ips)
      // ->where(function ($query) {
      //   $query->whereNull('until')
      //     ->orWhere('until', '>', now());
      // })
      ->pluck('id', 'ip_address')
      ->toArray();

    foreach ($loginLog['data'] as &$log) {
      $log->blocked_ip_id = null;
      if (isset($blockedIps[$log->ip_address])) {
        $log->blocked_ip_id = $blockedIps[$log->ip_address];
      }
    }

    return __setPaginateData($loginLog, []);
  }

  public function loginCountToday()
  {
    return UserLoginLog::whereDate('created_at', now()->toDateString())
      ->get()
      ->count();
  }

  private function userPointHistory($_userId, $_page, $_pointType)
  {
    try {
      return tap(
        UserPointLog::when($_pointType, function ($pointTypeQuery, $pointType) {
          $pointTypeQuery->where('point_type', $pointType);
        })
          ->where('user_id', $_userId)
          ->select([
            'point_type',
            'description',
            'amount',
            'created_at'
          ])
          ->latest()
          ->paginate($this->limit, ['*'], 'page', $_page)
      )
        ->map(function ($info) {
          $info->makeVisible('created_at');
          return $info;
        })
        ->toArray();
    } catch (Throwable $th) {
      throw new Exception($th->getMessage());
    }
  }

  // 보상액 정해지면 그때 다시..
  // private function userJoinTotal()
  // {
  //   try {
  //     return GameJoin::
  //       ->latest()
  //       ->paginate($this->limit, ['*'], 'page', $_page)
  //       ->toArray();
  //   } catch (Throwable $th) {
  //     throw new Exception($th->getMessage());
  //   }
  // }

  private function userJoinHistory($_userId, $_page)
  {
    try {
      return tap(
        GameJoin::withoutGlobalScope('excludeWithdraw')
          ->where('user_id', $_userId)
          ->select([
            'id',
            'game_id',
            'reward',
            'ranking',
            'point',
            'reward',
            'created_at',
          ])
          ->latest()
          ->paginate($this->limit, ['*'], 'page', $_page)
      )
        ->map(function ($info) {
          $info->makeVisible('created_at');
          $info->game_start_date = $info->game->start_date;
          $info->game_end_date = $info->game->end_date;
          $info->game_round_no = $info->game->ga_round;
          $info->status = $this->getStatusCount($info->game_id)['status'];
          $info->league_id = $info->game->season->leagueWithoutGS->id;
          $info->league_code = $info->game->season->leagueWithoutGS->league_code;

          unset($info->game);
          return $info;
        })
        ->toArray();
    } catch (Throwable $th) {
      throw new Exception($th->getMessage());
    }
  }

  private function userJoinSum($_userId)
  {
    $result = GameJoin::withoutGlobalScope('excludeWithdraw')
      ->where('user_id', $_userId)
      ->selectRaw('cast(sum(reward) as unsigned) as total_reward, count(id) as total_count')
      ->first()
      ->toArray();

    $result['running_count'] = GameJoin::where('user_id', $_userId)
      ->whereHas('game', function ($query) {
        $query->whereNull('completed_at');
      })
      ->selectRaw('count(id) as join_count')
      ->value('join_count');

    return $result;
  }

  private function userCardCount($_userId)
  {
    try {
      return UserPlateCard::query()
        ->selectRaw('
        cast(sum(if(' . sprintf('card_grade = \'%s\' and status = \'%s\'', CardGrade::NONE, PlateCardStatus::PLATE) . ', 1, 0)) as unsigned) as plate_card_count,
        cast(sum(if(' . sprintf('card_grade = \'%s\' and status = \'%s\'', CardGrade::NONE, PlateCardStatus::UPGRADING) . ', 1, 0)) as unsigned) as upgrading_card_count,
        cast(sum(if(' . sprintf('status = \'%s\'', PlateCardStatus::COMPLETE) . ', 1, 0)) as unsigned) as complete_card_count,
        cast(sum(if(' . sprintf('card_grade = \'%s\'', CardGrade::GOAT) . ', 1, 0)) as unsigned) as goat,
        cast(sum(if(' . sprintf('card_grade = \'%s\'', CardGrade::FANTASY) . ', 1, 0)) as unsigned) as fantasy,
        cast(sum(if(' . sprintf('card_grade = \'%s\'', CardGrade::ELITE) . ', 1, 0)) as unsigned) as elite,
        cast(sum(if(' . sprintf('card_grade = \'%s\'', CardGrade::AMAZING) . ', 1, 0)) as unsigned) as amazing,
        cast(sum(if(' . sprintf('card_grade = \'%s\'', CardGrade::DECENT) . ', 1, 0)) as unsigned) as decent,
        cast(sum(if(' . sprintf('card_grade = \'%s\'', CardGrade::NORMAL) . ', 1, 0)) as unsigned) as normal
        ')
        ->where('user_id', $_userId)
        ->first();
    } catch (Throwable $th) {
      throw new Exception($th->getMessage());
    }
  }

  private function userCardHistory($_userId, $_page, $_cardType, $_keyword = null)
  {
    try {
      if ($_cardType === 'grade') {
        return tap(
          UserPlateCard::with([
            'plateCard' => function ($query) {
              $query->withTrashed()->select(
                'id',
                'first_name',
                'last_name',
                'short_first_name',
                'short_last_name',
                'match_name',
                'position',
                'league_id',
                'team_id',
                'deleted_at'
              );
            },
            'plateCard.team:' . implode(',', config('commonFields.team')),
            'plateCard.league:id,league_code',
            'draftTeam:id,name',
            'draftComplete:user_plate_card_id,summary_position',
            'draftSeason.league:id,league_code',
          ])
            ->where([
              ['user_id', $_userId],
              ['card_grade', '!=', CardGrade::NONE],
            ])
            ->when($_keyword, function ($when, $keyword) {
              $when->whereHas('plateCard', function ($plateCard) use ($keyword) {
                $plateCard->nameFilterWhere($keyword);
              });
            })
            ->select(['id', 'plate_card_id', 'draft_team_id', 'draft_season_id', 'draft_level', 'card_grade', 'draft_completed_at', 'is_open'])
            ->latest('draft_completed_at')
            ->paginate($this->limit, ['*'], 'page', $_page)
        )->map(function ($info) {
          $info->is_valid = is_null($info->plateCard->deleted_at);
          return $info;
        })
          ->toArray();
      }

      return tap(UserPlateCard::where('user_id', $_userId)
        ->groupBy('plate_card_id')
        ->with([
          'plateCard' => function ($query) {
            $query->withTrashed()->select('id', 'first_name', 'last_name', 'short_first_name',
              'short_last_name', 'match_name', 'position', 'league_id', 'team_id', 'deleted_at');
          },
          'plateCard.team:' . implode(',', config('commonFields.team')),
          'plateCard.league:id,league_code'
        ])
        ->where('card_grade', CardGrade::NONE)
        ->when($_keyword, function ($when, $keyword) {
          $when->whereHas('plateCard', function ($plateCard) use ($keyword) {
            $plateCard->nameFilterWhere($keyword);
          });
        })
        ->selectRaw('plate_card_id, count(id) as card_count, IFNULL(max(updated_at), max(created_at)) as last_updated')
        ->latest('last_updated')
        ->oldest('plate_card_id')
        ->paginate($this->limit, ['*'], 'page', $_page)
      )->map(function ($info) {
        $info->is_valid = is_null($info->plateCard->deleted_at);
        return $info;
      })
        ->toArray();
    } catch (Throwable $th) {
      throw new Exception($th->getMessage());
    }
  }

  private function userTradeHistory($_userId, $_page, $_tradeType)
  {
    if ($_tradeType === 'buy') {
      return tap(Auction::query()
        ->whereHas('auctionBid', function ($query) use ($_userId) {
          $query->where('user_id', $_userId);
        })
        ->with([
          'auctionBid' => function ($query) use ($_userId) {
            $query->where('user_id', $_userId)
              ->orderByRaw("FIELD(status, " . sprintf('\'%s\', \'%s\', \'%s\'',
                  AuctionBidStatus::PURCHASED, AuctionBidStatus::SUCCESS,
                  AuctionBidStatus::FAILED) . ')');
          },
          'userPlateCard.plateCard' => function ($query) {
            $query->select([
              'id',
              'player_id',
              'headshot_path',
              ...config('commonFields.player')
            ])->withTrashed();
          },
        ])
        ->whereIn('status', [AuctionStatus::BIDDING, AuctionStatus::SOLD])
        ->latest()
        ->paginate($this->limit, ['*'], 'page', $_page)
      )
        ->map(function ($item) {
          $season['id'] = $item->userPlateCard->draft_season_id;
          $season['name'] = $item->userPlateCard->draft_season_name;
          $season['league_id'] = $item->userPlateCard->draftSeason->league_id;
          $season['league']['id'] = $item->userPlateCard->draftSeason->league_id;
          $season['league']['league_code'] = $item->userPlateCard->draftSeason->league->league_code;
          $item['auction_bid'] = $item->auctionBid->first();

          unset($item->auctionBid);
          $item->userPlateCard->draft_season = $season;
          return $item;
        })
        ->toArray();
    } else {
      return tap(Auction::query()
        ->with([
          'userPlateCard.plateCard' => function ($query) {
            $query->select([
              'id',
              'player_id',
              'headshot_path',
              ...config('commonFields.player')
            ])->withTrashed();
          },
          'successAuctionBid' => function ($query) {
            $query->select([
              'id',
              'auction_id',
              'price',
              'user_id',
              'created_at',
            ])->latest();
          }
        ])
        ->where('user_id', $_userId)
        ->whereIn('status', [AuctionStatus::BIDDING, AuctionStatus::SOLD, AuctionStatus::CANCELED])
        ->latest()
        ->paginate($this->limit, ['*'], 'page', $_page)
      )
        ->map(function ($item) {
          $item->makeVisible('created_at');
          $item->sold_price = $item->successAuctionBid?->price;
          $season['id'] = $item->userPlateCard->draft_season_id;
          $season['name'] = $item->userPlateCard->draft_season_name;
          $season['league_id'] = $item->userPlateCard->draftSeason->league_id;
          $season['league']['id'] = $item->userPlateCard->draftSeason->league_id;
          $season['league']['league_code'] = $item->userPlateCard->draftSeason->league->league_code;
          $item->userPlateCard->draft_season = $season;
          unset($item->successAuctionBid);
          return $item;
        })
        ->toArray();
    }
  }
}
