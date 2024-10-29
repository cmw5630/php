<?php

namespace App\Models\data;

use App\Models\game\PlateCard;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Commentary extends Model
{
  use SoftDeletes;
  protected $connection = 'data';

  protected $guarded = [];

  protected $hidden = [
    // 'status',
    'created_at',
    'updated_at',
    'deleted_at',
  ];



  public function homeTeam()
  {
    return $this->hasOne(Team::class, 'id', 'home_team_id');
  }

  public function awayTeam()
  {
    return $this->hasOne(Team::class, 'id', 'away_team_id');
  }

  public function playerRefOnMatch()
  {
    return $this->belongsTo(OptaPlayerDailyStat::class, 'player_ref1', 'player_id');
  }


  public function playerRef1()
  {
    return $this->belongsTo(PlateCard::class, 'player_ref1', 'player_id')->withTrashed();
  }

  public function playerRef2()
  {
    return $this->belongsTo(PlateCard::class, 'player_ref2', 'player_id')->withTrashed();
  }
}
