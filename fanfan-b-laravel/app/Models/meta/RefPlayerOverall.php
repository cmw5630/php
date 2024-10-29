<?php

namespace App\Models\meta;

use App\Models\data\Season;
use App\Models\game\PlateCard;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefPlayerOverall extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function season()
  {
    return $this->belongsTo(Season::class);
  }

  public function refPlayercurrentMeta()
  {
    return $this->hasMany(RefPlayerCurrentMeta::class, 'player_id', 'player_id');
  }

  public function plateCard()
  {
    return $this->belongsTo(PlateCard::class, 'player_id', 'player_id')->withTrashed();
  }
}
