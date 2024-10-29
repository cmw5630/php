<?php

namespace App\Models\data;

use App\Enums\Opta\Season\SeasonNameType;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\Opta\YesNo;
use App\Models\game\Game;
use Model;
use App\Models\game\PlateCard;
use App\Models\meta\RefAvgFp;
use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Str;

class Season extends Model
{
  use SoftDeletes;

  protected $connection = 'data';

  public $incrementing = false;

  protected $keyType = 'string';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function scopeCurrentSeasons($query, $active = true)
  {
    return $query->where('active', $active ? YesNo::YES : YesNo::NO);
  }

  public function scopeIdsOf(
    $query,
    array $_whenTypes = [],
    string $_nameType = SeasonNameType::ALL,
    int $_beforeYears = 3,
    array $_leagueRemove = []
  ): array {
    if (!in_array($_nameType, SeasonNameType::getValues())) throw new Exception("error : parameter must be in SeasonNameType", 1);
    foreach ($_whenTypes as $whenType) {
      if (!in_array($whenType, SeasonWhenType::getValues())) throw new Exception("error : parameter must be in SeasonWhenType", 1);
    }
    $allSeasonsArr = $query->get(['id', 'name', 'league_id'])->toArray();
    $currentSeasonsArr = $query->whereHas('league', function (Builder $query) use ($_leagueRemove) {
      return $query
        ->whereNotIn('id', $_leagueRemove)
        ->withoutGlobalScopes();
    })->currentSeasons()->get(['id', 'name', 'league_id'])->toArray();
    $total = [SeasonNameType::SINGLE => [], SeasonNameType::DOUBLE => []];
    foreach ($currentSeasonsArr as $curItems) {
      $curLeagueId = $curItems['league_id'];
      $curLeagueYear = Str::before($curItems['name'], '/');
      $nameType = Str::contains($curItems['name'], '/') ? SeasonNameType::DOUBLE : SeasonNameType::SINGLE;
      foreach ($allSeasonsArr as $noCurItems) {
        $noCurrentLeagueId = $noCurItems['league_id'];
        $noCurrentLeagueYear = Str::before($noCurItems['name'], '/');
        if ($curLeagueId === $noCurrentLeagueId) {
          if ($curLeagueYear < $noCurrentLeagueYear) {
            $total[$nameType]['future'][] = $noCurItems['id'];
          } else if ($curLeagueYear === $noCurrentLeagueYear) {
            $total[$nameType]['current'][] = $noCurItems['id'];
          } else if (($curLeagueYear > $noCurrentLeagueYear) && ($curLeagueYear - $_beforeYears <= $noCurrentLeagueYear)) {
            $total[$nameType]['before'][] = $noCurItems['id'];
          }
        }
      }
    }
    $result = [];
    $nameTypes = [];
    if ($_nameType === SeasonNameType::ALL) {
      $nameTypes = SeasonNameType::getValues();
    } else {
      $nameTypes = [$_nameType];
    }
    foreach ($nameTypes as $nameType) {
      foreach ($_whenTypes as $whenType) {
        if (isset($total[$nameType][$whenType])) {
          $result = array_merge($result, $total[$nameType][$whenType]);
        }
      }
    }
    return $result;
  }

  public function scopeGetBeforeCurrentMapCollection($query)
  {
    $currentBeforeSeasonIdMap = [];
    $beforeSeasonIds = Season::idsOf([SeasonWhenType::BEFORE], SeasonNameType::ALL, 1);
    return $query->whereIn('id', $beforeSeasonIds)->withWhereHas('league', function ($_query) {
      return $_query->withoutGlobalScopes()->withWhereHas('seasons', function ($query) {
        return $query->where('active', YesNo::YES);
      });
    })->get()->map(function ($seasonGroup) use (&$currentBeforeSeasonIdMap) {
      return $currentBeforeSeasonIdMap = [
        'league_id' => $seasonGroup['league']['id'],
        'current_id' => $seasonGroup['league']['seasons'][0]['id'],
        'before_id' => $seasonGroup['id'],
        'current_season_name' => $seasonGroup['league']['seasons'][0]['name'],
      ];
    });
  }


  public function scopeGetBeforeFuture($query, array $_whenTypes = [], $_leagueId = null, $_length = 1)
  {
    $currentSeasonsArr = [];
    $result = [];
    $data = $query->get()
      ->groupBy('league_id')
      ->map(function ($group) use (&$currentSeasonsArr) {
        return $group->sortByDesc('start_date')->values()
          ->map(function ($item, $key) use (&$currentSeasonsArr) {
            if ($item->active === YesNo::YES) {
              $currentSeasonsArr[$item->league_id] = $key;
            }
            return $item;
          });
      });

    foreach ($data as $league => $item) {
      if (!is_null($_leagueId) && $league !== $_leagueId) {
        continue;
      }

      $oldest = $currentSeasonsArr[$league] + $_length;
      $latest = $currentSeasonsArr[$league] - $_length;
      if ($oldest <= count($item) - 1 && in_array(SeasonWhenType::BEFORE, $_whenTypes)) {
        // for ($i = $currentSeasonsArr[$league] + 1; $i <= $oldest; $i++) {
        for ($i = $oldest; $i > $currentSeasonsArr[$league]; $i--) {
          $result[$league]['before'][] = $item[$i] ?? null;
        }
      }
      $result[$league]['current'] = $item[$currentSeasonsArr[$league]];
      if ($latest >= 0 && in_array(SeasonWhenType::FUTURE, $_whenTypes)) {
        for ($i = $latest; $i < $currentSeasonsArr[$league]; $i++) {
          $result[$league]['future'][] = $item[$i] ?? null;
        }
      }
    }
    return $result;
  }


  public function league()
  {
    return $this->belongsTo(League::class);
  }

  public function optaPlayerDailyStat()
  {
    return $this->hasMany(OptaPlayerDailyStat::class);
  }

  public function leagueWithoutGS()
  {
    return $this->belongsTo(League::class, 'league_id', 'id')->withoutGlobalScopes();
  }

  public function seasonTeam()
  {
    return $this->hasMany(SeasonTeam::class);
  }

  public function refAvgFp()
  {
    return $this->hasMany(RefAvgFp::class);
  }

  public function plateCard()
  {
    return $this->hasMany(PlateCard::class);
  }

  public function game()
  {
    return $this->hasMany(Game::class);
  }

  public function schedule()
  {
    return $this->hasMany(Schedule::class);
  }
}
