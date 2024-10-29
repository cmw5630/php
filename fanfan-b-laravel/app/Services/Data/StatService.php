<?php

namespace App\Services\Data;

use App\Enums\FantasyCalculator\OrderType;
use App\Enums\Opta\League\LeagueStatusType;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\StatCategory;
use App\Libraries\Classes\Exception;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\OptaPlayerSeasonStat;
use App\Models\data\OptaTeamSeasonStat;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\data\SeasonTeam;
use App\Models\data\Team;
use App\Models\game\GameLineup;
use App\Models\game\PlateCard;
use App\Models\log\PlateCardUserLikeLog;
use App\Models\meta\RefTeamCurrentMeta;
use App\Services\Game\DraftService;
use DB;
use Illuminate\Contracts\Auth\Authenticatable;
use Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

interface StatServiceInterface
{
  public function getTeamList($_filter, $_leagueCode);
  public function getPlayerList($_filter);
  public function getSeasonStatSummary($_playerId, $_seasonId = null);
  public function getTeamDetail($_data);
  public function getTeamDetailStats($_data);
  public function getTeamDetailSchedules($_data);
}
class StatService implements StatServiceInterface
{
  protected ?Authenticatable $user;
  protected $limit;
  protected DraftService $draftService;

  public function __construct(?Authenticatable $_user, DraftService $_draftService)
  {
    $this->user = $_user;
    $this->limit = 20;
    $this->draftService = $_draftService;
  }

  private function categoryFields($_type, $_category)
  {
    if (Str::endsWith($_type, '_per')) {
      return array_map(
        fn($val) => $val === 'games_played' ? $val : $val . '_per',
        config('stats.categories')[$_type][$_category]
      );
    }

    return config('stats.categories')[$_type][$_category];
  }

  public function getTeamList($_filter, $_leagueCode)
  {
    if ($_filter['category'] === StatCategory::SUMMARY && $_filter['sort'] === 'points') {
      $columns = [
        'team_id',
        'REVERSE(substr(last_six, 1, 5)) as last_five',
        '`rank` as num'
      ];
      $sort = 'rank';
      $orderBy = $_filter['order'] === OrderType::ASC ? OrderType::DESC : OrderType::ASC;
    } else {
      $columns = [
        'team_id',
        'REVERSE(substr(last_six, 1, 5)) as last_five',
        'rank() over (order by  ' . $_filter['sort'] . ' ' . $_filter['order'] . ') as num',
      ];
      $sort = 'num';
      $orderBy = OrderType::ASC;
    }

    try {
      $data = OptaTeamSeasonStat::query()
        ->with('team:id,short_name')
        ->selectRaw(implode(', ', array_merge($columns, $this->categoryFields('team', $_filter['category']))))
        ->where('season_id', $_filter['season'])
        ->orderBy($sort, $orderBy)
        ->paginate($this->limit, ['*'], 'page', $_filter['page'])
        ->toArray();

      if ($_filter['category'] === StatCategory::SUMMARY && $_filter['sort'] === 'points' && $orderBy === OrderType::DESC) {
        foreach ($data['data'] as &$item) {
          $item['num'] = (count($data['data']) + 1) - $item['num'];
        }
      }

      return __setPaginateData($data, []);
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function getPlayerList($_filter)
  {
    $type = $_filter['type'];
    $sort = $_filter['sort'];
    if ($_filter['mode'] === 'per') {
      $type .= '_' . $_filter['mode'];
      $sort .= '_' . $_filter['mode'];
    }

    try {
      $plateCardQuery = PlateCard::withTrashed()
        ->when($_filter['q'], function ($searchQuery, $q) {
          $searchQuery->nameFilterWhere($q);
        })
        ->when($_filter['player_id'], function ($searchQuery, $playerId) {
          $searchQuery->where('player_id', $playerId);
        })
        ->when($_filter['position'], function ($searchQuery, $position) {
          $searchQuery->whereIn('position', $position);
        })
        ->when($_filter['team'], function ($searchQuery, $team) {
          $searchQuery->whereIn('team_id', $team);
        })
        ->whereHas('season', function ($seasonQuery) {
          $seasonQuery->currentSeasons()->whereHas('league', function ($leagueQuery) {
            $leagueQuery->where('status', LeagueStatusType::SHOW);
          });
        })
        ->select([
          'team_id as tid',
          'season_id',
          'player_id',
          'position',
          'headshot_path',
          'match_name',
          'id as plate_card_id'
        ]);

      $opssTbl = OptaPlayerSeasonStat::getModel()->getTable();
      $columns = [
        'plate_card.player_id',
        'team_id',
        'mins_played',
        'rank() over (order by ' . $sort . ' ' . $_filter['order'] . ') as num',
        'position',
        'headshot_path',
        'match_name',
        'player_name',
        'player_name_eng',
        'short_player_name',
        'plate_card_id',
        DB::raw("IFNULL({$opssTbl}.team_id, plate_card.tid) as team_id"),
      ];

      $data = tap(
        OptaPlayerSeasonStat::query()
          ->with([
            'team:id,name',
          ])
          ->rightJoinSub($plateCardQuery, 'plate_card', function ($join) use ($opssTbl) {
            $join->on($opssTbl . '.player_id', '=', 'plate_card.player_id')
            ->on($opssTbl.'.season_id', '=', 'plate_card.season_id');
          })
          ->selectRaw(implode(', ',
            array_merge($columns, $this->categoryFields($type, $_filter['category']))))
          ->where('plate_card.season_id', $_filter['season'])
          ->orderBy('num')
          ->paginate($this->limit, ['*'], 'page', $_filter['page'])
      )
        ->map(function ($info) use ($_filter) {
          if ($_filter['mode'] === 'per') {
            foreach ($this->categoryFields('player', $_filter['category']) as $field) {
              if (isset($info->{$field . '_per'})) {
                $info->{$field} = round($info->{$field . '_per'}, 2);
                unset($info->{$field . '_per'});
              } else {
                if ($field !== 'games_played') {
                  $info->{$field} = null;
                }
              }
            }
          }

          $plateCard = collect();
          $plateCardColumns = [
            'plate_card_id',
            'match_name',
            'player_name',
            'player_name_eng',
            'short_player_name',
            'position',
            'headshot_path',
          ];

          foreach ($plateCardColumns as $column) {
            if (isset($info->$column)) {
              $temp = $column;
              if ($column === 'plate_card_id') {
                $temp = 'id';
              }
              $plateCard->put($temp, $info->$column);
              unset($info->$column);
            }
          }

          $info->plate_card = $plateCard;
          return $info;
        })
        ->toArray();

      return __setPaginateData($data, []);
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function getSeasonStatSummary($_playerId, $_seasonId = null)
  {
    if (is_null($_seasonId)) {
      $seasonId = PlateCard::where('player_id', $_playerId)->value('season_id');
    } else {
      $seasonId = $_seasonId;
    }

    $seasonStat = OptaPlayerSeasonStat::where([
      'season_id' => $seasonId,
      'player_id' => $_playerId,
    ])
      ->select([
        'season_id',
        'games_played as matches',
        'rating as ratings',
        'goals',
        'goal_assists as assists',
        'clean_sheets',
        'saves_made as saves'
      ])
      ->first();
    $seasonStatArray = $seasonStat?->toArray();
    $result = $seasonStat?->season->toArray();
    $result['stat'] = $seasonStatArray;

    return $result;
  }

  public function getTeamDetail($_data)
  {
    $list = [];

    $teamId = $_data['team_id'];

    // Todo : team descript
    $list['descript'] = 'abacdscsefdscsc';

    $list['last_5_match'] = array_reverse(Schedule::with([
      'home:id,code,name,short_name,city',
      'away:id,code,name,short_name'
    ])->where(function ($query) use ($teamId) {
      $query->where('home_team_id', $teamId)
        ->orWhere('away_team_id', $teamId);
    })->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
      ->select('id', 'home_team_id', 'away_team_id', 'started_at', 'round', 'status', 'score_home', 'score_away', 'winner')
      ->orderByDesc('started_at')
      ->limit(5)
      ->get()
      ->toArray());

    // 이번시즌 시작 전이라면 직전 시즌
    // 현재 시즌
    $seasonInfo = SeasonTeam::where('team_id', $teamId)->currentSeason()
      ->withWhereHas('season.league', function ($query) {
        $query->whereNot('id', config('constant.LEAGUE_CODE.UCL'));
      })->select('season_id')->first()?->toarray();

    $seasonId = $seasonInfo['season_id'];
    $leagueId = $seasonInfo['season']['league']['id'];
    if (!OptaPlayerSeasonStat::where([['team_id', $teamId], ['season_id', $seasonId]])->exists()) {
      $seasonId = Season::getBeforeFuture([SeasonWhenType::BEFORE], $leagueId)[$leagueId]['before'][0];
    }

    $list['main_formation'] = null;
    $teamMainFormations = RefTeamCurrentMeta::where([
      ['team_id', $teamId],
      ['season_id', $seasonId]
    ])->first()?->toArray();
    if (!is_null($teamMainFormations)) {
      $list['main_formation'] = Team::whereId($teamId)->value('color');
      $list['main_formation']['formation_used'] = $teamMainFormations['main_formation_used'];
      $representativePlayers = $teamMainFormations['representative_player'];

      $players = [];
      if (!is_null($representativePlayers)) {
        foreach ($representativePlayers as $formation => $playerId) {
          PlateCard::withTrashed()
            ->with('refTeamFormationMap:player_id,fantasy_point_per')
            ->where('player_id', $playerId)
            ->selectRaw('player_id,shirt_number,' . implode(',', config('commonFields.player')))
            ->get()
            ->map(function ($info) use (&$players, $formation) {
              $player['formation_place'] = $formation;
              $player['id'] = $info->player_id;
              foreach (config('commonFields.player') as $column) {
                $player[$column] = $info->$column;
              }
              $player['shirt_number'] = $info->shirt_number;
              $player['fantasy_point'] = $info->refTeamFormationMap->fantasy_point_per;
              $players[] = $player;
              return $players;
            });
        }
      }
      $list['main_formation']['players'] = $players;
    }

    $maxRound = Schedule::where('season_id', $seasonId)
      ->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
      ->orderByDesc('round')
      ->value('round');

    $standard = 90 * $maxRound * 0.15;

    // fantasy_point_per 구해서 높은 5명 구하기
    $sub = OptaPlayerDailyStat::where([
      ['team_id', $teamId],
      ['season_id', $seasonId],
      ['formation_place', '>', 0]
    ])->selectRaw('player_id, ROUND(SUM(fantasy_point) / ROUND(SUM(mins_played) / 90, 5),1) AS fantasy_point_per, ROUND(AVG(rating),1) AS rating, SUM(mins_played) AS sum_played')
      ->groupBy('player_id');

    $list['best_player'] = DB::query()->fromSub($sub, 'sub')->select('player_id', 'fantasy_point_per', 'rating')
      ->where('sum_played', '>=', $standard)
      ->orderByDesc('fantasy_point_per')
      ->orderByDesc('rating')
      ->limit(5)
      ->get()
      ->map(function ($_info) {
        $info = (array) $_info;
        $info['plate_card'] = PlateCard::where('player_id', $info['player_id'])
          ->selectRaw('id,player_id,headshot_path,shirt_number,position,' . implode(',', config('commonFields.player')))->first()?->toArray();

        return $info;
      })->toArray();

    $list['best_player'] = __sortByKeys($list['best_player'], ['keys' => ['fantasy_point_per', 'rating', 'first_name'], 'hows' => ['desc', 'desc', 'asc']]);

    return $list;
  }

  public function getTeamDetailStats($_data)
  {
    $teamId = $_data['team_id'];
    $seasonId = $_data['season'];

    // $list = $this->getAllSeasonsFromTeam($teamId);
    // if (!in_array($seasonId, array_column($list['season_list'], 'id'))) {
    //   $seasonId = $list['season_list'][0]['id'];
    // }
    // TODO : 프론트에서 전달하는 season_id와 싱크가 안맞을 수 있음
    // 해당 팀의 최근 시즌 찾기
    /*if (!Schedule::where('season_id', $seasonId)
      ->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
      ->where(function ($query) use ($teamId) {
        $query->where('home_team_id', $teamId)
          ->orwhere('away_team_id', $teamId);
      })->exists()) {
      $seasonId = Schedule::whereHas('optaTeamDailyStat', function ($query) use ($teamId) {
        $query->where('team_id', $teamId);
      })->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
        ->orderByDesc('started_at')
        ->value('season_id');
    }*/

    // optaTeamSeasonStat 기본 쿼리 생성
    $optaTeamSeasonStatQuery = OptaTeamSeasonStat::where('team_id', $teamId);

    // 클론하여 첫 번째 쿼리 실행
    $optaTeamSeasonStatQuery->clone()->where('season_id', $seasonId)
      ->get()
      ->map(function ($info) use (&$list) {
        if (empty($list['stats'])) {
          $list['rank'] = $info->rank;
          $list['matches']['total'] = $info->matches_won + $info->matches_lost + $info->matches_drawn;
          $list['matches']['won'] = $info->matches_won;
          $list['matches']['lost'] = $info->matches_lost;
          $list['matches']['drawn'] = $info->matches_drawn;
          $list['points'] = $info->points;
          $list['goals'] = $info->goals;
          $list['goals_conceded'] = $info->goals_conceded;
          $list['rating'] = $info->rating;

          if ($info->season && $info->season->league) {
            $list['league'] = [
              'id' => $info->season->league->id,
              'name' => $info->season->league->name,
              'league_code' => $info->season->league->league_code,
            ];
          }
        }
        foreach (config('stats.categories.team') as $category => $columns) {
          if ($category != StatCategory::SUMMARY) {
            foreach ($columns as $column) {
              $list['stats'][$category][$column] = $info->$column;
            }
          }
        }
      });

    $allSeasons = $optaTeamSeasonStatQuery->clone()->with('season:id,name,active')
      ->get()
      ->map(function ($info) {
        return [
          'id' => $info->season->id,
          'name' => $info->season->name,
          'active' => $info->season->active,
        ];
      })
      ->toArray();

    // 전체 시즌 리스트를 season_list에 추가
    $list['season_list'] = $allSeasons;

    return $list;
  }

  public function getTeamDetailSchedules($_data)
  {
    $teamId = $_data['team_id'];
    $seasonId = $_data['season'];

    $list = $this->getAllSeasonsFromTeam($teamId);

    $myGameJoins = GameLineup::whereHas('userPlateCard', function ($query) {
      $query->where('user_id', $this->user->id);
    })->distinct()->pluck('schedule_id')->toArray();

    $tops = [];
    $schedules = Schedule::where('season_id', $seasonId)
      ->with([
        'home:id,code,name,short_name,city,venue_name AS stadium',
        'away:id,code,name,short_name',
        'season:id,name',
        'gamePossibleSchedule.gameSchedule'
      ])
      ->has('home')
      ->has('away')
      ->where(function ($query) use ($teamId) {
        $query->where('home_team_id', $teamId)
          ->orWhere('away_team_id', $teamId);
      })->select('id', 'season_id', 'round', 'home_team_id', 'away_team_id', 'status', 'started_at', 'score_home', 'score_away', 'winner')
      ->orderBy('round')
      ->get()
      ->map(function ($info) use ($myGameJoins, &$tops) {
        $info->game_participant = in_array($info->id, $myGameJoins);
        $info->game_count = $info->gamePossibleSchedule?->gameSchedule->count();
        // if (($info->status === ScheduleStatus::FIXTURE || $info->status === ScheduleStatus::PLAYING) && count($tops) < 3) {
        //   $tops[] = $info;
        // }

        return $info;
      });

    // 프론트에서 계산하기로 했으나 혹시 몰라 주석처리.
    // $list['tops'] = $tops;
    $list['schedules'] = $schedules;

    return $list;
  }

  public function getAllSeasonsFromTeam($_teamId)
  {
    $leagueInfo = Season::whereHas('seasonTeam', function ($query) use ($_teamId) {
      $query->where('team_id', $_teamId);
    })->with('league:id,league_code')
      ->orderByDesc('start_date')
      ->first()?->toArray();

    $list['league'] = $leagueInfo['league'];
    $list['season_list'] = Season::where([
      ['league_id', $leagueInfo['league_id']],
      ['start_date', '<', now()]
    ])->select('id', 'name', 'active')
      ->orderByDesc('start_date')
      ->get()
      ->toArray();

    return $list;
  }

  public function upsertPlateCardUserLikeLog($_plateCardId): array
  {
    $likeLog = $this->plateCardLikeMyLog($_plateCardId, true);

    if (is_null($likeLog)) { //첫 좋아요의 경우
      $likeLog = new PlateCardUserLikeLog();
      $likeLog->user_id = $this->user->id;
      $likeLog->plate_card_id = $_plateCardId;
      $likeLog->save();
    } else {
      if (!is_null($likeLog->deleted_at)) { //다시 좋아요
        $likeLog->restore();
      } else {
        $likeLog->delete();
      }
    }

    return [
      'is_like' => is_null($likeLog->deleted_at),
      'total_count' => $this->countPlateCardLikes($_plateCardId),
    ];
  }

  public function plateCardLikeMyLog($_plateCardId, bool $_withTrashed = false)
  {
    return PlateCardUserLikeLog::when($_withTrashed, function ($query) {
      $query->withTrashed();
    })
      ->where([
        ['user_id', $this->user->id],
        ['plate_card_id', $_plateCardId]
      ])
      ->first();
  }

  public function countPlateCardLikes($_plateCardId)
  {
    return PlateCardUserLikeLog::where('plate_card_id', $_plateCardId)->get()->count();
  }
}
