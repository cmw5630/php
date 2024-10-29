<?php

namespace App\Models\meta;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class FantasyMeta extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function scopeWhereSyncGroup(Builder $_query, string $_syncGroup)
  {
    return $_query->where('sync_group', $_syncGroup);
  }

  public function scopeWhereSyncFeedNick(Builder $_query, string $_syncFeedNick)
  {
    return $_query->where('sync_feed_nick', $_syncFeedNick);
  }
}
