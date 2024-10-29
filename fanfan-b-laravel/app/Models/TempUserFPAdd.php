<?php

namespace App\Models;

use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TempUserFPAdd extends Model
{
  use SoftDeletes;

  protected $table = 'temp_user_fp_adds';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'special_skills' => 'array',
  ];
}
