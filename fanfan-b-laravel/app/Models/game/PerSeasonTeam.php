<?php

namespace App\Models\game;

use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerSeasonTeam extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];
}
