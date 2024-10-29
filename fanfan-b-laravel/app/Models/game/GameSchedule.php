<?php

namespace App\Models\game;

use App\Models\game\Game;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameSchedule extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function gamePossibleSchedule()
  {
    return $this->belongsTo(GamePossibleSchedule::class, 'schedule_id', 'schedule_id');
  }

  public function game()
  {
    return $this->belongsTo(Game::class);
  }

  public function draft()
  {
    return $this->hasMany(DraftSelection::class, 'schedule_id', 'schedule_id');
  }

  public function gameLineup()
  {
    return $this->hasMany(GameLineup::class, 'schedule_id', 'schedule_id');
  }
}
