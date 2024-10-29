<?php

namespace App\Models\data;

use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Player\PlayerStatus;
use App\Enums\Opta\Player\PlayerType;
use App\Enums\Opta\YesNo;
use App\Models\game\PlateCard;
use App\Models\meta\RefCountryCode;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class Squad extends Model
{
  use SoftDeletes;

  protected $connection = 'data';
  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function league()
  {
    return $this->belongsTo(League::class);
  }
  public function season()
  {
    return $this->belongsTo(Season::class);
  }
  public function team()
  {
    return $this->belongsTo(Team::class);
  }
  public function optaPlayerSeasonStat()
  {
    return $this->hasMany(OptaPlayerSeasonStat::class, 'player_id', 'player_id');
  }

  // public function plateCard()
  // {
  //   return $this->hasOne(PlateCard::class);
  // }

  public function plateCardWithTrashed()
  {
    return $this->hasOne(PlateCard::class, 'player_id', 'player_id')->withTrashed();
  }

  public function suspension()
  {
    return $this->hasMany(Suspension::class, 'player_id', 'player_id');
  }
  public function injury()
  {
    return $this->hasMany(Injuries::class, 'player_id', 'player_id');
  }

  public function countryCode()
  {
    return $this->belongsTo(RefCountryCode::class, 'nationality_id', 'nationality_id');
  }

  public function scopeNoCurrentSeason($_query)
  {
    return $_query->whereHas('season', function ($query) {
      $query->where('active', '!=', YesNo::YES);
    });
  }

  public function scopeCurrentSeason($_query)
  {
    return $_query->whereHas('season', function ($query) {
      $query->where('active', YesNo::YES);
    });
  }

  public function scopeActivePlayers($_query)
  {
    return $_query->where(
      [
        'status' => PlayerStatus::ACTIVE,
        'active' => YesNo::YES,
        'type' => PlayerType::PLAYER
      ]
    );
  }

  public function scopeApplyFilters($query, $input)
  {
    return $query->currentSeason(true)
      ->when($input['league'], function ($query, $league) {
        $query->where('league_id', $league);
      })
      ->when($input['club'], function ($innerQuery, $clubs) {
        $innerQuery->whereIn('team_id', $clubs);
      })
      // ->when($input['position'], function ($query, $positions) {
      //   $query->whereIn('position', $positions);
      // });
      ->when($input['player_name'], function ($query, $name) {
        $query->nameFilter($name);
      });
  }

  public function scopeNameFilter(Builder $query, $name)
  {
    $name = addslashes($name);
    return $query->where(function ($query) use ($name) {
      $query->whereLike([
        'first_name',
        'last_name',
        'short_first_name',
        'short_last_name'
      ], $name, 'right')
        ->orWhere(function ($orWhere) use ($name) {
          $name = preg_replace('/\.\s*/', '. ', $name);
          $orWhere->whereLike(['match_name'], $name);
        });
    });
  }


  public function scopeAvailablePositions($_query)
  {
    return $_query->whereIn(
      'position',
      [
        PlayerPosition::ATTACKER,
        PlayerPosition::DEFENDER,
        PlayerPosition::GOALKEEPER,
        PlayerPosition::MIDFIELDER,
      ]
    );
  }

  // 관계된 테이블에서 select 할 때 id는 필수값
  public function scopeInfosForSearch($_query)
  {
    return $_query->with('league', function ($query) {
      $query->select('id', 'league_code')->withoutGlobalScope('serviceLeague');
    });
  }

  public function scopeExceptLeague($_query, ...$_leagues)
  {
    return $_query->whereNotIn('league_id', $_leagues);
  }
}
