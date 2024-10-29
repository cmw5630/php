<?php

namespace App\Models\log;

use App\Models\game\Player;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class TeamChangeHistory extends Model
{
  use SoftDeletes;

  protected $connection = 'log';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'is_team_changed' => 'boolean',
  ];

  public function player()
  {
    return $this->belongsTo(Player::class);
  }
}
