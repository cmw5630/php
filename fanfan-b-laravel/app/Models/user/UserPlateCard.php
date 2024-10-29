<?php

namespace App\Models\user;

use App\Enums\Opta\Card\PlateCardStatus;
use App\Models\data\League;
use App\Models\data\Season;
use App\Models\data\Team;
use App\Models\game\Auction;
use App\Models\game\DraftComplete;
use App\Models\game\DraftSelection;
use App\Models\game\GameLineup;
use App\Models\game\PlateCard;
use App\Models\meta\RefPlayerOverallHistory;
use App\Models\order\DraftOrder;
use App\Models\simulation\SimulationLineup;
use App\Models\simulation\SimulationOverall;
use App\Models\simulation\SimulationRefCardValidation;
use App\Models\simulation\SimulationUserLineup;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserPlateCard extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  protected $casts = [
    'is_mom' => 'boolean',
    'is_open' => 'boolean',
    'is_free' => 'boolean',
    'draft_team_names' => 'array',
    'level_weight' => 'float',
    'ingame_fantasy_point' => 'float',
    'special_skills' => 'array',
  ];

  protected static function booted()
  {
    parent::booted();
    static::addGlobalScope('excludeBurned', function (Builder $builder) {
      $builder->whereNull('burned_at');
    });
  }

  public function scopeGradeFilters($query, $input)
  {
    return $query->where('status', PlateCardStatus::COMPLETE)
      ->when($input['league'], function ($query, $league) {
        $query->whereHas('draftSeason.league', function ($leaguQ) use ($league) {
          $leaguQ->whereId($league);
        });
      })->when($input['club'], function ($query, $clubs) {
        $query->whereIn('draft_team_id', $clubs);
      })->when($input['player_name'], function ($query, $name) {
        $query->whereHas('plateCardWithTrashed', function ($plateCard) use ($name) {
          $plateCard->withTrashed()->nameFilterWhere($name);
        });
      })
      ->when($input['other'], function ($query) use ($input) {
        $query->whereHas('plateCard', function ($plateCard) use ($input) {
          $plateCard->etcFilters($input);
        });
      }, function ($query) {
        $query->whereHas('plateCardWithTrashed', function ($plateCard) {
          $plateCard->currentSeason();
        });
      });
  }

  public function scopePlateFilters($query, $input)
  {
    return $query->where('status', '!=', PlateCardStatus::COMPLETE)
      ->whereHas('plateCard', function ($plateCard) use ($input) {
        $plateCard->when($input['league'], function ($innerQuery, $league) {
          $innerQuery->where('league_id', $league);
        })->when($input['club'], function ($innerQuery, $clubs) {
          $innerQuery->whereIn('team_id', $clubs);
        })->when($input['player_name'], function ($innerQuery, $name) {
          $innerQuery->nameFilterWhere($name);
        })->when($input['other'], function ($etcQuery) use ($input) {
          $etcQuery->etcFilters($input);
        }, function ($innerquery) {
          $innerquery->currentSeason();
        });
      });
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function auction()
  {
    return $this->hasOne(Auction::class)->latest();
  }

  public function myAuction()
  {
    if (is_null(auth()->user())) {
      return null;
    }

    return $this->auction()->where('user_id', auth()->user()->id)->latest();
  }

  public function plateCard()
  {
    return $this->belongsTo(PlateCard::class);
  }

  public function plateCardWithTrashed()
  {
    return $this->hasOne(PlateCard::class, 'id', 'plate_card_id')->withTrashed();
  }

  public function draftSelection()
  {
    return $this->hasOne(DraftSelection::class);
  }

  public function draftComplete()
  {
    return $this->hasOne(DraftComplete::class);
  }

  public function draftSeason()
  {
    return $this->belongsTo(Season::class, 'draft_season_id', 'id');
  }

  public function draftTeam()
  {
    return $this->belongsTo(Team::class, 'draft_team_id', 'id');
  }

  public function draftOrder()
  {
    return $this->hasOne(DraftOrder::class);
  }

  public function orderTeam()
  {
    return $this->belongsTo(Team::class, 'order_team_id', 'id');
  }

  public function orderLeague()
  {
    return $this->belongsTo(League::class, 'order_league_id', 'id');
  }

  public function simulationOverall()
  {
    return $this->hasOne(SimulationOverall::class, 'user_plate_card_id', 'id');
  }

  public function refPlayerOverall()
  {
    return $this->belongsTo(RefPlayerOverallHistory::class, 'ref_player_overall_history_id', 'id');
  }

  public function gameLineup()
  {
    return $this->hasMany(GameLineup::class);
  }

  public function refCardValidation()
  {
    return $this->hasOne(SimulationRefCardValidation::class);
  }

  public function simulationLineup()
  {
    return $this->hasMany(SimulationLineup::class);
  }

  public function simulationUserLineup()
  {
    return $this->hasOne(SimulationUserLineup::class);
  }
}
