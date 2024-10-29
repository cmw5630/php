<?php

namespace App\Models\simulation;

use App\Models\user\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class SimulationApplicant extends Model
{
  use SoftDeletes;

  protected $table = 'applicants';

  protected $connection = 'simulation';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id', 'id');
  }

  public function homeSchedule()
  {
    return $this->hasMany(SimulationSchedule::class, 'home_applicant_id');
  }

  public function awaySchedule()
  {
    return $this->hasMany(SimulationSchedule::class, 'away_applicant_id');
  }

  public function nextHomeSchedule()
  {
    return $this->homeSchedule()
      ->where([
        'is_user_lineup_locked' => false,
        'is_next_lineup_ready' => false,
        'is_sim_ready' => false,
      ])->oldest('started_at');
  }

  public function nextAwaySchedule()
  {
    return $this->awaySchedule()
      ->where([
        'is_user_lineup_locked' => false,
        'is_next_lineup_ready' => false,
        'is_sim_ready' => false,
      ])->oldest('started_at');
  }

  public function userLineupMeta()
  {
    return $this->hasOne(SimulationUserLineupMeta::class, 'applicant_id', 'id');
  }

  public function userLeague()
  {
    return $this->hasOne(SimulationUserLeague::class, 'applicant_id', 'id');
  }

  public function userRank()
  {
    return $this->hasMany(SimulationUserRank::class, 'applicant_id', 'id');
  }
}
