<?php

namespace App\Libraries\Traits;

use App\Enums\FantasyCalculator\FantasyPointCategoryType;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Models\data\Schedule;
use App\Models\game\PlayerDailyStat;
use Illuminate\Support\Str;

trait PlayerTrait
{
  /**
   * 최근 5경기 날짜, away_team_name, fantasy_point
   */
  public function playerLastXSchedule($_seasonId, $_playerId, $_teamId = null, $_limit = 5)
  {
    // $_seasonId = PlayerDailyStat::getStatConditionalSeason($_seasonId, $_playerId);

    return Schedule::with([
      'home:id,code,name,short_name',
      'away:id,code,name,short_name',
    ])
      ->has('home')
      ->has('away')
      ->withWhereHas('onePlayerDailyStat', function ($query) use ($_playerId, $_teamId) {
        $query
          ->with('player')
          ->where(
            ['player_id' => $_playerId],
          )
          ->when($_teamId, function ($query) use ($_teamId) {
            return $query->where(['team_id' => $_teamId]);
          })
          ->gameParticipantPlayer();
      })
      ->where([
        'season_id' => $_seasonId,
        'status' => ScheduleStatus::PLAYED
      ])
      ->latest('started_at')
      ->limit($_limit)
      ->get()
      ->map(function ($info) {
        foreach (['home', 'away'] as $teamSide) {
          $info[$teamSide]['is_player_team'] = $info[$teamSide]['id'] === $info->onePlayerDailyStat->team_id;
        }

        $info->rating = $info->onePlayerDailyStat->rating;
        $points = [];
        $points['fantasy_point'] = $info->onePlayerDailyStat->fantasy_point;
        $points['is_mom'] = $info->onePlayerDailyStat->is_mom;
        foreach (FantasyPointCategoryType::getValues() as $categoryName) {
          if ($categoryName === FantasyPointCategoryType::GENERAL) continue;
          $points[$categoryName . '_point'] = $info->onePlayerDailyStat->{$categoryName . '_point'};
        }
        $info['points'] = $points;

        unset($info->onePlayerDailyStat);
        return $info;
      });
  }

  public function getPlayerNameByPolicy(array $names)
  {
    // 해당 function 은 프론트 로직과 항상 맞추어져야 함.
    $nameColumns = config('commonFields.player');
    foreach ($nameColumns as $key) {
      if (!isset($names[$key])) {
        $names[$key] = null;
      }
    }

    $limitCharacter = 26;
    if (strlen($names['match_name']) <= $limitCharacter) {
      return $names['match_name'];
    }

    if ($names['known_name'] === null) {
      return $names['short_last_name'];
    }

    if (strlen($names['known_name']) < strlen($names['match_name'])) {
      return $names['short_last_name'];
    }

    if (str_contains($names['match_name'], '.')) {
      return trim(Str::after($names['match_name'], '.'));
    }

    return $names['match_name'];
  }
}
