<?php

namespace App\Models\meta;

use App\Models\game\Player;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class RefCountryCode extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function player()
  {
    return $this->belongsTo(Player::class, 'nationality_id', 'nationality_id');
  }
}
