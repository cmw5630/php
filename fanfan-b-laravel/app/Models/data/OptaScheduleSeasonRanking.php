<?php

namespace App\Models\data;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class OptaScheduleSeasonRanking extends Model
{
  use SoftDeletes;

  protected $connection = 'data';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];
}
