<?php

namespace App\Models\game;

use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\data\Team;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class FreeGameLineupMemory extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'special_skills' => 'array',
  ];

  public function season()
  {
    return $this->belongsTo(Season::class);
  }

  public function team()
  {
    return $this->belongsTo(Team::class);
  }

  public function plateCard()
  {
    return $this->belongsTo(PlateCard::class);
  }

  public function draftSchedule()
  {
    return $this->belongsTo(Schedule::class);
  }
}
