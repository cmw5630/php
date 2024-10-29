<?php

namespace App\Models\log;

use App\Models\user\User;
use App\Models\data\League;
use App\Models\data\Schedule;
use App\Models\data\Team;
use App\Models\game\PlateCard;
use App\Models\user\UserPlateCard;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DraftSelectionLog extends Model
{
  use SoftDeletes;

  protected $connection = 'log';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function plateCard()
  {
    return $this->hasOne(PlateCard::class, 'player_id', 'player_id');
  }

  public function plateCardWithTrashed()
  {
    return $this->hasOne(PlateCard::class, 'player_id', 'player_id')->withTrashed();
  }

  public function userPlateCard()
  {
    return $this->belongsTo(UserPlateCard::class);
  }

  public function schedule()
  {
    return $this->belongsTo(Schedule::class);
  }

  public function league()
  {
    return $this->belongsTo(League::class);
  }

  public function team()
  {
    return $this->belongsTo(Team::class);
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
