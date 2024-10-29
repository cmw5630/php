<?php

namespace App\Models\log;

use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserWithdrawLog extends Model
{
  use SoftDeletes;

  protected $connection = 'log';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];
}
