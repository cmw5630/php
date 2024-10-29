<?php

namespace App\Models\meta;

use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\data\Team;
use App\Models\game\PlateCard;
use App\Models\game\Player;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class RefPlayerCurrentMeta extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'last_5_matches' => 'array',
    'last_team_match' => 'array',
    'last_home' => 'array',
    'last_away' => 'array',
    'grades' => 'array',
    'formation_aggr' => 'array',
    'upcomming_home' => 'array',
    'upcomming_away' => 'array',
  ];

  public function scopeCurrentSeason($query)
  {
    $query->whereHas('currentSeason', function ($innerQuery) {
      return $innerQuery->currentSeasons();
    });
  }

  public function lastSchedule()
  {
    return $this->belongsTo(Schedule::class, 'last_schedule_id');
  }
  public function nextSchedule()
  {
    return $this->belongsTo(Schedule::class, 'upcomming_schedule_id');
  }

  public function player()
  {
    return $this->belongsTo(Player::class);
  }

  public function lastSeason()
  {
    return $this->belongsTo(Season::class, 'last_season_id');
  }

  public function currentSeason()
  {
    return $this->belongsTo(Season::class, 'target_season_id');
  }

  public function lastTeam()
  {
    return $this->belongsTo(Team::class, 'last_team_id');
  }

  public function plateCard()
  {
    return $this->hasMany(PlateCard::class, 'id', 'plate_card_id');
  }
}
