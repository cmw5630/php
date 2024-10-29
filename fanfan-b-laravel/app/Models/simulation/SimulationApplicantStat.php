<?php

namespace App\Models\simulation;

use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimulationApplicantStat extends Model
{
  use SoftDeletes;

  protected $connection = 'simulation';

  protected $table = 'applicant_stats';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'best_goal_players' => 'array',
    'best_assist_players' => 'array',
    'best_save_players' => 'array',
    'best_rating_players' => 'array',
    'recent_5_match' => 'array',
  ];

  public function applicant()
  {
    return $this->belongsTo(SimulationApplicant::class);
  }
  public function season()
  {
    return $this->belongsTo(SimulationSeason::class);
  }

  public function league()
  {
    return $this->belongsTo(SimulationLeague::class);
  }
}
