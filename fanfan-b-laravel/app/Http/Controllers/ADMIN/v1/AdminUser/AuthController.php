<?php

namespace App\Http\Controllers\ADMIN\v1\AdminUser;

use App\Enums\ErrorDefine;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AuthLoginRequest;
use App\Models\admin\Admin;
use DB;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use ReturnData;
use Throwable;

class AuthController extends Controller
{
  use AuthenticatesUsers;

  public function login(AuthLoginRequest $request): object
  {
    if ($this->hasTooManyLoginAttempts($request)) {
      $this->fireLockoutEvent($request);
      $seconds = $this->limiter()->availableIn(
        $this->throttleKey($request)
      );

      $msg = "입력 횟수 5회 이상 초과로 로그인을 할 수 없습니다. {$seconds}초 후에 다시 시도해주세요.";
      return ReturnData::setError([ErrorDefine::TOO_MANY_REQUEST, $msg])->send(Response::HTTP_TOO_MANY_REQUESTS);
    }

    $userIdCheck = Optional(Admin::where('login_id', $request->get('login_id'))->first())->id;
    if (empty($userIdCheck)) {
      $this->incrementLoginAttempts($request);
      return ReturnData::setError([ErrorDefine::FAIL_AUTHORIZATION, '존재하지 않는 아이디입니다.'])->send(Response::HTTP_UNAUTHORIZED);
    }

    $token = Auth::guard('admin')->attempt($request->only('login_id', 'password'));
    if (empty($token)) {
      $this->incrementLoginAttempts($request);
      return ReturnData::setError([ErrorDefine::FAIL_AUTHORIZATION, '잘못된 비밀번호입니다.'])->send(Response::HTTP_UNAUTHORIZED);
    }

    DB::beginTransaction();
    try {
      $tokenSignature = explode('.', $token)[2];
      Auth::guard('admin')->user()->update([
        'access_token' => $tokenSignature
      ]);

      DB::commit();
    } catch (Throwable $th) {
      DB::rollBack();
      return ReturnData::setError([ErrorDefine::INTERNAL_SERVER_ERROR, '서버 오류입니다.'])->send(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return ReturnData::setData(
      [
        'info' => array_merge(Auth::guard('admin')->user()->toArray(), ['role' =>  Auth::guard('admin')->user()->roles()->value('id')]),
        'access_token' => $token
      ],
      $request
    )->send(Response::HTTP_OK);
  }

  public function logout(): Object
  {
    if (auth()->check()) {
      auth()->user()->update([
        'access_token' => NULL
      ]);

      auth()->logout();
    }

    return ReturnData::send(Response::HTTP_OK);
  }
}
