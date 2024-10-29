<?php

namespace App\Models\alarm;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class AlarmTemplate extends Model
{
  use SoftDeletes;

  public $incrementing = false;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'message' => 'array',
  ];
}
