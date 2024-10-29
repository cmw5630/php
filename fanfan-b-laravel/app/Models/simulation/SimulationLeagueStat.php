<?php

namespace App\Models\simulation;

use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimulationLeagueStat extends Model
{
  use SoftDeletes;

  protected $connection = 'simulation';

  protected $table = 'league_stats';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'attack_power' => 'array',
    'defence_power' => 'array',
  ];
}
