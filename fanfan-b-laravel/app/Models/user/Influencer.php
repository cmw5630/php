<?php

namespace App\Models\user;

use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Influencer extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];
}
