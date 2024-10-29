<?php

namespace App\Models;

use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoginBlockedIp extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];
}
