<?php

namespace App\Models\simulation;

use App\Models\game\DraftSelection;
use App\Models\game\PlateCard;
use App\Models\meta\RefPlayerOverallHistory;
use App\Models\user\UserPlateCard;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimulationOverall extends Model
{
  use SoftDeletes;

  protected $connection = 'simulation';

  protected $table = 'overalls';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'final_overall' => 'array',
    'shot' => 'array',
    'finishing' => 'array',
    'dribbles' => 'array',
    'positioning' => 'array',
    'passing' => 'array',
    'chance_create' => 'array',
    'long_pass' => 'array',
    'crosses' => 'array',
    'tackles' => 'array',
    'blocks' => 'array',
    'clearances' => 'array',
    'instinct' => 'array',
    'ground_duels' => 'array',
    'aerial_duels' => 'array',
    'interceptions' => 'array',
    'recoveries' => 'array',
    'saves' => 'array',
    'high_claims' => 'array',
    'sweeper' => 'array',
    'punches' => 'array',
    'speed' => 'array',
    'balance' => 'array',
    'power' => 'array',
    'stamina' => 'array'
  ];

  public function refPlayerOverall()
  {
    return $this->hasOne(RefPlayerOverallHistory::class, 'player_id', 'player_id')->where('is_current', true);
  }

  public function draftSelection()
  {
    return $this->hasOne(DraftSelection::class, 'user_plate_card_id', 'user_plate_card_id');
  }

  public function userPlateCard()
  {
    return $this->belongsTo(UserPlateCard::class);
  }

  public function plateCard()
  {
    return $this->belongsTo(PlateCard::class, 'player_id', 'player_id');
  }

  public function lineup()
  {
    return $this->hasMany(SimulationLineup::class, 'user_plate_card_id', 'user_plate_card_id');
  }
}
