<?php

namespace App\Models;

use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlayerSeasonRankingView extends Model
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
