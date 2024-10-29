<?php

namespace App\Models\data;

use App\Casts\CorrectFloatPoint;
use App\Models\game\PlateCard;
use App\Models\game\Player;
use App\Models\meta\RefPlayerOverallHistory;
use App\Models\meta\RefTeamFormationMap;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OptaPlayerDailyStat extends Model
{
  use SoftDeletes;

  protected $connection = 'data';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'rating' => CorrectFloatPoint::class . ':1',
    'fantasy_point' => CorrectFloatPoint::class . ':1',
    'power_ranking' => CorrectFloatPoint::class . ':2',
    'is_mom' => 'boolean',
  ];


  public function scopeGameParticipantPlayer(Builder $_query)
  {
    return $_query->where(function ($query) {
      $query->where('game_started', true)->orWhere('total_sub_on', true);
    });
  }

  public function schedule()
  {
    return $this->belongsTo(Schedule::class);
  }
  public function season()
  {
    return $this->belongsTo(Season::class);
  }
  public function team()
  {
    return $this->belongsTo(Team::class);
  }
  public function player()
  {
    return $this->belongsTo(Player::class);
  }

  public function plateCard()
  {
    return $this->hasOne(PlateCard::class, 'player_id', 'player_id');
  }

  public function plateCardWithTrashed()
  {
    return $this->hasOne(PlateCard::class, 'player_id', 'player_id')->withTrashed();
  }

  public function refTeamFormationMap()
  {
    return $this->hasOne(RefTeamFormationMap::class, 'player_id', 'player_id');
  }

  public function playerOverall()
  {
    return $this->hasMany(RefPlayerOverallHistory::class, 'player_id', 'player_id')->where('is_current', true);
  }
}
