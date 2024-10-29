<?php

namespace App\Http\Middleware;

use App\Enums\ErrorDefine;
use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Http\Response;
use ReturnData;

class CheckAdminAuthToken implements AuthenticatesRequests
{
  /**
   * The authentication factory instance.
   *
   * @var \Illuminate\Contracts\Auth\Factory
   */
  protected $auth;

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
   */
  public function handle($request, Closure $next, string $guard)
  {
    if (!$this->auth->guard($guard)->getToken()) {
      return ReturnData::setError([ErrorDefine::NEED_TOKEN, '토큰이 없습니다.'])->send(Response::HTTP_UNAUTHORIZED);
    } else {
      if (!$this->auth->guard($guard)->check()) {
        return ReturnData::setError([ErrorDefine::EXPIRED_TOKEN, '인증 시간이 만료되었습니다.'])->send(Response::HTTP_UNAUTHORIZED);
      }
    }

    $this->auth->shouldUse($guard);

    // 조작된 토큰 유효성 검사해주기
    return $next($request);
  }
}
