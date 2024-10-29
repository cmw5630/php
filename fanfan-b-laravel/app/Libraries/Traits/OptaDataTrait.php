<?php

namespace App\Libraries\Traits;

use App\Enums\Opta\YesNo;
use App\Models\data\Season;
use Str;

trait OptaDataTrait
{
  public function getLeagueIdToSeasonIdMap(): array
  {
    // 리그당 현재 시즌, 다음 시즌 Id를 알 수 있다.
    $currentSeasonsArr = Season::currentSeasons()->get(['id', 'name', 'league_id'])->toArray();
    $noCurrentSeasonsArr = Season::where('active', YesNo::NO)->get(['id', 'name', 'league_id'])->toArray();

    $afterSeasonArr = [];
    foreach ($currentSeasonsArr as $curItems) {
      $temp = [];
      $curLeagueId = $curItems['league_id'];
      $curLeagueYear = (int) Str::after($curItems['name'], '/');
      $temp = ['current' => $curItems['id']];
      foreach ($noCurrentSeasonsArr as $noCurItems) {
        $noCurLeagueId = $noCurItems['league_id'];
        $noCurLeagueYear = (int) Str::after($noCurItems['name'], '/');
        if ($curLeagueId === $noCurLeagueId && $noCurLeagueYear === ($curLeagueYear + 1)) {
          $temp = [
            'current' => $curItems['id'],
            'future' => $noCurItems['id']
          ];
        }
        logger($temp);
        $afterSeasonArr[$curLeagueId] = $temp;
      }
    }
    return $afterSeasonArr;
  }

  public function getAllLeagueIdToSeasonIdMap(): array
  {
    $seasonsArr = Season::get([
      'id'
    ])->toArray();

    $allArr = [];
    foreach ($seasonsArr as $items) {
      array_push($allArr, $items['id']);
    }
    return $allArr;
  }
}
