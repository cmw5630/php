<?php

namespace App\Http\Controllers\API\v1\User;

use App\Enums\ErrorDefine;
use App\Enums\QuestCollectionType;
use App\Interfaces\UserConstantInterface;
use App\Libraries\Classes\Exception;
use App\Libraries\Classes\QuestRecorder;
use App\Libraries\Traits\LogTrait;
use App\Models\admin\UserRestriction;
use App\Models\LoginBlockedIp;
use App\Models\user\User;
use App\Models\user\UserLoginLog;
use Auth;
use Carbon\Carbon;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Laravel\Socialite\Facades\Socialite;
use League\OAuth2\Server\Exception\OAuthServerException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;
use ReturnData;
use Laravel\Passport\Http\Controllers\AccessTokenController as BaseAccessTokenController;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\Response;

class AccessTokenController extends BaseAccessTokenController implements UserConstantInterface
{
  use AuthenticatesUsers, LogTrait;

  protected function createRequest(Request $symfonyRequest): ServerRequestInterface
  {
    if (class_exists(PsrHttpFactory::class)) {
      $psr17Factory = new Psr17Factory();

      return (new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory))
        ->createRequest($symfonyRequest);
    }
  }
  /**
   * @OA\Post(
   *   path="/api/v1/oauth/token",
   *   operationId="getUserToken",
   *   tags={"Auth"},
   *   summary="사용자 토큰 발급",
   *   description="사용자 토큰 발급",
   *      @OA\RequestBody(
   *        @OA\JsonContent(),
   *        @OA\MediaType(
   *            mediaType="multipart/form-data",
   *            @OA\Schema(
   *               type="object",
   *            @OA\Property(property="grant_type", type="string", example="password"),
   *            @OA\Property(property="client_id", type="string", example="1"),
   *            @OA\Property(property="client_secret", type="string", example="yZbPeMmd0VfnhrFZY4KDGYiMBtLymfyFzgmHNan2"),
   *            @OA\Property(property="username", type="string", example="test@b2ggames.com"),
   *            @OA\Property(property="password", type="string", example="1234"),
   *            ),
   *        ),
   *      ),
   *
   *   @OA\Response(
   *        response=200, description="Success",
   *        @OA\JsonContent(
   *           @OA\Property(property="response", type="integer", example="200"),
   *           @OA\Property(property="data",type="object")
   *        )
   *     )
   *  )
   */
  public function login(Request $request)
  {
    try {
      $input = $request->all();           //get username (default is :email)
      $request->merge([
        'client_id' => env('CLIENT_ID', 1),
        'client_secret' => env('CLIENT_SECRET'),
      ]);

      if ($input['grant_type'] === 'social') {
        // 소셜 로그인
        if (empty($input['provider'])) {
          throw new Exception(__('auth.login.failed'), Response::HTTP_BAD_REQUEST);
        }

        switch ($provider = $input['provider']) {
          case 'facebook':
            $fieldName = 'id';
            break;
          default:
            $fieldName = 'email';
            break;
        }

        $data = $this->getIssueTokenData($request);
        $socialUser = Socialite::driver($provider)->userFromToken($request->input('access_token'));
        $fieldValue = $socialUser->{$fieldName};

        if (isset($data['error'])) {
          throw new OAuthServerException(
            'The user credentials were incorrect.',
            6,
            'invalid_credentials',
            401
          );
        }

        //add access token to user
        $response = [];

        $userQuery = User::query();

        if ($provider === 'facebook') {
          $userQuery->whereHas('linkedSocialAccounts', function ($subquery) use ($provider, $fieldValue) {
            $subquery->where('provider_name', $provider)
              ->where('provider_id', $fieldValue);
          });
        } else {
          $userQuery->where($fieldName, $fieldValue);
        }

        $user = $userQuery->first();

        $response['user'] = collect($user);

        $response['access_token'] = $data['access_token'];
        $response['refresh_token'] = $data['refresh_token'];
      } else if ($input['grant_type'] === 'refresh_token') {
        // 토큰 리프레시
        $credentials = $request->only('email', 'refresh_token');

        // 일반 로그인 계정정보
        $user = User::where('email', $credentials['email'])
          ->first();

        if (is_null($user)) {
          throw new Exception(__('auth.login.failed'), Response::HTTP_BAD_REQUEST);
        }

        $response = $this->signInSuccessEvent($request, $user);
      } else {
        // 일반 로그인

        //verify user credentials
        $credentials = $request->only('email', 'password');

        // 일반 로그인 계정정보
        $user = User::where('email', $credentials['email'])
          ->has('linkedSocialAccounts', 0)
          ->first();

        if (!is_null($user)) {
          $restricted = UserRestriction::where([
            ['user_id', $user->id],
            ['until_at', '>', now()]
          ])
            ->first();

          if (!is_null($restricted)) {
            $reason = __getCodeInfo('R01')[$restricted->reason];
            $period = sprintf('%s ~ %s', $restricted->created_at, $restricted->until_at);
            $response = ['reason' => $reason, 'period' => $period];
            throw new Exception(
              __('auth.login.restricted'),
              Response::HTTP_FORBIDDEN
            );
          }
        }
        // 임시 비밀번호로 로그인 할 때의 만료일시 조건
        if (!empty($user->temp_password) && Carbon::parse($user->temp_password_expired_at)->diffInSeconds(
          now(),
          false
        ) > 0) {
          throw new Exception(
            __('auth.login.failed'),
            Response::HTTP_FORBIDDEN
          );
        }

        // 로그인
        // 접근제한된 IP인지 확인
        $blockedIpData = LoginBlockedIp::whereIpAddress($request->getClientIp())->first();
        $redisKey = 'login_failure_' . $request->getClientIp();
        $redisFailedData = Redis::get($redisKey) ? json_decode(Redis::get($redisKey), true) : null;

        if (Carbon::parse($blockedIpData?->until)->diffInSeconds(now(), false) < 0) {
          return ReturnData::setError([
            ErrorDefine::FAIL_AUTHORIZATION,
            __('auth.login.failed_limit')
          ])->send(Response::HTTP_UNAUTHORIZED);
        }
        // todo: 횟수 증가 데이터를 언제까지 갖고 갈지.. 한시간? 하루?
        // else if (!empty($blockedIpData) && is_null($redisFailedData)) {
        //   $blockedIpData->delete();
        // }

        if (Auth::guard('web')->attempt($credentials)) {
          $blockedIpData?->delete();
          //$this->clearLoginAttempts($request);
          $response = $this->signInSuccessEvent($request, $user);
          if (!empty($user->temp_password)) {
            $user->temp_password_expired_at = now();
            $user->save();
          }
        } else {
          // $this->incrementLoginAttempts($request);
          // 최초 실패한 이후 10분동안 5회 실패시 로그인 차단
          $expire = config('auth.signin_limit_seconds');
          if (is_null($redisFailedData)) {
            $attemptCount = 1;
          } else {
            $attemptCount = $redisFailedData['attempt_count'] + 1;
          }

          if (!is_null($redisFailedData)) {
            $expire = Redis::command('ttl', [$redisKey]);
          }
          $this->signInFailedEvent($attemptCount, $blockedIpData, $request->getClientIp(), $expire);

          if ($attemptCount >= self::SIGNIN_FAILED_LIMIT) {
            return ReturnData::setError([ErrorDefine::FAIL_AUTHORIZATION, __('auth.login.failed_limit')])->send(Response::HTTP_UNAUTHORIZED);
          }

          return ReturnData::setError([ErrorDefine::FAIL_AUTHORIZATION,  __('auth.login.failed')])->send(Response::HTTP_UNAUTHORIZED);
        }
      }

      UserLoginLog::create([
        'user_id' => $user->id,
        'ip_address' => $request->getClientIp(),
        'agent' => $request->userAgent(),
      ]);


      // quest
      (new QuestRecorder())->act(QuestCollectionType::LOGIN, $user->id);

      return ReturnData::setData($response)->send(200);
    } catch (ModelNotFoundException | OAuthServerException $e) { // email notfound or password not correct..token not granted
      $this->errorLog($e->getMessage());
      return ReturnData::setError([ErrorDefine::FAIL_AUTHORIZATION, __('common.internal_server_error')])->send(Response::HTTP_UNAUTHORIZED);
    } catch (RequestException $e) { // social failed
      $this->errorLog($e->getMessage());
      return ReturnData::setError([ErrorDefine::FAIL_AUTHORIZATION, __('common.internal_server_error')])->send(Response::HTTP_UNAUTHORIZED);
    } catch (Exception $e) {
      $this->errorLog($e->getMessage());
      return ReturnData::setData($response ?? null)->setError([
        ErrorDefine::FAIL_AUTHORIZATION,
        $e->getMessage() ?? __('common.internal_server_error')
      ])->send($e->getCode());
    }
  }

  private function signInSuccessEvent(Request $request, User $user)
  {
    $request->request->add(['username' => $request->email]);

    $data = $this->getIssueTokenData($request);

    if (isset($data['error'])) {
      throw new OAuthServerException(
        'The user credentials were incorrect.',
        6,
        'invalid_credentials',
        401
      );
    }

    //add access token to user
    $response = [];
    $response['user'] = collect($user);
    $response['access_token'] = $data['access_token'];
    $response['refresh_token'] = $data['refresh_token'];

    return $response;
  }

  private function signInFailedEvent($_attemptCount, $_blockedIpData, $_ip, $_expire)
  {
    $blockMinutesMap = config('auth.signin_expire_map');
    // 실패한 로그인 횟수
    $redisKey = 'login_failure_' . $_ip;
    if ($_attemptCount >= 5) {
      Redis::del($redisKey);
      $_attemptCount = 0;
      if (!is_null($_blockedIpData)) {
        if (Carbon::parse($_blockedIpData->until)->addSeconds($_expire)->diffInSeconds(
          now(),
          false
        ) < 0) {
          $_blockedIpData->increment('count');
          $addMin = $blockMinutesMap[min(($_blockedIpData->count - 1),
            count($blockMinutesMap) - 1
          )];
          $_blockedIpData->until = now()->addMinutes($addMin);
        } else {
          $_blockedIpData->count = 1;
          $_blockedIpData->until = now()->addMinute();
        }
      } else {
        $_blockedIpData = new LoginBlockedIp();
        $_blockedIpData->ip_address = $_ip;
        $_blockedIpData->count = 1;
        $_blockedIpData->until = now()->addMinute();
      }
      $_blockedIpData->save();
    } else {
      Redis::set($redisKey, json_encode(['attempt_count' => $_attemptCount]), 'EX', $_expire);
    }
    return $_attemptCount;
  }

  protected function getIssueTokenData(Request $request)
  {
    $psrRequest = $this->createRequest($request);
    $tokenResponse = parent::issueToken($psrRequest);

    //convert response to json string
    $content = $tokenResponse->getContent();

    //convert json to array
    return json_decode($content, true);
  }

  public function revoke(Request $request)
  {
    $request->user()->token()->revoke();
    return ReturnData::send(Response::HTTP_OK);
  }
}
