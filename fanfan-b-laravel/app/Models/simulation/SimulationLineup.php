<?php

namespace App\Models\simulation;

use App\Models\game\PlateCard;
use App\Models\meta\RefPlayerOverallHistory;
use Model;
use App\Models\user\UserPlateCard;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimulationLineup extends Model
{
  use SoftDeletes;

  protected $table = 'lineups';

  protected $connection = 'simulation';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'game_started' => 'boolean',
    'is_mom' => 'boolean',
    'is_changed' => 'boolean',
  ];

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

  public function lineupMeta()
  {
    return $this->belongsTo(SimulationLineupMeta::class);
  }

  public function refPlayerOverall()
  {
    return $this->hasOne(RefPlayerOverallHistory::class, 'player_id', 'player_id')->where('is_current', true);
  }
}
