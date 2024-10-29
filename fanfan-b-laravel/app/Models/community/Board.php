<?php

namespace App\Models\community;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class Board extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function boardCategory()
  {
    return $this->hasMany(BoardCategory::class);
  }
}
