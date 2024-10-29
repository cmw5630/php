<?php

namespace App\Models\log;

use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventPointLog extends Model
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
