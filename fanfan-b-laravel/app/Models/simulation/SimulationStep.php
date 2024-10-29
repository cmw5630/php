<?php

namespace App\Models\simulation;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class SimulationStep extends Model
{
  use SoftDeletes;

  protected $table = 'steps';

  protected $connection = 'simulation';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'coords' => 'array',
    'ref_params' => 'array',
    'highlight_overall' => 'array',
  ];

  public function sqeuence()
  {
    return $this->belongsTo(RefSimulationSequence::class, 'sequence_meta_id', 'id');
  }

  public function commentaryTemplate()
  {
    return $this->belongsTo(SimulationCommentaryTemplate::class, 'commentary_template_id', 'id');
  }

  public function sequenceMeta()
  {
    return $this->belongsTo(SimulationSequenceMeta::class);
  }
}
