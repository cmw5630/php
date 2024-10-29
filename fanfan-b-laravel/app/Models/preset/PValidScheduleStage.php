<?php

namespace App\Models\preset;

use App\Models\data\League;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PValidScheduleStage extends Model
{
  use SoftDeletes;

  protected $connection = 'preset';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function league()
  {
    return $this->belongsTo(League::class);
  }
}
