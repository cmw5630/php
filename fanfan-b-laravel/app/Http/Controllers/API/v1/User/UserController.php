<?php

namespace App\Http\Controllers\API\v1\User;

use App\Enums\ErrorDefine;
use App\Enums\GradeCardLockStatus;
use App\Enums\Opta\Card\PlateCardStatus;
use App\Enums\QuestCollectionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\SignupRequest;
use App\Http\Requests\Api\User\UserRedeemRequest;
use App\Http\Requests\Api\User\UserValidationRequest;
use App\Http\Requests\Api\User\UserWithdrawRequest;
use App\Interfaces\UserConstantInterface;
use App\Libraries\Classes\QuestRecorder;
use App\Libraries\Classes\SendAction;
use App\Libraries\Traits\AuthTrait;
use App\Models\admin\Redeem;
use App\Models\data\League;
use App\Models\game\Game;
use App\Models\game\GameJoin;
use App\Models\log\EventPointLog;
use App\Models\user\UserPlateCard;
use App\Models\user\UserRedeem;
use App\Services\User\UserService;
use Arr;
use DB;
use Exception;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use ReturnData;
use Str;
use Symfony\Component\HttpFoundation\Response;
use Validator;


class UserController extends Controller implements UserConstantInterface
{
  use AuthTrait, AuthenticatesUsers;
  protected UserService $userService;

  public function __construct(UserService $_userService)
  {
    $this->userService = $_userService;
  }

  public function me(Request $request): object
  {
    $userId = $request->user()->id;
    (new QuestRecorder())->act(QuestCollectionType::LOGIN, $userId);
    $result = $request->user()?->load([
      'userMeta.favoriteTeam:id,code,short_name',
      'userReferral.invite',
      'linkedSocialAccounts'
    ]);

    if (!is_null($result->linkedSocialAccounts)) {
      $result->email = Str::of($result->linkedSocialAccounts->provider_name)
        ->substr(0, 1)
        ->ucfirst()
        ->append($result->linkedSocialAccounts->provider_id);
    }
    $result->my_code = $result->userReferral->user_referral_code;
    $result->referral_code = $result->userReferral->invite?->user_referral_code;
    $result->event_point = EventPointLog::where('user_id', $userId)
      ->selectRaw('CAST(SUM(point) AS UNSIGNED) AS total_point')->value('total_point');

    $result->most_league = null;
    $leagueCnt = [];
    Game::whereHas('gameJoin', function ($query) use ($userId) {
      $query->where('user_id', $userId);
    })->with(
      'season:id,league_id',
      'season.league'
    )
      ->has('season')
      ->get()
      ->groupBy('season.league_id')
      ->map(function ($group, $key) use (&$leagueCnt) {
        $leagueCnt[] = ['id' => $key, 'count' => $group->count()];
      });
    if (count($leagueCnt) > 0) {
      $leagueId = __sortByKey($leagueCnt, 'count')[0]['id'];
      $league = League::select(['id', 'league_code'])->find($leagueId);
      $result->most_league = $league;
    }

    $result->win_rate = GameJoin::where('user_id', $userId)
      ->whereHas('game', function ($query) {
        $query->where([
          ['completed_at', '!=', null],
          ['rewarded_at', '!=', null]
        ]);
      })->selectRaw('CAST(COUNT(IF(REWARD>0, 1,NULL)) / COUNT(*) AS float) AS win_rate')->value('win_rate');

    unset($result->userReferral);

    return ReturnData::setData($result, $request)->send(200);
  }

  public function signup(SignupRequest $request): object
  {
    // 회원가입
    $input = $request->only([
      'email',
      'name',
      'password',
      'password_confirm',
      'referral_code',
      'nation',
      'favorite_team',
      'optional_agree',
    ]);

    // if ($input['mode'] === 'validation') {
    //   return ReturnData::send(Response::HTTP_OK);
    // }

    $ip = Str::before($request->header('X_FORWARDED_FOR'), ',') ?? $request->ip();
    $input['country'] = 'unknown';

    // Todo : 변경된 ip ?
    if (!(Str::startsWith($ip, '127.') || Str::startsWith($ip, '192.') || Str::startsWith($ip, '10.'))) {
      $urls = ['https://world.b2ggames.net/api/ip/' . $ip];
      $sendAction = SendAction::getInstance();
      $result = $sendAction->send('GET', $urls);

      foreach ($result as $value) {
        $input['country'] = $value['data']['country']['iso_code'];
      }
    }

    DB::beginTransaction();
    try {
      $userId = $this->userService->signup($input);
      $this->userService->setUserMetaInfo($userId, $input);
    } catch (Exception $e) {
      DB::rollback();
      $this->errorLog($e->getMessage());
      return ReturnData::setError([ErrorDefine::INTERNAL_SERVER_ERROR, __('common.internal_server_error')])->send(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    DB::commit();
    return ReturnData::send(Response::HTTP_OK);
  }

  public function validation(UserValidationRequest $request, $_proc = false)
  {
    $input = $request->all();
    $rules = $request->checkRules();
    $message = $request->checkMessages();

    // 비밀번호 수정
    if ($input['mode'] === 'modify_password' || $input['mode'] === 'check_password') {
      $rules = [
        ...$rules,
        'current_password' => [
          'required',
          function ($key, $value, $fail) {
            if (!$this->userService->checkPassword($value)) {
              $fail(__('validation.password.check_current', ['attribute' => 'current password']));
            }
          }
        ]
      ];
    }
    // 임시 비밀번호 발급되지 않은 상태에서 초기화 접근
    if ($input['mode'] === 'reset_password' && empty($request->user?->temp_password)) {
      return ReturnData::setError('not reset password user')->send(Response::HTTP_BAD_REQUEST);
    }

    // foreach ($input as $key => $val) {
    //   if ($key === 'password' || $key === 'password_confirm') {
    //     if (!is_null($passwordKeys)) {
    //       continue;
    //     }
    //     $passwordKeys = [];
    //     if ($input['mode'] === 'signup') {
    //       $passwordKeys[] = 'password';
    //     } else {
    //       $passwordKeys[] = 'new_password';
    //     }
    //     // 임시 비번 발급 상태가 아닐경우 현재 패스워드 체크
    //
    //     foreach ($passwordKeys as $key) {
    //       $validation[$key] = $rules[$key];
    //     }
    //
    //     // 임시 비번 상태가 아니면 현재 비밀번호 검증 rule 넣음
    //     if ($input['mode'] === 'modify_password') {
    //       $validation['current_password'] = $rules['current_password'];
    //     }
    //     continue;
    //   }
    //   if (isset($rules[$key])) {
    //     $validation[$key] = $rules[$key];
    //   }
    // }

    $validator = Validator::make($input, $rules, $message, $request->attributes());

    if (count($errors = $validator->errors()) > 0) {
      return ReturnData::setError([
        ErrorDefine::VALIDATION_ERROR,
        $errors
      ])->send(Response::HTTP_BAD_REQUEST);
    }

    if (!$_proc) {
      return ReturnData::send(200);
    }
  }

  public function modifyUserInfo(UserValidationRequest $request)
  {
    $input = $request->except('mode');
    $errors = $this->validation($request, true);
    if ($errors) {
      return $errors;
    }

    // Update
    DB::beginTransaction();
    try {
      $user = $this->userService->getUser();
      unset($input['current_password']);

      foreach ($input as $key => $item) {
        // if (is_null($item)) {
        //   continue;
        // }
        if ($key === 'photo') {
          continue;
        }

        if ($key === 'name') {
          if ($user->name_change) {
            throw new Exception(__('user.nickname_already_change'));
          } else {
            $user->name_change = true;
          }
        }

        // if ($key === 'photo') {
        //   $storage = Storage::disk('dev');
        //   $oldPhoto = $user->userMeta->photo_path;
        //   $storage->move('')
        //   $user->userMeta->photo_path = $item;
        //   dd($item);
        //   continue;
        // }
        $user->{$key} = $key === 'password' ? bcrypt($item) : $item;
      }

      $user->save();

      // game_join.user_name update
      $gameJoins = GameJoin::where('user_id', $user->id);
      if ($gameJoins->exists()) {
        $gameJoins->update(['user_name' => $user->name]);
      }

      // user_rank 삭제
      $prefix = Str::lower(env('APP_NAME') . '_' . 'database_');
      $redisRankArr = Redis::keys('user_rank_*');
      foreach ($redisRankArr as $redisRank) {
        $redisKeyName = Str::replace($prefix, '', $redisRank);
        Redis::del($redisKeyName);
      }

      if (Arr::exists($input, 'photo')) {
        if (!is_null($input['photo'])) {
          $this->userService->uploadPhoto($user->userMeta, $input['photo']);
        } else {
          if ($this->userService->deletePhotoFile($user->userMeta->photo_path)) {
            $user->userMeta->photo_path = null;
            $user->userMeta->save();
          }
        }
      }

      DB::commit();

    } catch (Exception $e) {
      // 업로드는 했는데 save 실패할 경우 대응
      DB::rollBack();
      $this->errorLog($e->getMessage());
      return ReturnData::setError([ErrorDefine::INTERNAL_SERVER_ERROR, ($e->getMessage() ?? __('common.internal_server_error'))])->send(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return ReturnData::send(200);
  }

  public function withdraw(UserWithdrawRequest $request)
  {
    $input = $request->only(['reason']);
    try {
      if (UserPlateCard::where('user_id', $request->user()->id)
        ->where(function ($query) {
          $query->where('status', PlateCardStatus::UPGRADING)
            ->orWhere('lock_status', GradeCardLockStatus::MARKET);
        })->exists()
      ) {
        return ReturnData::setData(['success' => false])->send(Response::HTTP_OK);
      }
      $this->userService->withdraw($input);
      return ReturnData::setData(['success' => true])->send(Response::HTTP_OK);
    } catch (Exception $e) {
      $this->errorLog($e->getMessage());
      return ReturnData::setError([ErrorDefine::INTERNAL_SERVER_ERROR, __('common.internal_server_error')])->send(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function redeemRegister(UserRedeemRequest $request)
  {
    $userId = $request->user()->id;
    $input = $request->only(['redeem_code']);
    try {
      $redeem = Redeem::where('redeem_code', $input['redeem_code'])->first();

      $userRedeem = new UserRedeem();
      $userRedeem->user_id = $userId;
      $userRedeem->redeem_id = $redeem->id;
      $userRedeem->status = $redeem->status;
      $userRedeem->save();

      return ReturnData::send(Response::HTTP_OK);
    }catch (Exception $e){
      $this->errorLog($e->getMessage());
      return ReturnData::setError([ErrorDefine::INTERNAL_SERVER_ERROR, __('common.internal_server_error')])->send(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function welcomePack(Request $request)
  {
    try {
      $userId = $request->user()->id;
      $result = $this->userService->getWelcomePackCards($userId);

      return ReturnData::setData($result)->send(200);

    } catch (Exception $e) {
      $this->errorLog($e->getMessage());
      return ReturnData::setError([ErrorDefine::INTERNAL_SERVER_ERROR, __('common.internal_server_error')])->send(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function welcomePackOpenCheck(Request $request)
  {
    DB::beginTransaction();
    try {
      $user = $request->user();

      if (!$user || !$user->userMeta) {
        throw new Exception('User or UserMeta not found');
      }

      $user->userMeta->is_pack_open = true;
      $user->userMeta->save();

      DB::commit();
    } catch (Exception $e) {
      DB::rollBack();

      return ReturnData::setError($e->getMessage(), $request)->send(Response::HTTP_BAD_REQUEST);
    }

    return ReturnData::send(Response::HTTP_OK);
  }
}
