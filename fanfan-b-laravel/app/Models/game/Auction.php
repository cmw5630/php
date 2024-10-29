<?php

namespace App\Models\game;

use App\Enums\AuctionBidStatus;
use App\Models\user\User;
use App\Models\user\UserPlateCard;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class Auction extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'expired_at' => 'datetime',
  ];

  public function scopeIsSelling($query)
  {
    return $query->where('expired_at', '>', now())
      ->whereNull('canceled_at')
      ->whereNull('sold_at');
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function userPlateCard()
  {
    return $this->belongsTo(UserPlateCard::class);
  }

  public function auctionBid()
  {
    return $this->hasMany(AuctionBid::class)->latest();
  }

  public function successAuctionBid()
  {
    return $this->hasOne(AuctionBid::class)->whereIn('status', [AuctionBidStatus::SUCCESS, AuctionBidStatus::PURCHASED])->latest();
  }
}
