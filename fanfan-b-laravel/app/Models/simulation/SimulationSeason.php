<?php

namespace App\Models\simulation;

use App\Enums\Opta\YesNo;
use App\Libraries\Traits\StaticTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class SimulationSeason extends Model
{
  use SoftDeletes, StaticTrait;

  protected $table = 'seasons';

  protected $connection = 'simulation';

  public $incrementing = false;

  protected $keyType = 'string';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'first_started_at' => 'datetime',
    'last_started_at' => 'datetime',
  ];

  public static function boot()
  {
    parent::boot();

    static::creating(function ($model) {
      $model->id = self::getUuid($model);
    });
  }

  public function scopeCurrentSeasons($query, $active = true)
  {
    return $query->where('active', $active ? YesNo::YES : YesNo::NO);
  }

  public function league()
  {
    return $this->hasOne(SimulationLeague::class, 'season_id');
  }

  public function schedules()
  {
    return $this->hasMany(SimulationSchedule::class, 'season_id');
  }

  public function applicantStat()
  {
    return $this->hasMany(SimulationApplicantStat::class, 'season_id');
  }
}
