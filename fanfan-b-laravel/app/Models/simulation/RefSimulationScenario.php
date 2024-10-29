<?php

namespace App\Models\simulation;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class RefSimulationScenario extends Model
{
  use SoftDeletes;

  protected $table = 'ref_scenarios';

  public $incrementing = false;

  protected $connection = 'simulation';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function refSimulationSequence()
  {
    return $this->hasMany(RefSimulationSequence::class, 'ref_scenario_id', 'id');
  }
}
