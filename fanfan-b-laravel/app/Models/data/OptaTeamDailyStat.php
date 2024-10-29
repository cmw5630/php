<?php

namespace App\Models\data;

use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OptaTeamDailyStat extends Model
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
