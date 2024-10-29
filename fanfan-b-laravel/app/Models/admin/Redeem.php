<?php

namespace App\Models\admin;

use App\Models\user\UserRedeem;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class Redeem extends Model
{
  use SoftDeletes;

  protected $connection = 'admin';

  protected $casts = [
    'reward' => 'json',
    'requested_at' => 'datetime',
    'completed_at' => 'datetime',
  ];

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function userRedeems()
  {
    return $this->hasMany(UserRedeem::class);
  }

}
