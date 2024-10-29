<?php

namespace App\Models\data;

use App\Models\game\PlateCard;
use App\Models\game\Player;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class Transfer extends Model
{
  use SoftDeletes;

  protected $connection = 'data';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function team()
  {
    return $this->belongsTo(Team::class);
  }

  public function fromTeam()
  {
    return $this->belongsTo(Team::class, 'from_team_id', 'id');
  }

  public function player()
  {
    return $this->hasOne(Player::class, 'id', 'player_id');
  }

  public function plateCardWithTrashed()
  {
    return $this->hasOne(PlateCard::class, 'player_id', 'player_id')->withTrashed();
  }
}
