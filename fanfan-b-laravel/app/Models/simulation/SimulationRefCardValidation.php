<?php

namespace App\Models\simulation;

use App\Models\user\UserPlateCard;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimulationRefCardValidation extends Model
{
  use SoftDeletes;

  protected $connection = 'simulation';

  protected $table = 'ref_card_validations';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'banned_schedules' => 'array'
  ];

  public function userPlateCard()
  {
    return $this->belongsTo(UserPlateCard::class);
  }
  public function scopeIsBanned($query, $_simulationScheduleId)
  {
    return $query->whereJsonContains('banned_schedules',$_simulationScheduleId);
  }
}
