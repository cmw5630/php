<?php

namespace App\Models\game;

use App\Models\data\Season;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class FreePlayerPool extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function plateCard()
  {
    return $this->belongsTo(PlateCard::class, 'player_id', 'player_id');
  }

  public function season()
  {
    return $this->belongsTo(Season::class);
  }
}
