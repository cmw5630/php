<?php

namespace App\Models\data;

use App\Models\game\PlateCard;
use App\Models\game\Player;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OptaPlayerSeasonStat extends Model
{
  use SoftDeletes;

  protected $connection = 'data';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function player()
  {
    return $this->belongsTo(Player::class);
  }

  public function plateCard()
  {
    return $this->belongsTo(PlateCard::class, 'player_id', 'player_id');
  }

  public function team()
  {
    return $this->belongsTo(Team::class);
  }

  public function season()
  {
    return $this->belongsTo(Season::class);
  }
}
