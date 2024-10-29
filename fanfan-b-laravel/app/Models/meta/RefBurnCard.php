<?php

namespace App\Models\meta;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class RefBurnCard extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];
}
