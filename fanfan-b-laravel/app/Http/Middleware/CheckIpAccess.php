<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use Closure;
use Illuminate\Http\Request;
use ReturnData;
use Symfony\Component\HttpFoundation\Response;

class CheckIpAccess
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

    $restricted = BlockedIp::where('ip_address', $request->getClientIp())
      ->exists();

    if ($restricted) {
      if ($request->user()) {
        $request->user()->token()->revoke();
      }
      return ReturnData::setError(null)->send(Response::HTTP_NOT_ACCEPTABLE);
    }

    return $next($request);
  }
}
