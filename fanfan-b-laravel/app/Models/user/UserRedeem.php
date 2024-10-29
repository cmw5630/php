<?php

namespace App\Models\user;

use App\Models\admin\Redeem;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class UserRedeem extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function redeem()
  {
    return $this->belongsTo(Redeem::class);
  }
}
