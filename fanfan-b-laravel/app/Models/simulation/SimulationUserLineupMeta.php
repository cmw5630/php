<?php

namespace App\Models\simulation;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class SimulationUserLineupMeta extends Model
{
  use SoftDeletes;

  protected $table = 'user_lineup_metas';
  protected $connection = 'simulation';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'is_first' => 'boolean'
  ];

  public function applicant()
  {
    return $this->belongsTo(SimulationApplicant::class, 'applicant_id', 'id');
  }


  public function userLineup()
  {
    return $this->hasMany(SimulationUserLineup::class, 'user_lineup_meta_id', 'id');
  }
}
