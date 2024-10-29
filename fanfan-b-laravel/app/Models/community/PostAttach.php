<?php

namespace App\Models\community;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class PostAttach extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];
}
