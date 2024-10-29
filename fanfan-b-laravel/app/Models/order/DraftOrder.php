<?php

namespace App\Models\order;

use App\Models\user\User;
use App\Models\user\UserPlateCard;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DraftOrder extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    // 'created_at',
    // 'updated_at',
    'deleted_at',
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function userPlateCard()
  {
    return $this->belongsTo(UserPlateCard::class);
  }
}
