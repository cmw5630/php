<?php

namespace App\Models\meta;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class RefPowerRankingQuantile extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];
}
