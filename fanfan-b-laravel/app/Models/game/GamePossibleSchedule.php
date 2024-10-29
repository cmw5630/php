<?php

namespace App\Models\game;

use App\Models\data\League;
use App\Models\data\Schedule;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GamePossibleSchedule extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'wrapup_draft_completed' => 'boolean',
    'wrapup_draft_cancelled' => 'boolean',
    'wrapup_point_completed' => 'boolean',
  ];

  public function schedule()
  {
    return $this->belongsTo(Schedule::class);
  }

  public function league()
  {
    return $this->belongsTo(League::class);
  }

  public function draft()
  {
    return $this->hasMany(DraftSelection::class, 'schedule_id', 'schedule_id');
  }

  public function gameSchedule()
  {
    return $this->hasMany(GameSchedule::class, 'schedule_id', 'schedule_id');
  }
}
