<?php

namespace App\Models\user;

use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserReferral extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function joinUser()
  {
    return $this->belongsTo(User::class, 'user_id', 'id');
  }

  public function invite()
  {
    return $this->belongsTo(self::class, 'referral_id', 'id');
  }
}
