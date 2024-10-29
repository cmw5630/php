<?php

namespace App\Models\simulation;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class SimulationLineupMeta extends Model
{
  use SoftDeletes;

  protected $table = 'lineup_metas';

  protected $connection = 'simulation';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'is_result_checked' => 'boolean'
  ];

  public function lineup()
  {
    return $this->hasMany(SimulationLineup::class, 'lineup_meta_id', 'id');
  }

  public function schedule()
  {
    return $this->belongsTo(SimulationSchedule::class, 'schedule_id', 'id');
  }

  public function applicant()
  {
    return $this->belongsTo(SimulationApplicant::class);
  }
}
