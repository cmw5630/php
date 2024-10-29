<?php

namespace App\Http\Controllers\ADMIN\v1\AdminUser;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AccountEditRequest;
use App\Http\Requests\Admin\Auth\AccountStoreRequest;
use App\Models\admin\Admin;
use App\Services\Admin\AccountService;
use DB;
use Exception;
use Illuminate\Http\Request;
use ReturnData;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AccountController extends Controller
{
  protected AccountService $accountService;

  public function __construct(AccountService $_accountService)
  {
    $this->accountService = $_accountService;
  }

  public function main(Request $request): object
  {
    try {
      $account = $this->accountService->main();
      $levelList = Role::select('id', 'name')->orderBy('id')->get()->toArray();
    } catch (Throwable $th) {
      return ReturnData::setError($th->getMessage(), $request)->send($th->getCode());
    }

    return ReturnData::setData(compact('account', 'levelList'), $request)->send(Response::HTTP_OK);
  }

  public function store(AccountStoreRequest $request): object
  {
    $input = $request->only([
      'login_id',
      'name',
      'password',
      'role'
    ]);

    try {
      $this->accountService->store($input);
    } catch (Throwable $th) {
      return ReturnData::setError($th->getMessage(), $request)->send($th->getCode());
    }

    return ReturnData::send(Response::HTTP_OK);
  }

  public function destroy(Request $request, int $_userId): object
  {
    DB::beginTransaction();
    try {
      $this->accountService->destroy($_userId);
      DB::commit();
    } catch (Throwable $th) {
      DB::rollBack();
      return ReturnData::setError($th->getMessage(), $request)->send($th->getCode());
    }

    return ReturnData::setData([], $request)->send(Response::HTTP_OK);
  }

  public function edit(AccountEditRequest $request): object
  {
    $input = $request->only([
      'user_id',
      'password',
      'role'
    ]);

    DB::beginTransaction();
    try {
      $this->accountService->edit($input);
      DB::commit();
    } catch (Throwable $th) {
      DB::rollBack();
      return ReturnData::setError($th->getMessage(), $request)->send($th->getCode());
    }

    return ReturnData::setData([], $request)->send(Response::HTTP_OK);
  }

  public function checkId(Request $request): object
  {
    $input = $request->only([
      'login_id',
    ]);

    try {
      $user = Admin::where('login_id', $input['login_id'])->exists();

      if ($user) {
        throw new Exception('중복된 아이디입니다.', Response::HTTP_BAD_REQUEST);
      }
    } catch (Throwable $th) {
      return ReturnData::setError($th->getMessage(), $request)->send($th->getCode());
    }

    return ReturnData::send(Response::HTTP_OK);
  }

  public function checkNickname(Request $request): object
  {
    $input = $request->only([
      'nickname',
    ]);

    try {
      $nickname = Admin::where('nickname', $input['nickname'])->exists();

      if ($nickname) {
        throw new Exception('중복된 닉네임입니다..', Response::HTTP_BAD_REQUEST);
      }
    } catch (Throwable $th) {
      return ReturnData::setError($th->getMessage(), $request)->send($th->getCode());
    }

    return ReturnData::send(Response::HTTP_OK);
  }
}
