<?php

namespace App\Models\simulation;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class SimulationSequenceMeta extends Model
{
  use SoftDeletes;

  protected $table = 'sequence_metas';

  protected $connection = 'simulation';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'sequence_events' => 'array',
  ];

  public function refSimulationSequence()
  {
    return $this->belongsTo(RefSimulationSequence::class, 'ref_sequence_id', 'id');
  }

  public function step()
  {
    return $this->hasMany(SimulationStep::class, 'sequence_meta_id', 'id');
  }

  public function schedule()
  {
    return $this->belongsTo(SimulationSchedule::class);
  }
}
