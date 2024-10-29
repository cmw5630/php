<?php

namespace App\Console\Commands\DataControll;

use App\Models\data\OptaPlayerDailyStat;
use App\Models\game\GameLineup;
use App\Models\game\PlateCard;
use App\Models\game\PlayerDailyStat;
use App\Models\user\UserPlateCard;
use DB;
use Exception;

class GameJoinUpdator
{
  protected $scheduleId;

  public function __construct($_scheduleId)
  {
    $this->scheduleId = $_scheduleId;
  }

  public function update()
  {
    DB::beginTransaction();
    try {

      $fantasyPoints = PlayerDailyStat::where('schedule_id', $this->scheduleId)
        ->get(['schedule_id', 'player_id', 'fantasy_point'])->toArray();
      array_column($fantasyPoints, 'player_id');


      dd(PlayerDailyStat::where('schedule_id', $this->scheduleId)->get()->keyBy('player_id')->toArray());
      logger('start currentMeta update');
      DB::commit();
      logger('currentMeta update 성공');
    } catch (Exception $e) {
      DB::rollBack();
      logger($e);
      logger('update currentMeta 실패(RollBack)');
      throw $e; // 관련된 모두 롤백되어야 함.
    }
  }
}
