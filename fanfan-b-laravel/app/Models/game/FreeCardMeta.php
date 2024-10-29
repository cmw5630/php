<?php

namespace App\Models\game;

use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class FreeCardMeta extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function freeCardShuffleMemory()
  {
    return $this->hasMany(FreeCardShuffleMemory::class);
  }

  public function freeGameLineupMemory()
  {
    return $this->hasMany(FreeGameLineupMemory::class);
  }
}
