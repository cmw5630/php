<?php

namespace App\Models\log;

use App\Models\game\PlateCard;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlateCardDailyTop extends Model
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
    return $this->belongsTo(PlateCard::class, 'player_id', 'player_id');
  }
}
