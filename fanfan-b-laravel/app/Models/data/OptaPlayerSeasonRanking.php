<?php

namespace App\Models\data;

use App\Models\game\PlateCard;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class OptaPlayerSeasonRanking extends Model
{
  use SoftDeletes;

  protected $connection = 'data';

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

  public function plateCardWithTrashed()
  {
    return $this->belongsTo(PlateCard::class, 'player_id', 'player_id')->withTrashed();
  }
}
