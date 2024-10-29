<?php

namespace App\Services\Game;

use App\Enums\AuctionBidStatus;
use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\FantasyCalculator\FantasyPointCategoryType;
use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Card\DraftCardStatus;
use App\Enums\Opta\Card\PlateCardStatus;
use App\Enums\Opta\Career\MembershipType;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\Opta\Team\TeamType;
use App\Enums\Opta\YesNo;
use App\Enums\PlateCardActionType;
use App\Enums\PlayerStrengthType;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StatCategory;
use App\Libraries\Classes\Exception;
use App\Libraries\Classes\FantasyCalculator;
use App\Libraries\Traits\CommonTrait;
use App\Libraries\Traits\DraftTrait;
use App\Libraries\Traits\LogTrait;
use App\Libraries\Traits\PlayerTrait;
use App\Models\data\Injuries;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\OptaPlayerSeasonStat;
use App\Models\data\OptaTeamSeasonStat;
use App\Models\data\PlayerCareer;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\data\Suspension;
use App\Models\game\Auction;
use App\Models\game\DraftSelection;
use App\Models\game\GameJoin;
use App\Models\game\GameLineup;
use App\Models\game\GameSchedule;
use App\Models\game\PlateCard;
use App\Models\game\Player;
use App\Models\game\PlayerDailyStat;
use App\Models\log\DraftLog;
use App\Models\log\PlateCardDailyAction;
use App\Models\meta\RefAvgFp;
use App\Models\meta\RefCountryCode;
use App\Models\meta\RefPlayerCurrentMeta;
use App\Models\meta\RefPlayerOverallHistory;
use App\Models\meta\RefPlayerSeasonStrengths;
use App\Models\order\DraftOrder;
use App\Models\order\OrderPlateCard;
use App\Models\PlayerSeasonRankingView;
use App\Models\user\UserPlateCard;
use DB;
use Illuminate\Contracts\Auth\Authenticatable;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

interface DraftServiceInterface
{
  public function getPlateCardList($input);
  public function getPlayerInfo($_data);
  public function getPlayerDetail($_data);
  public function playerDetailStats($_data);
  public function getUserCardsByLeague($input);
  public function getUserCardsCountByGrade($input);
  public function getRoundsBySeason($leagueQuery);
  public function getUserCardsHistory($input);
}

class DraftService implements DraftServiceInterface
{
  use LogTrait, CommonTrait, PlayerTrait, DraftTrait;

  protected ?Authenticatable $user;
  protected int $limit;

  public function __construct(?Authenticatable $_user)
  {
    $this->user = $_user;
    $this->limit = 35;
  }

  public function getPlateCardList($input)
  {
    try {
      $sub = PlateCard::isOnsale()
        ->has('currentRefPlayerOverall')
        ->applyFilters($input)
        ->selectRaw('id, plate_cards.player_id, league_id, season_id, team_id, price, ' . implode(
          ',',
          config('commonFields.player')
        ) . ',position, headshot_path, team_code,ROW_NUMBER() OVER(ORDER BY first_name, last_name) AS rnum');

      $totalCount = DB::query()->fromSub($sub, 'sub')->max('rnum');

      $this->limit = $input['per_page'];

      $userCardList = $input['userCards'];

      $plateCardTblName = PlateCard::getModel()->getTable();

      $currentRefPlayerOveralls = RefPlayerOverallHistory::has('plateCard')
        ->select('player_id', 'final_overall')
        ->where('is_current', true);

      $cardList = $sub->addSelect('final_overall')
        ->joinSub(
          $currentRefPlayerOveralls,
          'current_overalls',
          function ($join) use ($plateCardTblName) {
            $join->on($plateCardTblName . '.player_id', 'current_overalls.player_id');
          }
        )->when($input['sort'], function ($query) use ($input) {
          switch ($input['sort']) {
            case 'name':
              $query->nameOrder(false, $input['order']);
              break;
            case 'price':
              $query->orderBy('price', $input['order'])
                ->nameOrder();
              break;
            case 'order_overall':
              $query->orderBy('final_overall', $input['order'])
                ->nameOrder();
              break;
          }
        })
        ->paginate($this->limit, ['*'], 'page', $input['page'])
        ->map(function ($info) use ($userCardList) {
          if (!empty($userCardList)) {
            foreach ($userCardList as $plateCardId => $userCard) {
              if ($info['id'] === $plateCardId) {
                $data['upgrading'] = $userCard['upgrading'] ?? 0;
                $data['plate'] = $userCard['plate'] ?? 0;
                $info->count = $data;
              }
            }
          }

          return $info;
        });

      return [
        'total_count' => $totalCount,
        'list' => $cardList,
      ];
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }


  public function getPointAvgs($_last5Schedules, $_playerId)
  {
    // 마지막 5개의 경기가 모두 같은 시즌임
    $seasonId = $_last5Schedules->first()['season_id'];
    if (!$seasonId) return null;
    $cardPosition = PlateCard::where('player_id', $_playerId)->value('position');

    $avgFps = RefAvgFp::where([
      'season_id' => $seasonId,
      'summary_position' => $cardPosition,
    ])->first();

    $pointAvgs = [];
    $pointAvgs['rating_avg'] = $avgFps->rating_avg;
    $pointAvgs['fantasy_point_avg'] = $avgFps->fantasy_point_avg;
    foreach (FantasyPointCategoryType::getValues() as $categoryName) {
      if ($categoryName === FantasyPointCategoryType::GENERAL) continue;
      $pointAvgs[$categoryName . '_point_avg'] = $avgFps->{$categoryName . '_point_avg'};
    }
    return $pointAvgs;
  }

  // private function getLastScheduleInfo($_last5Schedules)
  // {
  //   $lastOne =  $_last5Schedules->first()->toArray();
  //   $lastOne['fantasy_point'] = $lastOne['points']['fantasy_point'];
  //   unset($lastOne['points']);
  //   return $lastOne;
  // }

  // 구매페이지 선수정보
  public function getPlayerInfo($_data)
  {
    $plateCardInfo = $_data['base_info'][0];
    $playerId = $_data['player_id'];
    try {
      // 선수 기본 정보
      $list = $_data['base_info'][1];
      $currentMetas = RefPlayerCurrentMeta::when($_data['season_id'] !== null, function ($query) use ($_data) {
        $query->where('target_season_id', $_data['season_id']);
      }, function ($query) {
        $query->currentSeason();
      })
        ->with([
          'lastSchedule:id,home_team_id,away_team_id,started_at,round',
          'nextSchedule:id,home_team_id,away_team_id,started_at,round',
          'nextSchedule.home:' . implode(',', config('commonFields.team')),
          'nextSchedule.away:' . implode(',', config('commonFields.team')),
          'currentSeason:id,league_id,name',
          'currentSeason.league:id,league_code',
        ])
        ->where('player_id', $playerId)->first();

      $plateCardId = $currentMetas?->plate_card_id;
      $seasonId = $currentMetas?->target_season_id;

      // 유저가 보유한 해당 plate_card_id 개수(status:plate)
      $list['user_card_count'] = 0;
      if (!empty($this->user)) $list['user_card_count'] = $this->userCardCount($plateCardId);

      // 현재시즌 현재팀의 실제로 뛴 마지막 경기 가져오기
      // 마지막 경기(날짜, home/away 팀명, score)
      $last5Schedules = [];
      $list['last_schedule_info'] = null;
      $list['point_avgs'] = null;
      if (!is_null($currentMetas?->last_5_matches)) {
        $last5Schedules = collect($currentMetas->last_5_matches);
        $list['point_avgs'] = $this->getPointAvgs($last5Schedules, $playerId);
      }
      // 최근 5경기 날짜, away_team_name, fantasy_point
      $list['last_5_schedules'] = $last5Schedules;

      // 해당 팀의 마지막 경기
      if (!is_null($currentMetas?->last_team_match)) {
        $list['last_schedule_info'] = collect($currentMetas->last_team_match);
        $list['last_schedule_info']['select'] = $this->getPlayerLineupRate($currentMetas->last_schedule_id, $playerId);
        $list['last_schedule_info']['grade_points'] = null;
        if (!is_null($_data['user_plate_card_id']) && is_null($_data['mode']) && !is_null($currentMetas->last_team_match)) {

          $statDatasForCal = (Schedule::where('id', $currentMetas->last_schedule_id)->with(
            [
              'oneOptaPlayerDailyStat' => function ($query) use ($currentMetas, $playerId) {
                $query->where(['schedule_id' => $currentMetas->last_schedule_id, 'player_id' => $playerId]);
              },
              'onePlayerDailyStat' => function ($query) use ($currentMetas, $playerId) {
                $query->where(['schedule_id' => $currentMetas->last_schedule_id, 'player_id' => $playerId]);
              }
            ]
          )->first()->toArray());

          // 등급포인트 계산
          /**
           * @var FantasyCalculator $fipCalculator
           */
          $fipCalculator = app(FantasyCalculatorType::FANTASY_INGAME_POINT, [0]);
          $userPlateCard = UserPlateCard::where([['id', $_data['user_plate_card_id']], ['status', DraftCardStatus::COMPLETE]])->first()->toArray();
          $list['last_schedule_info']['grade_points'] = $fipCalculator->calculate([
            'user_card_attrs' => $userPlateCard,
            'fantasy_point' => $currentMetas?->last_player_fantasy_point,
            'schedule_id' => $currentMetas->last_schedule_id,
            'is_mom' => $statDatasForCal['one_opta_player_daily_stat']['is_mom'],
            'origin_stats' => $statDatasForCal['one_opta_player_daily_stat'],
            'fp_stats' => $statDatasForCal['one_player_daily_stat'],
          ]);
        }
      }

      $list['season_stat'] = null;
      // 현재 시즌스탯(경기수, 평균평점, 골수, 어시스트수)
      // if ($currentMetas['matches'] > 0) {
      $list['season_stat'] = [
        'season_id' => $seasonId,
        'season' => $currentMetas?->currentSeason,
        'matches' => $currentMetas?->matches,
        'ratings' => $currentMetas?->rating,
        'goals' => $currentMetas?->goals,
        'assists' => $currentMetas?->assists,
        'clean_sheets' => $currentMetas?->clean_sheets,
        'saves' => $currentMetas?->saves,
        'player_fantasy_point_avg' => $currentMetas?->player_fantasy_point_avg,
        'fantasy_top_rate' => $currentMetas?->fantasy_top_rate,
      ];
      // }

      // 최근 시즌 grade 별 카드수
      $list['season_grade_count'] = $currentMetas?->grades;

      // 다음 스케쥴
      $list['next_schedule_info'] = $currentMetas?->nextSchedule;

      // history - 강화중 상세모달 추가정보(draft_schedule, draft_level, price)
      if (!is_null($_data['mode'])) {
        $draftSelection = DraftSelection::where('user_plate_card_id', $_data['user_plate_card_id']);
        $draftSchedule = $draftSelection->clone()
          ->with([
            'schedule.home:' . implode(',', config('commonFields.team')),
            'schedule.away:' . implode(',', config('commonFields.team')),
            'schedule:id,started_at,home_team_id,away_team_id'
          ])
          ->has('schedule.home')
          ->has('schedule.away')
          ->select('schedule_id')
          ->get()->toArray();

        /**
         * @var FantasyCalculator $draftCalculator
         */
        $draftCalculator = app(FantasyCalculatorType::FANTASY_DRAFT, [0]);
        $draftRowWithCate = $draftCalculator->calculate(
          [
            'opta_stats' => [],
            'selections' => $draftSelection->first()->toArray(),
          ],
          true
        );
        $list['draft']['level'] = $draftRowWithCate['meta']['total_selection'];

        $draftOrder = DraftOrder::where([
          ['user_plate_card_id', $_data['user_plate_card_id']],
          ['order_status', PurchaseOrderStatus::COMPLETE]
        ])->select('upgrade_point', 'upgrade_point_type')->first()->toArray();
        $list['draft']['price'] = $draftOrder['upgrade_point'];
        $list['draft']['price_type'] = $draftOrder['upgrade_point_type'];
        $list['draft']['schedule'] = $draftSchedule;
      }

      return $list;
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  // 라인업 비율 구하기
  public function getPlayerLineupRate($_scheduleId, $_playerId)
  {
    // 1. 마지막 경기가 속한 게임id
    $gameId = GameSchedule::where('schedule_id', $_scheduleId)->value('game_id');

    // 해당 player_id 라인업 수 / 전체 참여자 수

    // 전체 참여자 수
    $allJoinCount = GameJoin::where('game_id', $gameId)->count();

    // 해당 player 라인업 수
    $playerCount = GameLineup::whereHas('gameJoin', function ($query) use ($gameId) {
      $query->where('game_id', $gameId);
    })->where('player_id', $_playerId)->count();

    if ($allJoinCount > 0 && $playerCount > 0) {
      return (float) bcdiv($playerCount, $allJoinCount, 2);
    }

    return 0;
  }

  public function getPlateCardOrderCount($_plateCardId)
  {
    return OrderPlateCard::selectRaw('SUM(quantity) AS total_quantity, RANK() OVER (order by SUM(quantity) DESC) as ranking ,plate_card_id')
      ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
      ->groupBy('plate_card_id')
      ->get()
      ->where('plate_card_id', $_plateCardId)
      ->first()
      ?->toArray();
  }
  public function getPlayerDetail($_data)
  {
    $playerId = $_data['player'];

    try {
      $plateCardInfo = PlateCard::with([
        'refPlayerOverall:player_id,season_id,final_overall',
        'season:id,name'
      ])
        ->where('player_id', $playerId)->withTrashed()->first()->toArray();
      $playerInfo = Player::whereId($playerId)->first()->toArray();

      // 해당 카드의 season의 start_date 확인
      // 시즌 시작 전이면 직전 시즌
      if (Season::where([
        ['id', $plateCardInfo['season_id']],
        ['start_date', '<', now()]
      ])->exists()) {
        $seasonId = $plateCardInfo['season_id'];
      } else {
        $seasonId = Season::getBeforeFuture([SeasonWhenType::BEFORE], $plateCardInfo['league_id'])[$plateCardInfo['league_id']]['before'][0]['id'];
      }

      $player = [];
      $player['id'] = $playerId;

      $orderResults = $this->getPlateCardOrderCount($plateCardInfo['id']);

      unset($orderResults['plate_card_id']);

      $player['order_ranking'] = !is_null($orderResults) ? $orderResults : [
        'total_quantity' => 0,
        'ranking' => 1
      ];

      // 선수의 기본 정보
      $player['plate_card_id'] = $plateCardInfo['id'];
      $player['league_id'] = $plateCardInfo['league_id'];
      $player['season_id'] = $seasonId;
      $player['team'] = [
        'id' => $plateCardInfo['team_id'],
        'code' => $plateCardInfo['team_code'],
        'name' => $plateCardInfo['team_name'],
        'short_name' => $plateCardInfo['team_short_name'],
        'club_name' => $plateCardInfo['team_club_name']
      ];
      foreach (
        [
          ...config('commonFields.player'),
          ...config('commonFields.combined_player')
        ] as $field
      ) {
        $player['name'][$field] = $plateCardInfo[$field];
      }
      $player['birth_date'] = $playerInfo['date_of_birth'];
      $player['height'] = $plateCardInfo['height'];
      $player['shirt_number'] = $plateCardInfo['shirt_number'];
      $player['nationality'] = $playerInfo['nationality'];
      $player['nation_code'] = RefCountryCode::where('nationality_id', $playerInfo['nationality_id'])->value('alpha_3_code');
      $player['position'] = $plateCardInfo['position'];
      $player['foot'] = $plateCardInfo['foot'];
      $player['headshot_path'] = $plateCardInfo['headshot_path'];
      $player['deleted_at'] = $plateCardInfo['deleted_at'];

      $player['status']['suspension'] = Suspension::where([
        ['season_id', $seasonId],
        ['player_id', $playerId],
        ['suspension_end_date', '>', now()]
      ])->select('suspension_start_date', 'suspension_end_date', 'description')
        ->orderByDesc('suspension_end_date')
        ->first()?->toArray();

      $player['status']['injury'] = Injuries::where([
        ['season_id', $seasonId],
        ['player_id', $playerId],
      ])->where(function ($query) {
        $query->whereNull('injury_end_date')
          ->orWhere('expected_end_date', '>', now());
      })->select('injury_type', 'injury_start_date', 'expected_end_date')->first()?->toArray();

      // TODO : Player SNS
      $player['sns'] = [];

      // next Match
      $player['next_match'] = Schedule::query()
        ->where('league_id', $plateCardInfo['league_id'])
        ->where('status', ScheduleStatus::FIXTURE)
        ->where(function ($query) use ($plateCardInfo) {
          $query->where('away_team_id', $plateCardInfo['team_id'])
            ->orWhere('home_team_id', $plateCardInfo['team_id']);
        })->with([
          'home:id,code,name,short_name',
          'away:id,code,name,short_name'
        ])
        ->select('id', 'season_id', 'home_team_id', 'away_team_id', 'started_at', 'status')
        ->orderBy('started_at')
        ->first()?->toArray();


      $optaSeasonStats = OptaPlayerSeasonStat::query()
        ->where([
          ['season_id', $seasonId],
          ['player_id', $playerId]
        ])->first();

      // player Season 기록
      $recordSeasonId = $optaSeasonStats?->season_id ?? $plateCardInfo['season']['id'];
      $season = [
        'season_id' => $recordSeasonId,
        'name' => Season::whereId($recordSeasonId)->value('name')
      ];
      $season['mataches'] = [
        'my' => $optaSeasonStats?->appearances,
        'team' => OptaTeamSeasonStat::where([
          ['season_id', $plateCardInfo['season_id']],
          ['team_id', $plateCardInfo['team_id']]
        ])->selectRaw('matches_won + matches_lost + matches_drawn AS sum')
          ->value('sum')
      ];

      $season['mins_played'] = $optaSeasonStats?->mins_played;
      $season['goals'] = $optaSeasonStats?->goals;
      $season['goal_assists'] = $optaSeasonStats?->goal_assists;
      $season['yellow_card'] = $optaSeasonStats?->yellow_cards;
      $season['red_card'] = $optaSeasonStats?->red_card;
      $season['rating'] = [
        'rating' => $optaSeasonStats?->rating,
        'rank' => OptaPlayerSeasonStat::where('season_id', $optaSeasonStats?->season_id)
          ->when($optaSeasonStats, function ($query, $stats) {
            $query->where('rating', '>', $stats?->rating);
          })->count()
      ];

      $player['season_record'] = $season;

      // player career
      $player['career']['teams'] = [];
      PlayerCareer::with('team')
        ->where([
          ['player_id', $playerId],
          ['team_type', TeamType::CLUB],
          ['membership_type', MembershipType::MEN],
          ['is_friendly', YesNo::NO]
        ])
        ->selectRaw('
        group_no,
        ANY_VALUE(player_id) AS player_id,
        ANY_VALUE(active) AS active,
        ANY_VALUE(team_id) AS team_id,
        ANY_VALUE(team_name) AS team_name,
        ANY_VALUE(membership_start_date) AS membership_start_date,
        ANY_VALUE(membership_end_date) AS membership_end_date,
        ANY_VALUE(membership_end_date) AS membership_end_date,
        SUM(ANY_VALUE(appearances)) AS appearances,
        SUM(ANY_VALUE(goals)) AS goals,
        SUM(ANY_VALUE(assists)) AS assists
      ')
        ->groupBy('group_no')
        ->get()
        ->map(function ($info) use (&$player) {
          $team['id'] = $info->team_id;
          $team['name'] = $info->team->short_name ?? $info->team_name;
          $team['active'] = $info->active;
          $team['start_date'] = $info->membership_start_date;
          $team['end_date'] = $info->membership_end_date;
          $team['match'] = $info->appearances;
          $team['goal'] = $info->goals;
          $team['assist'] = $info->assists;

          $player['career']['teams'][] = $team;
        });

      $player['career']['teams'] = __sortByKeys($player['career']['teams'], ['keys' => ['active', 'end_date'], 'hows' => ['desc', 'desc']]);

      $player['career']['national'] = PlayerCareer::with('countryCode:nationality_id,alpha_3_code AS nation_code')
        ->where([
          ['player_id', $playerId],
          ['team_type', TeamType::NATIONAL],
          ['membership_type', MembershipType::MEN],
        ])->selectRaw('
        IF(ANY_VALUE(team_name) = ANY_VALUE(nationality), ANY_VALUE(nationality), ANY_VALUE(second_nationality)) AS nationality, 
        IF(ANY_VALUE(team_name) = ANY_VALUE(nationality), ANY_VALUE(nationality_id), ANY_VALUE(second_nationality_id)) AS nationality_id, 
        ANY_VALUE(membership_start_date) AS start_date, 
        ANY_VALUE(membership_end_date) AS end_date, 
        CAST(SUM(ANY_VALUE(appearances)) AS unsigned) AS matches, 
        CAST(SUM(ANY_VALUE(goals)) AS unsigned) AS goals, 
        CAST(SUM(ANY_VALUE(assists)) AS unsigned) AS assist')
        ->orderByDesc('group_no')
        ->groupBy('group_no')
        ->first()?->toArray();

      // player Current Meta
      $curerntMeta = RefPlayerCurrentMeta::where([
        ['target_season_id', $seasonId],
        ['player_id', $playerId],
      ])->first()?->toArray();

      // player Last Match
      $player['last_match'] = null;
      if (!is_null($curerntMeta)) {
        $player['last_match'] = $curerntMeta['last_team_match'];
      }

      // player 강점
      $columns = [];
      foreach (config('refplayerstrength.categories') as $category) {
        $columns = array_merge($columns, array_keys($category));
      }

      $player['strength'] = [
        PlayerStrengthType::VERY_STRONG => [],
        PlayerStrengthType::STRONG => []
      ];
      RefPlayerSeasonStrengths::where([
        ['season_id', $seasonId],
        ['player_id', $playerId]
      ])->get()
        ->map(function ($info) use (&$player, $columns) {
          foreach ($columns as $column) {
            if (!is_null($info->$column)) {
              if ($info->$column === PlayerStrengthType::VERY_STRONG) {
                array_push($player['strength'][PlayerStrengthType::VERY_STRONG], $column);
              } else {
                array_push($player['strength'][PlayerStrengthType::STRONG], $column);
              }
            }
          }
        });

      // player 포지션 빈도
      $player['major_formation'] = null;
      if (!is_null($curerntMeta)) {
        $player['major_formation'] = $curerntMeta['formation_aggr'];
      }

      // player의 stat이 있는 season List
      $player['season_list'] = RefPlayerCurrentMeta::where('player_id', $playerId)
        ->selectRaw('target_season_id AS season_id, target_league_id AS league_id, target_league_code AS league_code, target_season_name AS season_name')
        ->orderByDesc('season_start_date')
        ->get()->toArray();

      // TODO : 임시로 season_list 에 현재 시즌 추가
      if (count($player['season_list']) === 0) {
        Season::currentSeasons()
          ->with(['league'])->whereId($plateCardInfo['season_id'])
          ->get()
          ->map(function ($info) use (&$tempData) {
            $tempData['season_id'] = $info->id;
            $tempData['league_code'] = $info->league->league_code;
            $tempData['league_id'] = $info->league_id;
            $tempData['season_name'] = $info->name;
          });
        $player['season_list'][] = $tempData;
      }

      $player['final_overall'] = null;
      if ($plateCardInfo['ref_player_overall']) {
        foreach ($plateCardInfo['ref_player_overall'] as $playerOverall) {
          if ($playerOverall['season_id'] === $plateCardInfo['season_id']) {
            $player['final_overall'] = $playerOverall['final_overall'] ?? null;
          }
        }
      }

      return $player;
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function playerDetailStats($_data)
  {
    $playerId = $_data['player'];
    $seasonId = $_data['season'];

    // 선수의 팀 기준 마지막 라운드
    $teamId = PlateCard::where('player_id', $playerId)->value('team_id');
    $currentRound = Schedule::where(function ($query) use ($teamId) {
      $query->where('home_team_id', $teamId)
        ->orWhere('away_team_id', $teamId);
    })->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
      ->latest()
      ->limit(1)
      ->value('round');
    if (!$currentRound) $currentRound = 1;

    $passCondition = $currentRound * 30;

    // 해당하는 시즌의 기록실 stat List
    $list = [];
    PlayerSeasonRankingView::withTrashed()
      ->where([
        ['player_id', $playerId],
        ['season_id', $seasonId]
      ])->get()
      ->map(function ($info) use (&$list, $passCondition) {
        foreach (config('stats.categories.player') as $category => $columns) {
          if ($category != StatCategory::SUMMARY) {
            foreach ($columns as $column) {
              $list['stats'][$category][$column] = $info->$column;
              $list['stats'][$category][$column . '_rank'] = $info->{$column . '_rank'};
              if ($column === 'passing_accuracy' && ($passCondition > $info->total_passes)) {
                $list['stats'][$category][$column . '_rank'] = '-';
              }
            }
          }
        }
      });

    // 해당하는 시즌의 last 5 schedules -> currentMeta
    // $curerntMeta = RefPlayerCurrentMeta::where([
    //   ['target_season_id', $seasonId],
    //   ['player_id', $playerId],
    // ])->first();

    // $list['last_5'] = [];
    // if (!is_null($curerntMeta?->last_5_matches)) {
    //   $last5Schedules = collect($curerntMeta->last_5_matches);
    //   $list['last_5']['schedules'] = $last5Schedules;
    //   $list['last_5']['point_avgs'] = $this->getPointAvgs($last5Schedules, $playerId);
    // }

    return $list;
  }

  // 유저가 보유한 해당plate_card_id 개수(status:plate)
  private function userCardCount($_plateCardId)
  {
    return UserPlateCard::where([
      'user_id' => $this->user->id,
      'plate_card_id' => $_plateCardId,
      'status' => PlateCardStatus::PLATE
    ])->count();
  }

  // 최근 시즌 grade 별 수
  private function seasonGradeCnt($_season_id, $_playerId)
  {
    return PlayerDailyStat::where([
      'season_id' => $_season_id,
      'player_id' => $_playerId,
      'status' => ScheduleStatus::PLAYED
    ])
      ->whereNotNull('card_grade')
      ->gameParticipantPlayer()
      ->selectRaw('card_grade, COUNT(card_grade) AS cnt')
      ->groupBy('card_grade')
      ->get()
      ->toArray();
  }

  // 사용자 cardList 통합
  public function getUserCardsByLeague($input)
  {
    $this->limit = $input['per_page'];
    try {
      switch ($input['type']) {
        case 'plate':
          return $this->userCards($input)
            ->when(!$input['other'], function ($other) {
              // ETC가 아닌 경우, 현재 판매되는 아이들만 나올 수 있게(scout 와 같이 씀.)
              $other->whereHas('plateCardWithTrashed', function ($query) {
                $query->isOnSale()
                  ->has('currentRefPlayerOverall');
              });
            })
            ->with(['plateCardWithTrashed', 'refPlayerOverall', 'orderTeam'])
            ->where('status', '!=', PlateCardStatus::COMPLETE)
            ->whereNotNull('ref_player_overall_history_id')
            ->selectRaw('
            IFNULL(GROUP_CONCAT(CASE WHEN status = "plate" then id end), ANY_VALUE(id)) AS id,
            ANY_VALUE(user_id) AS user_id,
            ANY_VALUE(player_name) AS player_name,
            ANY_VALUE(plate_card_id) AS plate_card_id,
            ANY_VALUE(position) AS position,
            ANY_VALUE(order_overall) AS order_overall,
            ref_player_overall_history_id,
            ANY_VALUE(order_league_id) AS order_league_id,
            ANY_VALUE(order_team_id) AS order_team_id,
            SUM(CASE WHEN ANY_VALUE(status) = "plate" then 1 else 0 END) as plate_cnt,
            SUM(CASE WHEN ANY_VALUE(status) = "upgrading" then 1 else 0 END) as upgrading_cnt')
            ->groupBy('ref_player_overall_history_id')
            ->when($input['sort'], function ($query) use ($input) {
              if ($input['sort'] === 'name') {
                $query->orderBy('player_name', $input['order']);
              } else {
                $query->orderBy('order_overall', $input['order'])
                  ->orderBy('player_name');
              }
            }, function ($query) {
              $query->orderBy('player_name')
                ->orderByDesc('order_overall')
                ->latest();
            })->get()
            ->map(function ($info) use ($input) {
              $info->player_id = $info->plateCardWithTrashed->player_id;
              foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
                $info->{$field} = $info->plateCardWithTrashed->{$field};
              }
              $info->headshot_path = $info->plateCardWithTrashed->headshot_path;
              foreach (config('commonFields.team') as $field) {
                $team[$field] = $info->orderTeam->{$field};
              }
              $info->order_team = $team;

              $info->status = $info->upgrading_cnt > 0 ? PlateCardStatus::UPGRADING : PlateCardStatus::PLATE;
              $info->cnt = [
                PlateCardStatus::PLATE => $info->{PlateCardStatus::PLATE . '_cnt'},
                PlateCardStatus::UPGRADING => $info->{PlateCardStatus::UPGRADING . '_cnt'}
              ];

              unset($info->plate_cnt);
              unset($info->upgrading_cnt);
              unset($info->plateCardWithTrashed);
              unset($info->refPlayerOverall);
              unset($info->orderTeam);
              unset($info->orderLeague);

              return $info;
            });
        case 'grade':
          $sub = Auction::whereHas('auctionBid', function ($query) {
            $query->where('user_id', $this->user->id)
              ->whereIn('status', [AuctionBidStatus::SUCCESS, AuctionBidStatus::PURCHASED]);
          })
            ->whereNotNull('sold_at')
            ->selectRaw('user_plate_card_id, MAX(sold_at) as sold_at')
            ->groupBy('user_plate_card_id');

          $upcTbl = UserPlateCard::getModel()->getTable();

          return $this->userCards($input)
            ->leftJoinSub($sub, 'auction', function ($query) use ($upcTbl) {
              $query->on('user_plate_card_id', '=', $upcTbl . '.id');
            })
            ->with(['plateCardWithTrashed', 'draftSeason', 'draftTeam', 'simulationOverall'])
            ->selectRaw(
              'id,
              user_id,
              draft_season_id,
              draft_season_name,
              draft_team_id,
              draft_schedule_round,
              draft_shirt_number,
              draft_level,
              plate_card_id,
              card_grade,
              special_skills,
              position,
              is_mom,
              draft_completed_at,
              lock_status,
              is_open,
              is_free,
              IFNULL(sold_at, draft_completed_at) AS draft_completed_at'
            )
            ->orderBy('card_grade')
            ->orderByDesc('draft_level')
            ->orderByDesc('draft_completed_at')
            ->orderBy('player_name')
            ->get()
            ->map(function ($item) {
              $item->grade_order_no = config('constant.DRAFT_CARD_GRADE_ORDER')[$item->card_grade];
              $item->player_id = $item->plateCardWithTrashed->player_id;
              foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
                $item->{$field} = $item->plateCardWithTrashed->{$field};
              }
              $item->headshot_path = $item->plateCardWithTrashed->headshot_path;

              $item->final_overall = null;
              if ($item->simulationOverall) {
                $finalOverall = $item->simulationOverall->final_overall;
                $subPosition = $item->simulationOverall->sub_position;
                $item->sub_position = $subPosition;
                $item->final_overall = $finalOverall ? (int) $finalOverall[$subPosition] : null;
              }

              $season['id'] = $item->draft_season_id;
              $season['name'] = $item->draft_season_name;
              $season['league_id'] = $item->draftSeason->league_id;
              $season['league']['id'] = $item->draftSeason->league_id;
              $season['league']['league_code'] = $item->draftSeason->league->league_code;
              $item->draft_season = $season;

              $team['id'] = $item->draftTeam->id;
              $team['code'] = $item->draftTeam->code;
              $team['name'] = $item->draftTeam->name;
              $team['short_name'] = $item->draftTeam->short_name;
              $item->draft_team = $team;

              unset($item->draft_season_id);
              unset($item->draft_season_name);
              unset($item->draft_team_id);
              unset($item->plateCardWithTrashed);
              unset($item->draftTeam);
              unset($item->draftSeason);
              unset($item->simulationOverall);
              return $item;
            });
        default:
          break;
      }
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function getUserCardsCountByGrade($input)
  {
    try {
      // 강화중인 카드
      $list['card_count']['upgrading'] = $this->userCards($input)
        ->selectRaw('status, COUNT(*) AS count')
        ->where('card_grade', CardGrade::NONE)
        ->where('status', PlateCardStatus::UPGRADING)
        ->groupBy('status')
        ->value('count');

      $input['type'] = 'plate';
      // 플레이트 카드 : plate
      $list['card_count']['plate'] = $this->userCards($input)
        ->whereNotNull('ref_player_overall_history_id')
        ->selectRaw('status, COUNT(*) AS count')
        ->where('card_grade', CardGrade::NONE)
        ->where('status', PlateCardStatus::PLATE)
        ->groupBy('status')
        ->value('count');

      $input['type'] = 'grade';
      // 강화된 카드 : 등급별
      $list['card_count']['grade'] = $this->userCards($input)
        ->selectRaw('card_grade, COUNT(*) AS count')
        ->where('card_grade', '!=', CardGrade::NONE)
        ->groupBy('card_grade')
        ->pluck('count', 'card_grade')
        ->toArray();

      return $list;
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  // cardHistory > League, Season 별 round
  public function getRoundsBySeason($leagueQuery)
  {
    try {
      // 각 시즌 별 maxRound
      $maxRounds = Schedule::groupBy('season_id')
        ->selectRaw('season_id, IFNULL(MAX(round),1) as maxRound')
        ->get()
        ->pluck('maxRound', 'season_id')
        ->toArray();
      // dd($maxRounds);
      // 카드가 있는 라운드
      $hasCardRounds = Schedule::whereHas('draftLog', function ($query) {
        $query->where('user_id', $this->user->id);
      })
        ->select('season_id', 'round')
        ->groupBy(['season_id', 'round'])
        ->get()
        ->mapToGroups(function ($info) {
          return [$info['season_id'] => $info['round']];
        })
        ->toArray();

      // 미래시즌(active=no) 제외
      $showSeasons = Season::idsOf([SeasonWhenType::BEFORE, SeasonWhenType::CURRENT]);

      return $leagueQuery
        ->with(['seasons' => function ($query) use ($showSeasons) {
          $query->whereIn('id', $showSeasons)->orderByDesc('start_date');
        }])
        ->get()
        ->map(function ($info) use ($maxRounds, $hasCardRounds) {
          foreach ($info->seasons as $season) {
            $season->max_round = $maxRounds[$season->id] ?? 1;
            $currentRound = $this->upcomingRound($season->id) ?? 1;
            $season->current_round = $currentRound;
            $season->default_round = $currentRound;
            if (!empty($hasCardRounds[$season->id])) {
              sort($hasCardRounds[$season->id]);
              $pastRoundArr = array_filter($hasCardRounds[$season->id], function ($babo) use ($currentRound) {
                return $babo <= $currentRound;
              });
              if ($currentRound > max($hasCardRounds[$season->id])) {
                $season->default_round = max($pastRoundArr);
              }
            }
            $season->has_card_rounds = $hasCardRounds[$season->id] ?? [];
          }

          return $info;
        });
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  private function userCardHistoryQuery($_input)
  {
    return DraftLog::withWhereHas('userPlateCard', function ($query) {
      $query->withoutGlobalScope('excludeBurned')
        ->with([
          'plateCard:id,player_id,headshot_path,' . implode(',', config('commonFields.player')),
          'simulationOverall:user_plate_card_id,sub_position,final_overall'
        ])->where('user_id', $this->user->id);
    })->where('user_id', $this->user->id)
      ->withWhereHas('schedule', function ($query) use ($_input) {
        $query->with([
          'home:' . implode(',', config('commonFields.team')),
          'away:' . implode(',', config('commonFields.team')),
        ])
          ->has('home')
          ->has('away')
          ->when($_input['schedule'], function ($query, $scheduleId) {
            $query->where('id', $scheduleId);
          }, function ($query) use ($_input) {
            $query->where([
              'season_id' => $_input['season'],
              'round' => $_input['round']
            ]);
          });
      });
  }

  public function getUserCardsHistory($input)
  {
    try {
      if (empty($input['round'])) {
        $input['round'] = $this->upcomingRound($input['season']);
      }

      // normal
      $list = [
        'normal' => [],
        'cancel' => [],
      ];
      if (!isset($input['index'])) {
        $this->userCardHistoryQuery($input)
          ->get()
          ->sortByDesc('id')
          ->sortBy('is_open')
          ->sortBy('schedule.home.name')
          ->sortByDesc('origin_started_at')
          ->sortBy(function ($value) {
            return config('constant.DRAFT_SCHEDULE_ORDER')[$value->schedule->status];
          })
          ->groupBy(['schedule_id', 'user_plate_card_id'])
          ->map(function ($group, $key) use (&$list, &$input) {
            $schedule = null;
            $cards = [];
            $breakActive = false;
            $group->map(function ($info) use (&$cards, &$schedule, &$breakActive, &$input) {
              $card = $info->first();
              if (count($info) > 1) {
                $draft = $info->toArray()[1];
                $card->drafted_at = $draft['created_at'];
              }

              if (!$breakActive && in_array($card->schedule_status, ScheduleStatus::NORMAL)) {
                $breakActive = true;
              } else {
                return false;
              }

              if ($breakActive && !in_array($card->schedule_status, ScheduleStatus::NORMAL)) {
                return false;
              } else {
                $breakActive = false;
              }

              if (!isset($schedule)) {
                $schedule = $card->schedule->only([
                  'id',
                  'score_home',
                  'score_away',
                  'status',
                  'winner',
                  'started_at',
                  'home',
                  'away',
                ]);

                if ($input['status'] != $schedule['status']) {
                  $input['end_id'] = null;
                }
              }

              // dd($card);
              // $card->draft_team->id = $card->userPlateCard->draft_team_id;
              $card->draft_team = $card->userPlateCard->draft_team_names;
              $card->position = $card->userPlateCard->position;
              $card->order_overall = $card->userPlateCard->order_overall;
              $card->order_league_id = $card->userPlateCard->order_league_id;
              $card->order_league_code = $card->userPlateCard->order_league_code;
              $card->special_skills = $card->userPlateCard->special_skills;
              if ($card->status === DraftCardStatus::COMPLETE) {
                $card->draft_league_id = $card->userPlateCard->draftSeason->league_id;
                $card->draft_league_code = $card->userPlateCard->draftSeason->league->league_code;
              }
              $card->sub_position = null;
              $card->overall = null;
              if ($card->userPlateCard->status === DraftCardStatus::COMPLETE) {
                $card->sub_position = $card->userPlateCard->simulationOverall?->sub_position;
                if (!is_null($card->sub_position)) {
                  $overalls = $card->userPlateCard->simulationOverall->final_overall;
                  $card->overall = (int) $overalls[$card->sub_position];
                }
              }

              // dd($card->userPlateCard->plateCardWithTrashed->toArray());
              // dd($card->userPlateCard->plateCardWithTrashed->selectRaw('id,player_id,team_id,headshot_path,' . implode(',', config('commonFields.player')))->get()->toArray());
              // foreach ($card->userPlateCard->plateCardWithTrashed->toArray() as $key => $val) {
              // }
              $plateCardInfo = $card->userPlateCard->plateCardWithTrashed->toArray();
              $card->id = $plateCardInfo['id'];
              $card->player_id = $plateCardInfo['player_id'];
              $card->team_id = $plateCardInfo['team_id'];
              $card->headshot_path = $plateCardInfo['headshot_path'];
              foreach (config('commonFields.player') as $field) {
                $card[$field] = $plateCardInfo[$field];
              }

              //              $card->player_name = $card->userPlateCard->plateCard->player_name;
              //              $card->first_name_eng = $card->userPlateCard->plateCard->first_name_eng;
              //              $card->last_name_eng = $card->userPlateCard->plateCard->last_name_eng;
              //              $card->match_name = $card->userPlateCard->plateCard->match_name;
              //              $card->short_first_name = $card->userPlateCard->plateCard->short_first_name;
              //              $card->short_last_name = $card->userPlateCard->plateCard->short_last_name;
              //              $card->headshot_path = $card->userPlateCard->plateCard->headshot_path;

              $card->draft_level = $card->userPlateCard->draft_level;
              $card->card_grade = $card->userPlateCard->card_grade;
              $card->is_open = $card->userPlateCard->is_open;
              $card->status_order = config('constant.DRAFT_STATUS_ORDER')[$card->status];
              $card->card_grade_order = config('constant.DRAFT_CARD_GRADE_ORDER')[$card->userPlateCard->card_grade];

              unset(
                $card->userPlateCard,
                $card->schedule,
                $card->scheduleStatusChangeLog
              );

              $cards[] = $card->toArray();
            });
            if (count($cards) > 0) {
              $list['normal'][$key]['schedule'] = $schedule;
              $list['normal'][$key]['total_count'] = count($cards);
              $list['normal'][$key]['cards'] = __sortByKeys($cards, ['keys' => ['status_order', 'card_grade_order', 'draft_level', 'created_at', 'first_name_eng', 'last_name_eng'], 'hows' => ['asc', 'asc', 'desc', 'asc', 'asc', 'asc']]);
            }
          });
      }

      if (!isset($input['schedule']) || isset($input['index'])) {
        // 비정상
        $this->userCardHistoryQuery($input)
          ->withHas('scheduleStatusChangeLog')
          ->get()
          ->sortByDesc('id')
          ->sortBy('schedule.home.name')
          ->sortByDesc('origin_started_at')
          ->sortBy(function ($value) {
            return config('constant.DRAFT_SCHEDULE_ORDER')[$value->schedule->status];
          })
          ->groupBy(['schedule_id', 'user_plate_card_id'])
          ->map(function ($group, $key) use (&$list, &$input) {
            $schedule = null;
            $cards = [];
            $breakActive = false;
            $group->map(function ($info) use (&$cards, &$schedule, &$breakActive, &$input) {
              $card = $info->first();
              if (count($info) > 1) {
                $draft = $info->toArray()[1];
                $card->drafted_at = $draft['created_at'];
              }

              if (!$breakActive && !in_array($card->schedule_status, ScheduleStatus::NORMAL)) {
                $breakActive = true;
              } else {
                return false;
              }

              if ($breakActive && in_array($card->schedule_status, ScheduleStatus::NORMAL)) {
                return false;
              } else {
                $breakActive = false;
              }

              if (!isset($schedule)) {
                $schedule = $card->schedule->only([
                  'id',
                  'score_home',
                  'score_away',
                  'status',
                  'winner',
                  'started_at',
                  'home',
                  'away',
                ]);
                $schedule['started_at'] = $card->origin_started_at;
                $schedule['status'] = $card->schedule_status;

                if ($input['status'] != $schedule['status']) {
                  $input['end_id'] = null;
                }
              }

              // $card->draft_team->id = $card->userPlateCard->draft_team_id;
              // $card->draft_team = $card->userPlateCard->draft_team_names;
              $card->position = $card->userPlateCard->position;
              $card->order_overall = $card->userPlateCard->order_overall;
              $card->order_league_id = $card->userPlateCard->order_league_id;
              $card->order_league_code = $card->userPlateCard->order_league_code;
              if ($card->status === DraftCardStatus::COMPLETE) {
                $card->draft_league_id = $card->userPlateCard->draftSeason->league_id;
                $card->draft_league_code = $card->userPlateCard->draftSeason->league->league_code;
              }
              $card->sub_position = null;
              $card->overall = null;
              if ($card->userPlateCard->status === DraftCardStatus::COMPLETE) {
                $card->sub_position = $card->userPlateCard->simulationOverall?->sub_position;
                if (!is_null($card->sub_position)) {
                  $overalls = $card->userPlateCard->simulationOverall?->final_overall;
                  $card->overall = (int) $overalls[$card->sub_position];
                }
              }
              $card->player_name = $card->userPlateCard->plateCard->player_name;
              $card->first_name_eng = $card->userPlateCard->plateCard->first_name_eng;
              $card->last_name_eng = $card->userPlateCard->plateCard->last_name_eng;
              $card->match_name = $card->userPlateCard->plateCard->match_name;
              $card->short_first_name = $card->userPlateCard->plateCard->short_first_name;
              $card->short_last_name = $card->userPlateCard->plateCard->short_last_name;
              $card->headshot_path = $card->userPlateCard->plateCard->headshot_path;

              foreach ($card->scheduleStatusChangeLog as $changeLogInfo) {
                if ($changeLogInfo->new_started_at === $card->origin_started_at) {
                  $card->index = $changeLogInfo->index_changed;
                }
              }

              unset(
                $card->userPlateCard,
                $card->schedule,
                $card->scheduleStatusChangeLog
              );

              $card->status_order = config('constant.DRAFT_STATUS_ORDER')[$card->status];
              $card->card_grade_order = config('constant.DRAFT_CARD_GRADE_ORDER')[$card->card_grade];
              $cards[] = $card->toArray();

              // $latestStatus = $card->schedule_status;
            });
            if (count($cards) > 0) {
              $list['cancel'][$key]['schedule'] = $schedule;
              $list['cancel'][$key]['total_count'] = count($cards);
              $list['cancel'][$key]['cards'] = __sortByKeys($cards, ['keys' => ['status_order', 'card_grade_order', 'draft_level', 'created_at', 'first_name_eng', 'last_name_eng'], 'hows' => ['asc', 'asc', 'desc', 'asc', 'asc', 'asc']]);
            }
          });
      }

      $startIdx = 0;
      foreach (['normal', 'cancel'] as $status) {
        foreach ($list[$status] as $scheduleId => $data) {
          if (isset($input['end_id'])) {
            foreach ($data['cards'] as $key => $val) {
              if ($val['id'] === (int) $input['end_id']) {
                $startIdx = $key + 1;
                break;
              }
            }
          }

          $list[$status][$scheduleId]['cards'] = array_slice($data['cards'], $startIdx, $input['limit']);
        }
      }

      return $list;
    } catch (Throwable $th) {
      throw new Exception($th->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  public function getPopularSameRank($_sameRank = [], $_key = null)
  {
    if ($_key === 'sale') {
      $nextKey = 'upgrade';
    } else if ($_key === 'upgrade') {
      $nextKey = 'lineup';
    } else {
      $nextKey = null;
    }

    $compareColumn = 'count';
    if ($_key === 'sale') {
      $data = PlateCardDailyAction::query()
        ->whereIn('player_id', array_column($_sameRank, 'player_id'))
        ->where('type', PlateCardActionType::SALE)
        ->get()
        ->keyBy('player_id')
        ->toArray();
      // 필요한 데이터 불러와서 재정렬
    } else if ($_key === 'upgrade') {
      $data = UserPlateCard::whereHas('plateCard', function ($plateCardQuery) use ($_sameRank) {
        $plateCardQuery->whereIn('player_id', array_column($_sameRank, 'player_id'));
      })
        ->get()
        ->groupBy('plateCard.player_id')
        ->map(function ($group) {
          return [
            'count' => $group->sum('upgrading_count')
          ];
        })
        ->toArray();
    } else if ($_key === 'lineup') {
      $data = GameLineup::whereHas('userPlateCard.plateCard', function ($query) use ($_sameRank) {
        $query->whereIn('player_id', array_column($_sameRank, 'player_id'));
      })
        ->get()
        ->groupBy('userPlateCard.plateCard.player_id')
        ->map(function ($group) {
          return [
            'count' => $group->count()
          ];
        })
        ->toArray();
    }

    // 비교 대상 데이터만 추출한 상태 - 비교 전
    if (empty($data)) {
      // 비교 데이터가 존재하지 않을 때
      if (!is_null($nextKey)) {
        return $this->getPopularSameRank($_sameRank, $nextKey);
      }

      $topData = $_sameRank;
    } else {
      // 하나라도 있으면 랭킹 가능
      foreach ($_sameRank as &$item) {
        if (!isset($data[$item['player_id']])) {
          $item[$compareColumn] = 0;
          continue;
        }
        $item[$compareColumn] = $data[$item['player_id']][$compareColumn];
      }

      $topData = __ranking($_sameRank, $compareColumn)[0];
    }

    // 비교 후
    if (count($topData) > 1) {
      // 최고 데이터가 두개 이상일때
      if (!is_null($nextKey)) {
        // 다음 키가 있으면 그대로 돌림
        return $this->getPopularSameRank($topData, $nextKey);
      }
      unset($topData[rand(0, count($topData) - 1)]);
    }

    $topData = current($topData);
    unset($topData[$compareColumn]);
    return $topData;
  }

  private function userCards($input)
  {
    return UserPlateCard::where('user_id', $this->user->id)
      // ->withWhereHas('plateCard', function ($query) use ($input) {
      //   $query->applyFiltersA($input);
      // })
      ->when($input['position'], function ($query, $postions) {
        $query->whereIn('position', $postions);
      })
      ->when($input['grade'], function ($query, $grades) {
        $query->whereIn('card_grade', $grades);
      })
      ->when($input['type'], function ($query, $type) use ($input) {
        switch ($type) {
          case 'grade':
            $query->where('is_open', true)->gradeFilters($input);
            break;
          case 'plate':
            $query->plateFilters($input);
            break;
          default:
            break;
        }
      });
  }

  public function getDraftSelections($_draftSelection, $_withMeta = false)
  {
    /**
     * @var FantasyCalculator *  $draftCalculator
     */
    $draftCalculator = app(FantasyCalculatorType::FANTASY_DRAFT, [0]);
    $draftMetaTable = $draftCalculator->getDraftCategoryMetaTable();

    // $draftSelection = $_userPlateCard->draftSelection;
    // $draftComplete = $_userPlateCard->draftComplete->toArray();
    $optaStats = OptaPlayerDailyStat::gameParticipantPlayer()
      ->where([
        ['schedule_id', $_draftSelection->schedule_id],
        ['player_id', $_draftSelection->player_id],
      ])->first()?->toArray();

    if (is_null($optaStats)) {
      $optaStats = [];
    }
    $draftCompleteRowWithCate = $draftCalculator->calculate(
      [
        'opta_stats' => $optaStats,
        'selections' => $_draftSelection->toArray(),
      ],
      $_withMeta = true
    );

    $meta = $draftCompleteRowWithCate['meta'];
    unset($draftCompleteRowWithCate['meta']);

    foreach ($draftCompleteRowWithCate as $resultType => $cateSet) {
      foreach ($cateSet as $cate => $levelMap) {
        foreach ($levelMap as $skillName => $level) {
          switch ($resultType) {
            case 'success':
              $draftMetaTable[$cate][$skillName]['header_status'] = $resultType;
              $draftMetaTable[$cate][$skillName]['selection'] = $level;
              break;
            case 'failure':
              $draftMetaTable[$cate][$skillName]['header_status'] = $resultType;
              $draftMetaTable[$cate][$skillName]['selection'] = $level;
              break;
            case 'answer':
              $draftMetaTable[$cate][$skillName]['answer'] = $level;
              break;
            case 'stat':
              $draftMetaTable[$cate][$skillName]['stat'] = $level;
              break;
            default:
              break;
          }
        }
      }
    }

    if ($_withMeta) {
      $draftMetaTable['meta'] = $meta;
    }
    return $draftMetaTable;
  }

  public function dailyActionUpdateOrCreate($_playerId, $_seasonId, $_position, $_type)
  {
    $nowTime = now();
    $nowDateString = $nowTime->toDateString();
    $startOfMonth = $nowTime->copy()->startOfMonth()->toDateString();

    $condition = [
      'player_id' => $_playerId,
      'season_id' => $_seasonId,
      'position' => $_position
    ];

    $data = PlateCardDailyAction::where($condition)
      ->whereBetween('based_at', [$startOfMonth, $nowDateString])
      ->orderByDesc('based_at')
      ->first();

    $playerDailyActionData = array_merge($condition, ['based_at' => $nowDateString]);

    if (empty($data)) {
      $newDailyAction = new PlateCardDailyAction();
      $newDailyAction->fill($playerDailyActionData);
      $newDailyAction[$_type . '_count'] = 1;
    } else {
      if ($data->based_at !== $nowDateString) {
        // 오늘날짜 데이터가 없으면 새로운 데이터 삽입
        $newDailyAction = $data->replicate();
        $newDailyAction['based_at'] = $nowDateString;
      } else {
        // 오늘날짜 데이터가 있으면 업데이트
        $newDailyAction = $data;
      }
      $newDailyAction[$_type . '_count'] += 1;
    }
    $newDailyAction->save();
  }
}
