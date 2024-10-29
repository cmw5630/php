<?php

namespace App\Models\user;

use App\Models\data\Team;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserMeta extends Model
{
  use SoftDeletes;
  protected $primaryKey = 'user_id';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function favoriteTeam()
  {
    return $this->belongsTo(Team::class, 'favorite_team_id');
  }
}
