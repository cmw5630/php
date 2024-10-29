<?php

namespace App\Models\log;

use App\Models\game\PlateCard;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class PlateCardFailLog extends Model
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
}
