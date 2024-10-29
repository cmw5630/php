<?php

namespace App\Models\simulation;

use App\Models\game\PlateCard;
use Model;
use App\Models\user\UserPlateCard;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimulationUserRank extends Model
{
  use SoftDeletes;

  protected $table = 'user_ranks';

  protected $connection = 'simulation';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'is_confirm' => 'boolean',
  ];

  public function applicant()
  {
    return $this->belongsTo(SimulationApplicant::class);
  }

  public function league()
  {
    return $this->belongsTo(SimulationLeague::class, 'league_id');
  }

  public function applicantStat()
  {
    return $this->belongsTo(SimulationApplicantStat::class, 'applicant_id', 'applicant_id');
  }

}
