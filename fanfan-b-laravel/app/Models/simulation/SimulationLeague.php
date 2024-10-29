<?php

namespace App\Models\simulation;

use App\Libraries\Traits\StaticTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class SimulationLeague extends Model
{
  use SoftDeletes, StaticTrait;

  protected $table = 'leagues';

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
    'start_date' => 'datetime:Y-m-d',
    'end_date' => 'datetime:Y-m-d',
  ];

  public static function boot()
  {
    parent::boot();

    static::creating(function ($model) {
      $model->id = self::getUuid($model);
    });
  }

  public function season()
  {
    return $this->belongsTo(SimulationSeason::class);
  }

  public function division()
  {
    return $this->belongsTo(SimulationDivision::class, 'division_id');
  }

  public function userRank()
  {
    return $this->hasMany(SimulationUserRank::class, 'league_id');
  }
}
