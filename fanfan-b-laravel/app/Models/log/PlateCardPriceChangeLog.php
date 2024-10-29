<?php

namespace App\Models\log;

use App\Models\data\Season;
use App\Models\game\PlateCard;
use App\Models\user\User;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlateCardPriceChangeLog extends Model
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
    return $this->belongsTo(PlateCard::class);
  }

  public function season()
  {
    return $this->belongsTo(Season::class);
  }
}
