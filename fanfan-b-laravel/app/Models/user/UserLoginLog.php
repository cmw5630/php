<?php

namespace App\Models\user;

use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserLoginLog extends Model
{
  use SoftDeletes;

  protected $connection = 'log';

  protected $guarded = [];

  protected $hidden = [
    'updated_at',
    'deleted_at',
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
