<?php

namespace App\Models\game;

use App\Models\user\User;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestUserLog extends Model
{
  use SoftDeletes;

  protected $connection = 'log';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function questType()
  {
    return $this->belongsTo(QuestType::class);
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
