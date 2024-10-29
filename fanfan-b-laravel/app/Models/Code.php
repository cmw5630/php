<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class Code extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'id',
    'created_at',
    'updated_at',
    'deleted_at',
  ];
}
