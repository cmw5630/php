<?php

namespace App\Models\order;

use App\Models\data\League;
use App\Models\data\Team;
use App\Models\game\PlateCard;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderPlateCard extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function order()
  {
    return $this->belongsTo(Order::class);
  }

  public function plateCard()
  {
    return $this->belongsTo(PlateCard::class);
  }

  public function plateCardWithTrashed()
  {
    return $this->belongsTo(PlateCard::class, 'plate_card_id', 'id')->withTrashed();
  }

  public function leagueNoGlobal()
  {
    return $this->belongsTo(League::class, 'league_id', 'id')->withoutGlobalScopes();
  }

  public function team()
  {
    return $this->belongsTo(Team::class);
  }
}
