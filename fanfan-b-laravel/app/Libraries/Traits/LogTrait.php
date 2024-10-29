<?php

namespace App\Libraries\Traits;

use App\Enums\PointType;
use App\Models\user\User;
use App\Models\log\UserPointLog;
use Exception;
use Illuminate\Http\Response;
use Schema;
use Throwable;

trait LogTrait
{
  public function errorLog($_log): void
  {
    logger($_log);
  }

  public function recordLog(string $_model, array $_logRow)
  {
    try {
      Schema::connection('log')->disableForeignKeyConstraints();
      $_model::create($_logRow);
    } catch (\Exception $e) {
      logger($e->getMessage());
      throw $e;
    } finally {
      Schema::connection('log')->enableForeignKeyConstraints();
    }
  }

  public function plusUserPointWithLog(
    int $_amount,
    string $_pointType,
    string $_pointRefType = 'etc',
    string $_description = '',
    ?int $_userId = null
  ): void {
    try {
      Schema::connection('log')->disableForeignKeyConstraints();
      $userId = $_userId ?? $this->user->id;
      $userPointLog = new UserPointLog();
      $userPointLog->user_id = $userId;
      $userPointLog->point_type = $_pointType;
      $userPointLog->point_ref_type = $_pointRefType;
      $userPointLog->amount = $_amount;
      $userPointLog->description = $_description;
      $userPointLog->save();

      $user = User::with('userMeta')->find($userId);
      if ($_amount < 0 && ($user->{$_pointType} + $_amount < 0)) {
        throw new Exception('포인트가 모자라(임시 텍스트)', Response::HTTP_BAD_REQUEST);
      }
      if ($_pointType === PointType::FAN_POINT) {
        $user->userMeta->increment($_pointType, $_amount);
        $user->userMeta->save();
      } else {
        $user->increment($_pointType, $_amount);
        $user->save();
      }
    } catch (Throwable $th) {
      throw new Exception(
        $th->getMessage(),
        Response::HTTP_BAD_REQUEST
      );
    } finally {
      Schema::connection('log')->enableForeignKeyConstraints();
    }
  }

  public function minusUserPointWithLog(
    int $_amount,
    string $_pointType,
    string $_pointRefType = 'etc',
    string $_description = '',
    ?int $_userId = null
  ): void {
    $this->plusUserPointWithLog(
      -$_amount,
      $_pointType,
      $_pointRefType,
      $_description,
      $_userId,
    );
  }
}
