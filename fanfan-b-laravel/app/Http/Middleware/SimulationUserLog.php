<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Models\simulation\SimulationApplicant;
use App\Libraries\Traits\CommonTrait;
use App\Enums\ErrorDefine;
use Illuminate\Http\Response;
use ReturnData;

class SimulationUserLog
{
  use CommonTrait;

  protected $except = [];

  /**
   * Handle an incoming request.
   *
   * @param \Illuminate\Http\Request $request
   * @param \Closure $next
   * @return mixed
   *
   * @throws \Illuminate\Auth\AuthenticationException
   */
  public function handle(Request $request, Closure $next)
  {
    $path = explode('/', $request->path());
    $lastPath = end($path);

    if (in_array($request->path(), $this->except) || $lastPath === 'applicants') {
      return $next($request);
    }

    $applicantId = SimulationApplicant::where('user_id', $request->user()->id)->value('id');

    if (!$applicantId) {
      return ReturnData::setError([ErrorDefine::FAIL_AUTHORIZATION, 'Not a simulation user.'])->send(Response::HTTP_UNAUTHORIZED);
    }

    $userLogRedis = Redis::connection('user_log');
    $userLogRedis->set($this->getRedisCachingKey('simulation_user_log', '', $applicantId), json_encode(['date' => now()->toDateTimeString()]));

    return $next($request);
  }
}
