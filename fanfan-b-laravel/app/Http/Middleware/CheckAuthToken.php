<?php

namespace App\Http\Middleware;

use App\Enums\ErrorDefine;
use App\Models\admin\UserRestriction;
use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use ReturnData;
use Symfony\Component\HttpFoundation\Response;

class CheckAuthToken implements AuthenticatesRequests
{
  /**
   * The authentication factory instance.
   *
   * @var \Illuminate\Contracts\Auth\Factory
   */
  protected $auth;

  protected $except = [];

  /**
   * Create a new middleware instance.
   *
   * @param  \Illuminate\Contracts\Auth\Factory  $auth
   * @return void
   */
  public function __construct(Auth $auth)
  {
    $this->auth = $auth;
  }

  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   *
   * @throws \Illuminate\Auth\AuthenticationException
   */
  public function handle($request, Closure $next)
  {
    if (in_array($request->path(), $this->except)) {
      return $next($request);
    }

    if (!$this->auth->guard('api')->check()) {
      return ReturnData::setError([
        ErrorDefine::FAIL_AUTHORIZATION,
        config('token.valid_failed')
      ])->send(Response::HTTP_UNAUTHORIZED);
    }

    $restricted = UserRestriction::where([
      ['user_id', $this->auth->guard('api')->user()->id],
      ['until_at', '>', now()]
    ])
      ->first();

    if (!is_null($restricted)) {
      $request->user()->token()->revoke();
      $reason = __getCodeInfo('R01')[$restricted->reason];
      $period = sprintf('%s ~ %s', $restricted->created_at, $restricted->until_at);
      $response = ['reason' => $reason, 'period' => $period];
      return ReturnData::setData($response)->setError([
        ErrorDefine::FAIL_AUTHORIZATION,
        __('auth.login.restricted')
      ])->send(Response::HTTP_FORBIDDEN);
    }

    $this->auth->shouldUse('api');

    return $next($request);
  }
}
