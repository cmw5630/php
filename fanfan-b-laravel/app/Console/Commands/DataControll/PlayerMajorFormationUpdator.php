<?php

namespace App\Console\Commands\DataControll;

use App\Enums\Opta\Player\PlayerSubPosition;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\Schedule;

// 어디에 쓰는 물건인고?
class PlayerMajorFormationUpdator
{
  protected $playerIds = [];
  protected $formations = [
    'total' => 0,
    PlayerSubPosition::ST => 0,
    PlayerSubPosition::LW => 0,
    PlayerSubPosition::AM => 0,
    PlayerSubPosition::RW => 0,
    PlayerSubPosition::LM => 0,
    PlayerSubPosition::CM => 0,
    PlayerSubPosition::RM => 0,
    PlayerSubPosition::LWB => 0,
    PlayerSubPosition::DM => 0,
    PlayerSubPosition::RWB => 0,
    PlayerSubPosition::LB => 0,
    PlayerSubPosition::CB => 0,
    PlayerSubPosition::RB => 0,
    PlayerSubPosition::GK => 0
  ];

  public function __construct(array|string $_playerIds = [])
  {
    $this->playerIds = $_playerIds;
    if (gettype($_playerIds) === 'string') {
      $this->playerIds = [$_playerIds];
    }
  }

  private function baseUpdate()
  {
    foreach ($this->playerIds as $playerId) {
      $sub = Schedule::whereHas('optaPlayerDailyStat', function ($query) use ($playerId) {
        $query->where('player_id', $playerId);
      })->select('id', 'home_team_id', 'away_team_id', 'home_formation_used', 'away_formation_used');

      OptaPlayerDailyStat::joinSub($sub, 'sub', function ($join) {
        $tableName = OptaPlayerDailyStat::getModel()->getTable();
        $join->on($tableName . '.schedule_id', 'sub.id');
      })->where('player_id', $playerId)
        ->get()
        ->map(function ($info) {
          if ($info->team_id === $info->home_team_id) {
            $formationUsed = $info->home_formation_used;
          } else {
            $formationUsed = $info->away_formation_used;
          }

          if ($info->formation_place > 0) {
            $this->formations[config('formation-by-sub-position.formation_used')[$formationUsed][$info->formation_place]]++;
          } else {
            $this->formations[config('formation-by-sub-position.substitution')[$info->sub_position]]++;
          }
          $this->formations['total']++;
        });
    }
  }

  public function update()
  {
    $this->baseUpdate();
  }
}
