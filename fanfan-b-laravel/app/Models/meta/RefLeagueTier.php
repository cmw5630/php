<?php

namespace App\Models\meta;

use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefLeagueTier extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];
}
