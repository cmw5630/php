<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class BlockedIp extends Model
{
  use SoftDeletes;

  protected $connection = 'admin';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];
}
