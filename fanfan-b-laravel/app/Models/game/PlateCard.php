<?php

namespace App\Models\game;

use App\Casts\CustomTeamName;
use App\Enums\Opta\League\LeagueStatusType;
use App\Models\data\League;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\OptaPlayerSeasonStat;
use App\Models\data\Season;
use App\Models\data\Team;
use App\Models\log\PlateCardFailLog;
use App\Models\log\PlateCardPriceChangeLog;
use App\Models\meta\RefPlayerCurrentMeta;
use App\Models\meta\RefPlayerOverallHistory;
use App\Models\meta\RefPowerRankingQuantile;
use App\Models\meta\RefTeamFormationMap;
use App\Models\Scopes\AddPlayerNameScope;
use App\Models\user\UserPlateCard;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlateCard extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
  ];

  protected $casts = [
    'team_short_name' => CustomTeamName::class
  ];

  protected static function booted()
  {
    parent::booted();
    static::addGlobalScope(new AddPlayerNameScope);
  }

  public function league()
  {
    return $this->belongsTo(League::class);
  }
  public function season()
  {
    return $this->belongsTo(Season::class);
  }
  public function team()
  {
    return $this->belongsTo(Team::class);
  }

  public function player()
  {
    return $this->belongsTo(Player::class);
  }

  public function userPlateCard()
  {
    return $this->hasMany(UserPlateCard::class);
  }

  public function plateCardFailLog()
  {
    return $this->hasMany(PlateCardFailLog::class, 'player_id', 'player_id');
  }

  public function plateCardPriceChangeLog()
  {
    return $this->hasMany(PlateCardPriceChangeLog::class);
  }

  public function optaPlayerSeasonStat()
  {
    return $this->hasMany(OptaPlayerSeasonStat::class, 'player_id', 'player_id');
  }

  public function refTeamFormationMap()
  {
    return $this->hasOne(RefTeamFormationMap::class, 'player_id', 'player_id');
  }

  public function refPlayerOverall()
  {
    return $this->hasMany(RefPlayerOverallHistory::class, 'player_id', 'player_id');
  }

  public function currentRefPlayerOverall()
  {
    return $this->hasOne(RefPlayerOverallHistory::class, 'player_id', 'player_id')->where('is_current', true);
  }

  public function optaPlayerDailyStat()
  {
    return $this->hasMany(OptaPlayerDailyStat::class, 'player_id', 'player_id');
  }

  public function freePlayerPool()
  {
    return $this->hasMany(FreePlayerPool::class, 'player_id', 'player_id');
  }

  public function onePlayerCurrentMeta()
  {
    return $this->hasOne(RefPlayerCurrentMeta::class, 'player_id', 'player_id')
      ->latest();
  }

  public function scopeNameOrder($query, $short = false, $order = 'asc')
  {
    return $query->orderBy(($short ? 'short_' : '') . 'first_name', $order)
      ->orderBy(($short ? 'short_' : '') . 'last_name', $order);
  }

  public function scopeHasPowerRankingQuantile($query)
  {
    $availableLeagueIds = [];
    RefPowerRankingQuantile::get()->unique('league_id')->each(function ($item) use (&$availableLeagueIds) {
      $availableLeagueIds = array_merge($availableLeagueIds, [$item['league_id']]);
    });
    return $query->whereIn('league_id', $availableLeagueIds);
  }

  public function scopeIsPriceSet(Builder $query, $_onSale = true)
  {
    /**
     * 반드시 현재 시즌에 대해서만(시즌이 바뀌는 경우 만들어진 플레이트카드 중 현재 시즌에 대해서만 적용해야하기 때문)
     */
    return $query
      ->currentSeason()
      ->when(
        $_onSale,
        function ($innerQuery) {
          $innerQuery->whereIn(
            'price_init_season_id',
            Season::currentSeasons()->pluck('id')
          ) // price 초기화 시즌이 현재시즌인지
            ->whereColumn('season_id', 'price_init_season_id') // 반드시 같아야 함.
            ->whereNotNull('init_grade')
            ->whereNotNull('grade')
            ->whereNotNull('price')
            ->whereNotNull('price_init_season_id');
        },
        function ($innerQuery) {
          $innerQuery->where(function ($innerQuery2) {
            $innerQuery2->whereNotIn(
              'price_init_season_id',
              Season::currentSeasons()->pluck('id')
            ); // price 초기화 시즌이 현재시즌인지
          })
            ->orWhereColumn('season_id', '<>', 'price_init_season_id') // 같지 않은 경우도.
            ->orWhere('init_grade', null)
            ->orWhere('grade', null)
            ->orWhere('price', null)
            ->orwhere('price_init_season_id', null);
        }
      );
  }


  // PlateCard::whereNotIn('price_init_season_id', Season::currentSeasons(true)->pluck('id')) // 가격 초기화 시즌이 현재시즌인지
  //   ->orWhere('price', null) // 가격이 없음(현재 트랜잭션에서 insert로 들어온 카드)
  //   ->get()
  //   ->map(function ($plateCard) {
  //   }); // 가격이 아직 설정되지 않은지

  public function scopeCurrentSeason($query, $active = true)
  {
    return $query->whereHas('season', function ($innerQuery) use ($active) {
      $innerQuery->currentSeasons($active);
    });
  }

  public function scopeEtcFilters($query, $input)
  {
    return $query->withTrashed()
      ->where(function ($curseason) {
        $curseason->currentSeason(false);
      })->orWhere(function ($innerQuery) use ($input) {
        $innerQuery->onlyTrashed()->where('league_id', $input['league']); // none service league
      })->orWhere(function ($innerQuery) {
        $innerQuery->withoutTrashed()->whereHas('league', function ($lQuery) {
          $lQuery->where('status', '!=', LeagueStatusType::SHOW);
        });
      });
  }

  public function scopeEtcFilters2($query, $etc, $input)
  {
    return $query->withTrashed()
      ->when($etc, function ($etcQuery) {
        $etcQuery->where(function ($isonsale) {
          $isonsale->isOnSale(false)
            ->orWhere(function ($curseason) {
              $curseason->currentSeason(false);
            })->orWhere(function ($innerQuery) {
              $innerQuery->whereNotNull('deleted_at');
            });
        });
      }, function ($query) use ($input) {
        $query->currentSeason()
          ->when($input['club'], function ($innerQuery, $clubs) {
            $innerQuery->whereIn('team_id', $clubs);
          })
          ->when($input['player_name'], function ($innerQuery, $name) {
            $innerQuery->nameFilterWhere($name);
          });
      });
  }

  // 사용X
  public function scopeApplyFiltersA(Builder $query, $input)
  {
    return $query->withTrashed()
      ->when($input['league'], function ($query, $league) {
        $query->where('league_id', $league);
      })
      ->when($input['other'], function ($innerQuery) {
        $innerQuery->currentSeason(false);
      }, function (Builder $innerQuery) use ($input) {
        $innerQuery->currentSeason(true)
          ->when($input['club'], function ($innerQuery, $clubs) {
            $innerQuery->whereIn('team_id', $clubs);
          });
      })
      ->when($input['player_name'], function ($query, $name) {
        $query->nameFilterWhere($name);
      });
  }

  public function scopeApplyFilters(Builder $query, $input)
  {
    return $query->currentSeason(true)
      ->when($input['league'], function ($query, $league) {
        $query->where('league_id', $league);
      })
      ->when($input['club'], function ($innerQuery, $clubs) {
        $innerQuery->whereIn('team_id', $clubs);
      })
      ->when($input['position'], function ($query, $positions) {
        $query->whereIn('position', $positions);
      })
      ->when($input['player_name'], function ($query, $name) {
        $query->nameFilterWhere($name);
      });
  }

  public function scopeNameFilterWhere(Builder $query, $name)
  {
    $name = addslashes($name);
    $splitWords = explode(' ', $name);
    return $query->where(function ($likeQuery) use ($name, $splitWords) {
      foreach ($splitWords as $word) {
        $likeQuery->whereLike([
          'first_name',
          'last_name',
          'short_first_name',
          'short_last_name'
        ], $word, 'right');
      }
      $likeQuery->orWhere(function ($orWhere) use ($name) {
        $name = preg_replace('/\.\s*/', '. ', $name);
        $orWhere->whereLike(['match_name'], $name);
      })->orWhere(function ($orWhere) use ($name) {
        $orWhere->whereRaw("concat_ws(' ', first_name, last_name) like '{$name}%'");
      })->orWhere(function ($orWhere) use ($name) {
        $orWhere->whereRaw("concat_ws(' ', short_first_name, short_last_name) like '{$name}%'");
      });
    });
  }
  public function scopeNameFilterSelect(Builder $query, $name)
  {
    $name = addslashes($name);
    return $query->selectRaw("
      *,
        (CASE WHEN concat_ws(' ', short_first_name, short_last_name) = ? OR concat_ws(' ', first_name, last_name) = ? OR concat_ws(' ', first_name_eng, last_name_eng) = ? THEN 4
        WHEN match_name = ? OR match_name_eng = ? THEN 3
        WHEN concat_ws(' ', first_name, last_name) = ? OR concat_ws(' ', short_first_name, short_last_name) = ? THEN 2
        WHEN short_first_name = ? OR short_last_name = ? THEN 1
        ELSE 0 END) as match_score
      ", [$name, $name, $name, $name, $name, $name, $name, $name, $name])
      ->orderByDesc('match_score');

    // 검색어 조건 변경 및 성능 개선 작업
    // return $query->where(function ($query) use ($name) {
    //   $query->whereLike([
    //     'first_name',
    //     'last_name',
    //     'short_first_name',
    //     'short_last_name'
    //   ], $name, 'right')
    //     ->orWhere(function ($orWhere) use ($name) {
    //       $name = preg_replace('/\.\s*/', '. ', $name);
    //       $orWhere->whereLike(['match_name'], $name);
    //     })->orWhere(function ($orWhere) use ($name) {
    //       $orWhere->whereRaw("concat_ws(' ', first_name, last_name) like '{$name}%'");
    //     })->orWhere(function ($orWhere) use ($name) {
    //       $orWhere->whereRaw("concat_ws(' ', first_name_eng, last_name_eng) like '{$name}%'");
    //     })->orWhere(function ($orWhere) use ($name) {
    //       $orWhere->whereRaw("concat_ws(' ', short_first_name, short_last_name) like '{$name}%'");
    //     });
    // })->orderByRaw("CASE WHEN concat_ws(' ', short_first_name, short_last_name) = '{$name}' OR concat_ws(' ', first_name, last_name) = '{$name}' OR concat_ws(' ', first_name_eng, last_name_eng) = '{$name}' THEN 0
    // WHEN match_name = '{$name}' OR match_name_eng = '{$name}' THEN 1
    // WHEN first_name = '{$name}' OR last_name = '{$name}' OR first_name_eng = '{$name}' OR last_name_eng = '{$name}' THEN 2
    // WHEN short_first_name = '{$name}' OR short_last_name = '{$name}' THEN 3
    // ELSE 4 END");
  }

  public function scopeIsOnSale(Builder $query, bool $_isOnsale = true)
  {
    /**
     * 현재 시즌의 카드 내에서
     */
    // ->whereNotIn('id', PlateCardFailLog::where('done', false)->whereNotNull('plate_card_id')->pluck('plate_card_id')) // fail log 처리가 안된 카드는 초기가격 세팅에서 제외되도록 설정.(when, where 순서 주의);
    // 아래 코드로 변경
    return $query->has('refPlayerOverall')->whereDoesntHave('plateCardFailLog', function ($innerQuery) {
      $innerQuery->where('done', false);
    })->has('league')
      ->isPriceSet($_isOnsale) // 현재 시즌 조건 포함되어 있음.
      ->where('on_sale_manual', true); // (수동으로 설정한) 판매 여부 검사
  }
}
