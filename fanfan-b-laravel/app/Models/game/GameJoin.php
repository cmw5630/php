<?php

namespace App\Models\game;

use App\Models\user\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GameJoin extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'point' => 'double',
    'reward' => 'integer',
  ];

  protected static function booted()
  {
    parent::booted();
    static::addGlobalScope('excludeWithdraw', function (Builder $builder) {
      $builder->has('user');
    });
  }


  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function game()
  {
    return $this->belongsTo(Game::class);
  }

  public function gameLineups()
  {
    return $this->hasMany(GameLineup::class);
  }
}
