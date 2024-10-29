<?php

namespace App\Models\simulation;

use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimulationDivisionStat extends Model
{
  use SoftDeletes;

  protected $connection = 'simulation';

  protected $table = 'division_stats';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function leagueStat()
  {
    return $this->hasMany(SimulationLeagueStat::class, 'season_id', 'season_id');
  }
}
