<?php

namespace App\Models\simulation;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class RefSimulationSequence extends Model
{

  use SoftDeletes;

  protected $table = 'ref_sequences';

  protected $connection = 'simulation';

  protected $guarded = [];

  protected $casts =     [
    'step0' => 'array',
    'step1' => 'array',
    'step2' => 'array',
    'step3' => 'array',
    'step4' => 'array',
    'step5' => 'array',
    'step6' => 'array',
    'step7' => 'array',
    'step8' => 'array',
    'step9' => 'array',
    'step10' => 'array',
    'step11' => 'array',
    'step12' => 'array',
    'step13' => 'array',
    'step14' => 'array',
    'step15' => 'array',
    'step16' => 'array',
    'event_split' => 'array',
    'highlight_check' => 'array',
  ];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function scopeFirstHalf($qeury)
  {
    $qeury->where('nth_half', 'first')->whereNotNull('attack_direction');
  }

  public function scopeSecondHalf($qeury)
  {
    $qeury->where('nth_half', 'second')->whereNotNull('attack_direction');
  }

  public function refSimulationScenario()
  {
    return $this->belongsTo(RefSimulationScenario::class, 'id', 'ref_scenario_id');
  }

  public function sequenceMeta()
  {
    return $this->hasMany(SimulationSequenceMeta::class, 'ref_sequence_id', 'id');
  }
}
