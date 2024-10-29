<?php

namespace App\Models\order;

use App\Models\user\User;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function orderPlateCard()
  {
    return $this->hasMany(OrderPlateCard::class);
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
