<?php

namespace App\Http\Controllers\ADMIN\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Op\RestrictListRequest;
use App\Http\Requests\Admin\User\LoginHistoryIpDetailReqeust;
use App\Http\Requests\Admin\User\UserCardGradeDetailRequest;
use App\Http\Requests\Admin\User\LoginHistoryRequest;
use App\Http\Requests\Admin\User\UserCardsRequest;
use App\Http\Requests\Admin\User\UserDetailRequest;
use App\Http\Requests\Admin\User\UserListRequest;
use App\Http\Requests\Admin\User\UserRestrictDeleteRequest;
use App\Http\Requests\Admin\User\UserRestrictStoreRequest;
use App\Http\Requests\Admin\User\UserSearchRequest;
use App\Libraries\Classes\Exception;
use App\Models\admin\UserRestriction;
use App\Models\user\User;
use App\Models\user\UserLoginLog;
use App\Models\user\UserPlateCard;
use App\Services\Admin\UserService;
use App\Services\Game\DraftService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use ReturnData;
use Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class UserController extends Controller
{
  protected UserService $userService;
  protected DraftService $draftService;
  protected int $limit = 20;

  public function __construct(UserService $_userService, DraftService $_draftService)
  {
    $this->userService = $_userService;
    $this->draftService = $_draftService;
  }

  public function list(UserListRequest $request)
  {
    $input = $request->only([
      'q',
      'status',
      'provider',
      'limit',
      'page',
    ]);

    try {
      $list = $this->userService->userList($input);
      return ReturnData::setData($list, $request)->send(Response::HTTP_OK);
    } catch (Throwable $th) {
      return ReturnData::setError($th->getMessage(), $request)->send($th->getCode());
    }
  }

  public function detail(UserDetailRequest $request)
  {
    $input = $request->only([
      'q',
      'mode',
      'sub_mode',
      'user_id',
      'page'
    ]);

    try {
      $userInfo = User::with([
        'restriction',
        'userReferral',
      ])->withoutGlobalScope('excludeWithdraw')->find($input['user_id']);

      $list = $userInfo->toArray();
      $list['latest_login'] = $userInfo->latestUserLoginLogs?->created_at->toDateTimeString();
      if ($socialInfo = $userInfo->linkedSocialAccounts) {
        $list['channel'] = $socialInfo->provider_name;
      } else {
        $list['channel'] = 'direct';
      }
      // object 가 필요한지.. 일단 주석처리
      // $list[$input['mode'] . '_history'] = $this->userService->detail($input);
      $list['user_referral_code'] = $userInfo->userReferral?->user_referral_code;
      $list = array_merge($list, $this->userService->detail($input));
      return ReturnData::setData($list, $request)->send(Response::HTTP_OK);
    } catch (Throwable $th) {
      return ReturnData::setError($th->getMessage(), $request)->send($th->getCode());
    }
  }

  public function me(Request $request)
  {
    try {
      return ReturnData::setData(array_merge($request->user()->toArray(), ['role' =>  $request->user()->roles()->value('id')]))->send(Response::HTTP_OK);
    } catch (Throwable $th) {
      return ReturnData::setError($th->getMessage(), $request)->send($th->getCode());
    }
  }

  public function loginHistory(LoginHistoryRequest $request)
  {
    $input = $request->only([
      'q',
      'start_date',
      'end_date',
      'page'
    ]);

    try {
      $todayCount = $this->userService->loginCountToday();
      $history = $this->userService->loginHistory($input);
      return ReturnData::setData(array_merge(['today_count' => $todayCount], $history), $request)->send(Response::HTTP_OK);
    } catch (Throwable $th) {
      return ReturnData::setError($th->getMessage(), $request)->send($th->getCode());
    }
  }

  public function loginHistoryIpDetail(LoginHistoryIpDetailReqeust $request)
  {
    $filter = $request->only([
      'ip_address',
      'page',
    ]);

    $list = UserLoginLog::with(['user' => function ($query) {
      $query->withoutGlobalScope('excludeWithdraw')->select(['id', 'name', 'email', 'status']);
    }])
      ->when($filter['ip_address'], function ($whenIp) use ($filter) {
        $whenIp->where('ip_address', $filter['ip_address']);
      })
      ->latest()
      ->paginate($this->limit, ['*'], 'page', $filter['page'])
      ->toArray();

    foreach ($list['data'] as &$log) {
      $log['browser'] = __getBrowserName($log['agent']);
      unset($log['agent']);
    }


    return ReturnData::setData(__setPaginateData($list, []), $request)->send(Response::HTTP_OK);
  }

  public function userCards(UserCardsRequest $_request, $_status)
  {
    dd($_status);
  }

  public function userCardGradeDetail(UserCardGradeDetailRequest $_request)
  {
    try {
      $userGradeCard = UserPlateCard::query()
        ->withoutGlobalScope('excludeBurned')
        ->with([
          'user' => function ($query) {
            $query->withoutGlobalScope('excludeWithdraw')->select(['id', 'name', 'status']);
          },
          'plateCardWithTrashed:id,player_id,headshot_path,deleted_at,' . implode(
            ',',
            config('commonFields.player'),
          ),
          'draftSelection' => function ($query) {
            $query->with([
              'schedule:id,home_team_id,away_team_id,score_home,score_away,started_at',
              'schedule.home:' . implode(',', config('commonFields.team')),
              'schedule.away:' . implode(',', config('commonFields.team')),
            ])
              ->has('schedule');
          },
          'draftSeason:id,name,league_id',
          'draftSeason.league:id,league_code',
          'auction',
          'draftOrder:user_plate_card_id,upgrade_point,upgrade_point_type,order_status,user_id',
          'draftOrder.user' => function ($query) {
            $query->withoutGlobalScope('excludeWithdraw')->select(['id', 'name', 'status']);
          },
        ])
        ->find($_request['user_plate_card_id']);

      $userGradeCard->latest_auction_id = $userGradeCard->auction?->id;
      unset($userGradeCard->auction);
      $userGradeCard->is_valid = is_null($userGradeCard->plateCardWithTrashed->deleted_at);
      $userGradeCard->plate_card = $userGradeCard->plateCardWithTrashed;
      unset($userGradeCard->plateCardWithTrashed);

      $userGradeCard['skills'] = $this->draftService->getDraftSelections($userGradeCard->draftSelection, true);
    } catch (Throwable $th) {
      return ReturnData::setError($th->getMessage(), $_request)->send($th->getCode());
    }
    return ReturnData::setData($userGradeCard, $_request)->send(Response::HTTP_OK);
  }

  public function restrictList(RestrictListRequest $request)
  {
    $filter = $request->only([
      'q',
      'page',
      'per_page',
    ]);

    $this->limit = $filter['per_page'];

    $reason = __getCodeInfo('R01');
    $period = __getCodeInfo('R02');

    $list = tap(UserRestriction::with([
      'user' => function ($query) {
        $query->withoutGlobalScope('excludeWithdraw')->select(['id', 'name', 'email', 'status']);
      },
      'user.latestUserLoginLogs:user_id,created_at'
      ])
      ->when($filter['q'], function ($whenQuery, $q) use ($filter) {
        $whenQuery->whereHas('user', function ($query) use ($q) {
          $query->where('email', $q)
            ->orWhere('name', $q);
        });
      })
      ->withTrashed()
      ->latest()
      ->paginate($this->limit, ['*'], 'page', $filter['page'])
    )
      ->map(function ($item) use ($reason, $period) {
        $item->reason = $reason[$item->reason];
        $item->period = $period[$item->period];
        $item->latest_login = $item->user->latestUserLoginLogs->created_at->format('Y-m-d H:i:s');
        unset($item->user->latestUserLoginLogs);
      })
      ->toArray();

    $options['reason'] = __getCodeInfo('R01', false);
    $options['period'] = __getCodeInfo('R02', false);

    return ReturnData::setData(__setPaginateData($list, [], compact('options')), $request)->send(Response::HTTP_OK);
  }

  public function restrictStore(UserRestrictStoreRequest $request)
  {
    $input = $request->only([
      'user_id',
      'reason',
      'period',
    ]);

    try {
      $alreadyRestricted = UserRestriction::where('until_at', '>=', now())
        ->where('user_id', $input['user_id'])
        ->exists();

      if ($alreadyRestricted) {
        throw new Exception('Already Restricted.');
      }

      $userRestriction = new UserRestriction;
      $userRestriction->admin_id = $request->user()->id;
      $userRestriction->user_id = $input['user_id'];
      $userRestriction->reason = $input['reason'];
      $userRestriction->period = $input['period'];

      if (Str::endsWith($input['period'], 'd')) {
        $userRestriction->until_at = now()->addDays((int) $input['period'])->subSecond();
      } else if (Str::endsWith($input['period'], 'y')) {
        $userRestriction->until_at = now()->addYear()->subSecond();
      }

      $userRestriction->save();

    } catch (Throwable $th) {
      return ReturnData::setError($th->getMessage(), $request)->send($th->getCode());
    }
    return ReturnData::send(Response::HTTP_OK);

  }

  public function restrictDelete(UserRestrictDeleteRequest $request)
  {
    $input = $request->only('user_restriction_id');

    $userRestriction = UserRestriction::find($input['user_restriction_id']);
    $userRestriction->admin_id = $request->user()->id;
    $userRestriction->save();
    $userRestriction->delete();

    return ReturnData::send(Response::HTTP_OK);
  }

  public function searchForRestrict(UserSearchRequest $request)
  {
    $filter = $request->only('q');

    $result = User::withoutGlobalScope('excludeWithdraw')
      ->with('latestUserLoginLogs:user_id,created_at')
      ->select([
        'id',
        'email',
        'name',
        'status'
      ])
      ->where('email', $filter['q'])
      ->orWhere('name', $filter['q'])
      ->first();

    if (!is_null($result)) {
      $result->lastest_login = $result->latestUserLoginLogs->created_at->format('Y-m-d H:i:s');
      unset($result->latestUserLoginLogs);
    }

    return ReturnData::setData($result, $request)->send(Response::HTTP_OK);
  }
}
