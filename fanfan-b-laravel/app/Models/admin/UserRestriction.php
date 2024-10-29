<?php

namespace App\Models\admin;

use App\Models\user\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class UserRestriction extends Model
{
  use SoftDeletes;

  protected $connection = 'admin';
  protected $guarded = [];

  protected $hidden = [
    'updated_at',
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
