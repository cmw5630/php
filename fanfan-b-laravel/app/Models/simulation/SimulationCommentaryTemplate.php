<?php

namespace App\Models\simulation;

use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimulationCommentaryTemplate extends Model
{
  use SoftDeletes;

  protected $connection = 'simulation';

  protected $table = 'commentary_templates';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];
}
