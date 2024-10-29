<?php

namespace App\Models\simulation;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class SimulationCommantry extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];
}
