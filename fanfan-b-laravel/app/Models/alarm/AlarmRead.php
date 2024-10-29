<?php

namespace App\Models\alarm;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class AlarmRead extends Model
{
  use SoftDeletes;

  protected $connection = 'log';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function alarmLog()
  {
    return $this->belongsTo(AlarmLog::class);
  }
}
