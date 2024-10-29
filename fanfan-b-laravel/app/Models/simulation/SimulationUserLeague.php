<?php

namespace App\Models\simulation;

use App\Models\game\PlateCard;
use Model;
use App\Models\user\UserPlateCard;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimulationUserLeague extends Model
{
  use SoftDeletes;

  protected $table = 'user_leagues';

  protected $connection = 'simulation';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function applicant()
  {
    return $this->belongsTo(SimulationApplicant::class);
  }

  public function division()
  {
    return $this->belongsTo(SimulationDivision::class);
  }

  public function league()
  {
    return $this->belongsTo(SimulationLeague::class);
  }

  public function leagueStat()
  {
    return $this->hasOne(SimulationLeagueStat::class, 'league_id', 'league_id');
  }

  public function season()
  {
    return $this->belongsTo(SimulationSeason::class);
  }
}
