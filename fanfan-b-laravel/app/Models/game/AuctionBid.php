<?php

namespace App\Models\game;

use App\Models\user\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class AuctionBid extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'updated_at',
    'deleted_at',
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function auction()
  {
    return $this->belongsTo(Auction::class);
  }
}
