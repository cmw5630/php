<?php

namespace App\Models\log;

use App\Models\admin\Admin;
use App\Models\game\PlateCard;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class PredictVote extends Model
{
  use SoftDeletes;

  protected $connection = 'log';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function items()
  {
    return $this->hasMany(PredictVoteItem::class);
  }

  public function admin()
  {
    return $this->belongsTo(Admin::class);
  }

  public function logs()
  {
    return $this->hasManyThrough(PredictVoteLog::class, PredictVoteItem::class);
  }
}
