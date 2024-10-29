<?php

namespace App\Models\game;

use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\data\Team;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyStatTimeline extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [];

  public function season()
  {
    return $this->belongsTo(Season::class);
  }

  public function schedule()
  {
    return $this->belongsTo(Schedule::class);
  }

  public function team()
  {
    return $this->belongsTo(Team::class);
  }
}
