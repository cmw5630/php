<?php

namespace App\Models\log;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class PredictVoteQuestion extends Model
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
