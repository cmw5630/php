<?php

namespace App\Models\simulation;

use App\Models\game\PlateCard;
use Model;
use App\Models\user\UserPlateCard;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimulationUserLineup extends Model
{
  use SoftDeletes;

  protected $table = 'user_lineups';

  protected $connection = 'simulation';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'game_started' => 'boolean',
  ];

  public function userLineupMeta()
  {
    return $this->belongsTo(SimulationUserLineupMeta::class);
  }

  public function plateCardWithTrashed()
  {
    return $this->belongsTo(PlateCard::class, 'player_id', 'player_id')->withTrashed();
  }

  public function userPlateCard()
  {
    return $this->belongsTo(UserPlateCard::class);
  }

  public function simulationOverall()
  {
    return $this->belongsTo(SimulationOverall::class, 'user_plate_card_id', 'user_plate_card_id');
  }

}
