<?php

namespace App\Models\data;

use App\Enums\Opta\League\LeagueStatusType;
use App\Enums\Opta\YesNo;
use Model;
use App\Models\game\PlateCard;
use App\Models\preset\PValidScheduleStage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class League extends Model
{
  use SoftDeletes;
  protected $connection = 'data';

  public $incrementing = false;

  protected $keyType = 'string';

  protected $guarded = [];

  protected $hidden = [
    // 'status',
    'created_at',
    'updated_at',
    'deleted_at',
  ];


  // league status Globalscope
  protected static function booted()
  {
    parent::booted();
    static::addGlobalScope('serviceLeague', function (Builder $builder) {
      $builder->where('status', '!=', LeagueStatusType::HIDE);
    });
  }

  public function scopeParsingAvalilable($query)
  {
    $query->withoutGlobalScopes()->whereNot([
      ['is_opta_contracted', YesNo::NO],
      ['status', LeagueStatusType::HIDE],
    ])->whereNot([
      ['is_opta_contracted', YesNo::NO],
      ['status', LeagueStatusType::DISABLE],
    ]);
  }

  public function seasons()
  {
    return $this->hasMany(Season::class);
  }

  public function currentSeason()
  {
    return $this->hasOne(Season::class)->currentSeasons();
  }

  public function plateCards()
  {
    return $this->hasMany(PlateCard::class);
  }

  public function schedules()
  {
    return $this->hasMany(Schedule::class);
  }

  public function pValidScheduleStage()
  {
    return $this->hasMany(PValidScheduleStage::class);
  }

  public static function defaultLeague()
  {
    return self::where('league_code', config('constant.DEFAULT_LEAGUE'))
      ->first();
  }
}
