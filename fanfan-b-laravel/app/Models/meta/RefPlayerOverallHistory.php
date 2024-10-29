<?php

namespace App\Models\meta;

use App\Enums\Opta\Card\PlateCardStatus;
use App\Models\data\Season;
use App\Models\game\PlateCard;
use App\Models\user\UserPlateCard;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RefPlayerOverallHistory extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function season()
  {
    return $this->belongsTo(Season::class);
  }

  public function refPlayercurrentMeta()
  {
    return $this->hasMany(RefPlayerCurrentMeta::class, 'player_id', 'player_id');
  }

  public function plateCard()
  {
    return $this->belongsTo(PlateCard::class, 'player_id', 'player_id')->withTrashed();
  }

  public function schedule()
  {
    return $this->belongsTo(PlateCard::class, 'player_id', 'player_id')->withTrashed();
  }

  public function userPlateCard($_complete = false)
  {
    return $this->hasMany(UserPlateCard::class, 'ref_player_overall_history_id')->where('user_id',
      auth()->user()->id)
      ->when(!$_complete, function ($query) {
        $query->whereIn('status', [PlateCardStatus::PLATE, PlateCardStatus::UPGRADING]);
      });
  }
}
