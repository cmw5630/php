<?php

namespace App\Console\Commands\DataControll;

use App\Libraries\Traits\DraftTrait;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Libraries\Traits\GameTrait;
use App\Models\data\Schedule;
use App\Models\game\PlayerDailyStat;
use App\Models\meta\RefPlateCardRank;
use App\Models\meta\RefPlayerOverallHistory;

class PlateCardRankUpdator
{
  use FantasyMetaTrait, DraftTrait, GameTrait;

  protected $feedNick;
  protected $playerId;

  public function __construct($_playerId = null)
  {
    $this->playerId = $_playerId;
    $this->feedNick = 'PCRU';
  }

  public function update()
  {
    $updateArr = [];
    // overall, match_name, grade
    $info = RefPlayerOverallHistory::with('plateCard:player_id,position,grade,match_name')
      ->where([
        ['player_id', $this->playerId],
        ['is_current', true]
      ])
      ->select('player_id', 'season_id', 'final_overall')
      ->first();

    if (is_null($info)) return;

    $updateArr['season_id'] = $info->season_id;
    $updateArr['position'] = $info->plateCard->position;
    $updateArr['overall'] = $info->final_overall;
    $updateArr['grade'] = $info->plateCard->grade;
    $updateArr['match_name'] = $info->plateCard->match_name;

    // fantasy_point
    $allSchedules = PlayerDailyStat::whereHas('season', function ($query) {
      $query->currentSeasons();
    })->where('player_id', $this->playerId)
      ->gameParticipantPlayer()
      ->select('schedule_id', 'fantasy_point')
      ->get()
      ->keyBy('schedule_id')
      ->toArray();

    $lastScheduleId = Schedule::whereIn('id', array_keys($allSchedules))
      ->orderByDesc('ended_at')
      ->limit(1)
      ->value('id');

    $updateArr['fantasy_point'] = 0;
    if (count($allSchedules) > 0 && isset($allSchedules[$lastScheduleId])) {
      $updateArr['fantasy_point'] = $allSchedules[$lastScheduleId]['fantasy_point'];
    }

    RefPlateCardRank::updateOrCreateEx(
      [
        'player_id' => $this->playerId,
      ],
      $updateArr,
      false,
      true,
    );
  }
}
