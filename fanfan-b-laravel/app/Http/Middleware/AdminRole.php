<?php

namespace App\Http\Middleware;

use App\Enums\ErrorDefine;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use ReturnData;

class AdminRole
{
  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return mixed
   */
  public function handle(Request $request, Closure $next)
  {
    if (!auth()->guard('admin')->user()->can('admin_access')) {
      return ReturnData::setError([ErrorDefine::FAIL_AUTHORIZATION, '권한이 유효하지 않습니다.'])->send(Response::HTTP_UNAUTHORIZED);
    }

    return $next($request);
  }
}
