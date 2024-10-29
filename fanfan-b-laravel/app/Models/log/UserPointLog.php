<?php

namespace App\Models\log;

use App\Models\user\User;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPointLog extends Model
{
  use SoftDeletes;

  protected $connection = 'log';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
