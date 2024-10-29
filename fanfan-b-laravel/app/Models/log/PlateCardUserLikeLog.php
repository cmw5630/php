<?php

namespace App\Models\log;


use App\Models\game\PlateCard;
use App\Models\user\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class PlateCardUserLikeLog extends Model
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

  public function plateCard()
  {
    return $this->belongsTo(PlateCard::class);
  }
}
