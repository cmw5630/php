<?php

namespace App\Models\order;

use App\Models\game\PlateCard;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cart extends Model
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
    return $this->belongsTo(PlateCard::class);
  }
}
