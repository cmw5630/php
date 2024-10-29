<?php

namespace App\Http\Controllers\API\v1;

use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Player\PlayerType;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\Opta\YesNo;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Stat\StatListRequest;
use App\Http\Requests\Api\Stat\TeamDetailRequest;
use App\Http\Requests\Api\Stat\TeamDetailSeasonRequest;
use App\Http\Requests\Api\Stat\TeamVoteRequest;
use App\Libraries\Classes\Exception;
use App\Libraries\Traits\PlayerTrait;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\data\SeasonTeam;
use App\Models\data\Squad;
use App\Models\data\Substitution;
use App\Models\data\Team;
use App\Models\data\Transfer;
use App\Models\log\ScheduleVote;
use App\Services\Data\DataService;
use App\Services\Data\StatService;
use App\Services\Game\DraftService;
use DB;
use ReturnData;
use Str;
use Symfony\Component\HttpFoundation\Response;

class StatController extends Controller
{
  use PlayerTrait;

  private StatService $statService;
  private DraftService $draftService;
  private DataService $dataService;

  public function __construct(
    StatService $_statService,
    DraftService $_draftService,
    DataService $_dataService
  ) {
    $this->statService = $_statService;
    $this->draftService = $_draftService;
    $this->dataService = $_dataService;
  }

  public function list(StatListRequest $request)
  {
    $input = $request->only([
      'type',
      'league',
      'season',
      'team',
      'category',
      'position',
      'mode',
      'sort',
      'order',
      'page',
      'player_id',
      'q',
    ]);

    // $result['filters']['season'] = Season::query()
    //   ->select('id', 'name')
    //   ->where('league_id', $input['league'])
    //   ->latest('start_date')
    //   ->get()
    //   ->toArray();
    $seasons = Season::with('league:id,league_code')->getBeforeFuture(
      [SeasonWhenType::BEFORE],
      $input['league']
    )[$input['league']];

    $currentSeason = $seasons['current'];
    $result['filters']['season'][] = [
      'id' => $currentSeason['id'],
      'name' => $currentSeason['name']
    ];
    foreach ($seasons['before'] as $item) {
      $result['filters']['season'][] = ['id' => $item['id'], 'name' => $item['name']];
    }

    try {
      $result = array_merge(
        $result,
        $this->statService->{'get' . Str::ucfirst($input['type']) . 'List'}(
          $input,
          $seasons['current']['league']['league_code']
        )
      );

      return ReturnData::setData($result, $request)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function teamDetailTop(TeamDetailRequest $request)
  {
    $input = $request->only([
      'team_id'
    ]);
    try {
      $result = [];

      $teamInfo = Team::whereId($input['team_id'])->first();

      $nextMatch = Schedule::with([
        'home:id,code,name,short_name,city',
        'away:id,code,name,short_name',
        'scheduleVote:schedule_id,home_vote,away_vote'
      ])->has('league')
        ->where(function ($query) use ($input) {
          $query->where('home_team_id', $input['team_id'])
            ->orWhere('away_team_id', $input['team_id']);
        })->where('status', ScheduleStatus::FIXTURE)
        ->whereNotNull('ga_round')
        ->select('id', 'league_id', 'home_team_id', 'away_team_id', 'started_at', 'status')
        ->orderBy('ga_round')
        ->first()?->toArray();

      $result['name']['official_name'] = $teamInfo->official_name;
      foreach (config('commonFields.team') as $column) {
        $result['name'][$column] = $teamInfo->$column;
      }

      $leagueId = null;
      if (!is_null($nextMatch)) $leagueId = $nextMatch['league_id'];
      $result['league_id'] = $leagueId;

      $result['address']['city'] = $teamInfo->city;
      $result['address']['postal'] = $teamInfo->postal_address;
      $result['address']['zip'] = $teamInfo->adress_zip;
      $result['stadium'] = $teamInfo->venue_name;
      $result['founded'] = $teamInfo->founded;
      $result['capacity'] = $teamInfo->capacity;
      // Todo : team web site / SNS
      $result['web_site'] = null;
      $result['sns'] = [];

      $result['next_match'] = $nextMatch;


      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function teamDetail(TeamDetailRequest $request)
  {
    $input = $request->only([
      'team_id'
    ]);
    try {
      $result = $this->statService->getTeamDetail($input);
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function teamVote(TeamVoteRequest $request)
  {
    $input = $request->only([
      'schedule_id',
      'vote',
      'count'
    ]);
    try {
      ScheduleVote::updateOrCreate([
        'schedule_id' => $input['schedule_id']
      ], [
        $input['vote'] . '_vote' => DB::raw($input['vote'] . '_vote+' . $input['count'])
      ]);

      $result = ScheduleVote::where('schedule_id', $input['schedule_id'])->select('schedule_id', 'home_vote', 'away_vote')->first()->toArray();

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function teamDetailStats(TeamDetailSeasonRequest $request)
  {
    $input = $request->only([
      'team_id',
      'season'
    ]);
    try {
      $result = $this->statService->getTeamDetailStats($input);
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function teamDetailSchedules(TeamDetailSeasonRequest $request)
  {
    $input = $request->only([
      'team_id',
      'season'
    ]);
    try {
      $result = $this->statService->getTeamDetailSchedules($input);
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function teamDetailView($_scheduleId)
  {
    try {
      $scheduleInfo = Schedule::whereId($_scheduleId)
        ->with([
          'home:id,code,name,short_name,city,venue_name AS stadium',
          'away:id,code,name,short_name'
        ])
        ->has('home')
        ->has('away')
        ->select('id', 'season_id', 'round', 'home_team_id', 'away_team_id', 'status', 'started_at', 'score_home', 'score_away', 'winner')
        ->first()
        ->toArray();

      $result['schedule_info'] = $scheduleInfo;

      $players = [];
      OptaPlayerDailyStat::with('plateCardWithTrashed:player_id,' . implode(',', config('commonFields.player')))
        ->gameParticipantPlayer()
        ->where([
          ['schedule_id', $_scheduleId],
          ['mins_played', '>', 0]
        ])
        ->selectRaw('schedule_id, player_id, team_id, fantasy_point, goals, goal_assist, position, summary_position')
        ->get()
        ->map(function ($info) use ($scheduleInfo, &$players) {
          $player['player_id'] = $info->player_id;
          $player['team_id'] = $info->team_id;
          $player['fantasy_point'] = $info->fantasy_point;
          $player['goals'] = $info->goals;
          $player['goal_assist'] = $info->goal_assist;
          $player['position'] = $info->position;
          $player['summary_position'] = $info->summary_position;

          foreach ($info->plateCardWithTrashed->toArray() as $key => $value) {
            $player[$key] = $value;
          }

          if ($info->position === 'Substitute') {
            $player['position_order'] = 4;
          } else {
            $player['position_order'] = config('constant.LINEUP_POSITION_ORDER')[$info->summary_position];
          }

          $sub = Substitution::where('schedule_id', $scheduleInfo['id'])
            ->where(function ($query) use ($info) {
              $query->where('player_on_id', $info->player_id)
                ->orWhere('player_off_id', $info->player_id);
            })->get()?->toArray();

          $substitute = [];
          for ($i = 0; $i < count($sub); $i++) {
            foreach (['on', 'off'] as $type) {
              if ($sub[$i]['player_' . $type . '_id'] === $info->player_id) {
                array_push($substitute, ($type == 'on') ? 'In' : 'Out');
              }
            }
            $player['substitution'] = $substitute;
          }

          if ($info->team_id === $scheduleInfo['home_team_id']) {
            $players['home'][$info->player_id] = $player;
          } else if ($info->team_id === $scheduleInfo['away_team_id']) {
            $players['away'][$info->player_id] = $player;
          }

          return $players;
        });

      if (!empty($players)) {
        $home = __sortByKeys($players['home'], ['keys' => ['position_order', 'player_name'], 'hows' => ['ASC', 'ASC']]);
        $away = __sortByKeys($players['away'], ['keys' => ['position_order', 'player_name'], 'hows' => ['ASC', 'ASC']]);

        $result['players']['home'] = array_values($home);
        $result['players']['away'] = array_values($away);
      }


      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function teamDetailSquad(TeamDetailRequest $request)
  {
    $input = $request->only([
      'team_id',
    ]);

    try {
      $result = [
        PlayerPosition::GOALKEEPER => [],
        PlayerPosition::DEFENDER => [],
        PlayerPosition::MIDFIELDER => [],
        PlayerPosition::ATTACKER => []
      ];

      // 해당 팀의 현재 시즌
      $currentSeasonId = SeasonTeam::whereHas('season', function ($query) {
        $query->currentSeasons();
      })->where('team_id', $input['team_id'])
        ->value('season_id');

      Squad::withTrashed()
        ->has('league')
        ->where([
          ['season_id', $currentSeasonId],
          ['team_id', $input['team_id']],
          ['type', PlayerType::PLAYER],
          ['active', YesNo::YES]
        ])->with([
          'plateCardWithTrashed:id,player_id,headshot_path',
          'countryCode:nationality_id,alpha_3_code',
          'suspension' => function ($suspension) use ($currentSeasonId) {
            $suspension->where([
              ['season_id', $currentSeasonId],
              ['suspension_end_date', '>', now()]
            ])->selectRaw('player_id, suspension_start_date AS start_date, suspension_end_date AS end_date, description');
          },
          'injury' => function ($injury) use ($currentSeasonId) {
            $injury->where('season_id', $currentSeasonId)
              ->where(function ($query) {
                $query->whereNull('injury_end_date')
                  ->orWhere('expected_end_date', '>', now());
              })->selectRaw('player_id, injury_start_date AS start_date, expected_end_date AS end_date, injury_type');
          }
        ])->has('plateCardWithTrashed')
        ->get()
        ->map(function ($info) use (&$result) {
          $player['plate_card_id'] = $info->plateCardWithTrashed->id;
          $player['player_id'] = $info->player_id;
          foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
            $player['name'][$field] = $info->$field;
          }
          $player['shirt_number'] = $info->shirt_number;
          $player['headshot_path'] = $info->plateCardWithTrashed->headshot_path;
          $player['nationality_id'] = $info->nationality_id;
          $player['nationality'] = $info->nationality;
          $player['nation_code'] = $info->countryCode->alpha_3_code;

          $player['suspension'] = $info?->suspension->first();
          $player['injury'] = $info?->injury->first();

          $result[$info->position][] = $player;
        });

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function teamDetailTransfer(TeamDetailRequest $request)
  {
    $input = $request->only([
      'team_id'
    ]);
    try {
      // 전시즌 종료 이후 ~ 현시즌 종료까지
      $leagueInfo = $this->statService->getAllSeasonsFromTeam($input['team_id'])['league'];
      $seasonList = Season::with('league:id,league_code')->getBeforeFuture(
        [SeasonWhenType::BEFORE],
        $leagueInfo['id']
      )[$leagueInfo['id']];

      $result['season'] = [
        'league_id' => $leagueInfo['id'],
        'league_code' => $leagueInfo['league_code'],
        'season_id' => $seasonList['current']['id'],
        'season_name' => $seasonList['current']['name'],
      ];

      $result['player'] = array_values(Transfer::with([
        'plateCardWithTrashed:player_id,headshot_path,' . implode(',', config('commonFields.player')),
        'player:id,date_of_birth',
        'team:' . implode(',', config('commonFields.team')),
        'fromTeam:' . implode(',', config('commonFields.team'))
      ])
        ->has('plateCardWithTrashed')
        ->where(function ($query) use ($input) {
          $query->where('team_id', $input['team_id'])
            ->orWhere('from_team_id', $input['team_id']);
        })->where([
          ['player_type', PlayerType::PLAYER],
          ['active', YesNo::YES],
        ])->whereBetween('membership_start_date', [$seasonList['before'][0]['end_date'], $seasonList['current']['end_date']])
        ->selectRaw('player_id, player_position, membership_start_date, membership_end_date, team_id, team_name, transfer_type, value, currency, from_team_id, from_team_name, IF(team_id="' . $input['team_id'] . '","In","Out") AS status')
        ->get()
        ->sortBy('plateCardWithTrashed.player_name')
        ->sortBy('membership_start_date')
        ->toArray());

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }
}
