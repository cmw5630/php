<?php

namespace App\Models\log;

use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserChangeLog extends Model
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
