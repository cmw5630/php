<?php

namespace App\Models\game;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class RefTransferValue extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];
}
