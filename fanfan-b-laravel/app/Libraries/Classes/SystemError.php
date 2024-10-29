<?php

namespace App\Libraries\Classes;

use App\Models\SystemErrorLog;
use App\Services\FantasyData\FantasyDataService;
use DB;
use Illuminate\Database\Eloquent\Collection;
use Exception;
use Illuminate\Support\Facades\Artisan;

class SystemError
{
  public static function regSystemErrorLog(array $data)
  {
    $error = SystemErrorLog::where([
      'command' => $data['command'],
    ])
      ->whereDate('created_at', now()->toDateString())
      ->first();

    if (is_null($error)) {
      $error = new SystemErrorLog();
      $error->command = $data['command'];
      $error->count = 1;
    } else {
      // 처리 중인것은 냅두기
      if ($error->status === 'P') {
        return;
      }
      $error->status = 'N';
      $error->count = $error->count + 1;
    }
    $error->detail = $data['message'] ?? null;
    $error->path = $data['path'] ?? null;
    $error->save();
  }

  public static function autoRepair(Collection $errors)
  {
    if (empty($errors)) {
      return;
    }

    foreach ($errors as $error) {
      Artisan::call($error['command']);
      $error->status = 'Y';
      $error->save();
    }
  }
}