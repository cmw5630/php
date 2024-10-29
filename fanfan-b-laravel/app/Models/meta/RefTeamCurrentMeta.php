<?php

namespace App\Models\meta;

use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefTeamCurrentMeta extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'representative_player' => 'array',
    'next_match_team' => 'array',
  ];
}
