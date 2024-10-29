<?php

namespace App\Console\Commands\DataControll\Live;

use App\Console\Commands\OptaParsers\MA2MatchStatsParser;
use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Schedule\ScheduleWinnerStatus;
use App\Enums\System\NotifyLevel;
use App\Enums\System\SocketChannelType;
use App\Enums\UserStatus;
use App\Events\GameInfoEvent;
use App\Events\IngameSocketEvent;
use App\Events\ScheduleSocketEvent;
use App\Exceptions\Custom\Parser\OTPInsertException;
use App\Libraries\Classes\FantasyCalculator;
use App\Libraries\Traits\DraftTrait;
use App\Libraries\Traits\GameTrait;
use App\Libraries\Traits\LogTrait;
use App\Models\data\EventCard;
use App\Models\data\EventGoal;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\Schedule;
use App\Models\game\DailyStatTimeline;
use App\Models\game\GameJoin;
use App\Models\game\GameLineup;
use App\Models\game\PlayerDailyStat;
use App\Models\log\LiveLog;
use App\Models\data\Substitution;
use App\Models\game\FreeGameLineup;
use App\Models\game\GamePossibleSchedule;
use App\Models\game\GameSchedule;
use App\Models\user\User;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Str;
use Throwable;

// https://api.performfeeds.com/soccerdata/matchstats/1vmmaetzoxkgg1qf6pkpfmku0k/ew92044hbj98z55l8rxc6eqz8?_fmt=json&_rt=b&detailed=yes
// goals의 scorerId 또는 assistPlayerId 가 players 테이블에 없는 경우가 있는 듯. 확인할 것. 또는 Foreign key로 처리하고 테스트해볼 것.
class LiveMA2MatchStatsParser extends MA2MatchStatsParser
{
  use LogTrait, DraftTrait, GameTrait;
  protected const REQUEST_COUNT_AT_ONCE = 20;

  protected $ma2LiveLastUpdateKeyName;
  protected $currentDateTimeUTC;
  protected $paramList;
  protected $timeCheckerArr = [];
  protected $scheduleQueueMap = [];

  // public function __construct()
  // {
  //   parent::__construct();
  // }

  protected function isAllEndStatus($_response): bool
  {
    if (
      $this->getStatus($_response) === ScheduleStatus::PLAYED ||
      $this->getStatus($_response) === ScheduleStatus::AWARDED ||
      $this->getStatus($_response) === ScheduleStatus::SUSPENDED ||
      $this->getStatus($_response) === ScheduleStatus::CANCELLED ||
      $this->getStatus($_response) === ScheduleStatus::POSTPONED
    ) {
      return true;
    }
    return false;
  }


  protected function getStatus($_response): string
  {
    return $_response['liveData']['matchDetails']['matchStatus'];
  }


  protected function getPeriodId($_response): int
  {
    return $_response['liveData']['matchDetails']['periodId'] ??
      $_response['liveData']['matchDetails']['period'][0]['id'];
  }

  protected function getScheduleId($_response): string
  {
    return $_response['matchInfo']['id'];
  }

  protected function getWinner($_response): string
  {
    return $_response['liveData']['matchDetails']['winner'];
  }

  private function getTeamFormationUsed($_response, $_scheduleInst): array|null
  {
    $teamFormationUsedMap = [];
    $result = [
      'home_formation_used' => null,
      'away_formation_used' => null,
    ];

    if (!isset($_response['liveData']['lineUp'])) return $result;

    $lineup = $_response['liveData']['lineUp'];
    foreach ($lineup as $teamSet) {
      if (isset($teamSet['contestantId']) && isset($teamSet['formationUsed'])) {
        $teamFormationUsedMap[$teamSet['contestantId']] = $teamSet['formationUsed'];
      } else {
        $teamFormationUsedMap[$teamSet['contestantId']] = null;
      }
    }
    $result['home_formation_used'] = __getDefault($teamFormationUsedMap, $_scheduleInst->home_team_id, null);
    $result['away_formation_used'] = __getDefault($teamFormationUsedMap, $_scheduleInst->away_team_id, null);

    return $result;
  }

  protected function getScheduleEndedAt($_response)
  {
    if (isset($_response['liveData']['matchDetails']['period'][1]['end'])) {
      return Carbon::parse($_response['liveData']['matchDetails']['period'][1]['end']);
    }
    return false;
  }



  protected function updateGamePossibleScheduleStatus($_response): void
  {
    // schedules에 걸린 트리거로 인해 lock이 걸리므로 트랜잭션 외부에서 콜하자.
    $scheduleId = $_response['matchInfo']['id'];
    $scheduleStatus = $this->getStatus($_response);
    $originInst = GamePossibleSchedule::withTrashed()->where('schedule_id', $scheduleId);

    if (!$originInst->clone()->where('status', $scheduleStatus)->exists()) {
      $gamePossibleScheduleInst = $originInst->first();
      $gamePossibleScheduleInst->status = $scheduleStatus;
      $gamePossibleScheduleInst->save();
    }
  }

  protected function makePubSetSchedule($_response): array|null
  {
    // $periodSet = $_response['liveData']['matchDetails']['period'] ?? [];
    // if (!empty($periodSet)) {
    // }

    $scheduleId = $this->getScheduleId($_response);
    $oldScheduleInst = Schedule::withUnrealSchedule()->find($scheduleId);

    $pubSetSchedule = $this->getSchedulePubSetBase($_response, SocketChannelType::SCHEDULE);
    $pubSetSchedule['round'] = $oldScheduleInst->round;


    if ($oldScheduleInst->status !== $this->getStatus($_response)) {
      $pubSetSchedule['data_updated']['status'] = $this->getStatus($_response);
    }

    if ($this->getStatus($_response) === ScheduleStatus::FIXTURE) {
      if ($oldScheduleInst->started_at !== $_response['matchInfo']['started_at']) {
        $pubSetSchedule['data_updated']['started_at'] = $_response['matchInfo']['started_at'];
      }
    }

    if (
      $this->getStatus($_response) === ScheduleStatus::PLAYING ||
      $this->getStatus($_response) === ScheduleStatus::PLAYED ||
      $this->getStatus($_response) === ScheduleStatus::AWARDED
    ) {
      $newMatchLengthSec = $_response['liveData']['matchDetails']['matchLengthSec'] ?? 0;
      $pubSetSchedule['data_updated']['match_length_sec'] = $newMatchLengthSec;
      $pubSetSchedule['data_updated']['period_id'] = $this->getPeriodId($_response);
      $pubSetSchedule['data_updated']['score_home'] = $_response['liveData']['matchDetails']['scores']['total']['home'];
      $pubSetSchedule['data_updated']['score_away'] = $_response['liveData']['matchDetails']['scores']['total']['away'];
      $pubSetSchedule['data_updated']['injury_times'] = $this->getInjuryTimes($_response);
    }
    if (
      $this->getStatus($_response) === ScheduleStatus::PLAYED ||
      $this->getStatus($_response) === ScheduleStatus::AWARDED
    ) {
      if ($oldScheduleInst->winner !== $this->getWinner($_response)) {
        $pubSetSchedule['data_updated']['winner'] = $this->getWinner($_response);
      }
    }
    $newMatchLengthMin = $_response['liveData']['matchDetails']['matchTime'] ??
      $_response['liveData']['matchDetails']['matchLengthMin'] ??  0;
    if ($newMatchLengthMin != 0) {
      $pubSetSchedule['data_updated']['match_length_min'] = $newMatchLengthMin;
    }
    if (empty($pubSetSchedule['data_updated'])) {
      return null;
    }
    logger("!!!schedule socket data set ok!");
    logger($pubSetSchedule);
    return $pubSetSchedule;
  }

  private function broadcastSchedulePubSet($_response)
  {
    try {
      $schedulePubSet = $this->makePubSetSchedule($_response);
      broadcast(new ScheduleSocketEvent($schedulePubSet));
    } catch (\Exception $e) {
      logger($e);
    }
  }

  protected function updateScheduleAllAttrs($_response): void
  {

    $matchLengthMin = $_response['liveData']['matchDetails']['matchTime'] ??
      $_response['liveData']['matchDetails']['matchLengthMin'] ??  0;

    $scheduleId = $this->getScheduleId($_response);
    // $gameSchedule = GameSchedule::where('schedule_id', $scheduleId)->first();

    $schedule = Schedule::withUnrealSchedule()->whereId($scheduleId)->first();
    // $schedule->last_updated = '';
    $schedule->status = $this->getStatus($_response); // trigger start
    // $gameSchedule->status = $this->getStatus($_response);

    $teamFormationUsed = $this->getTeamFormationUsed($_response, $schedule);
    $schedule->home_formation_used = $teamFormationUsed['home_formation_used'];
    $schedule->away_formation_used = $teamFormationUsed['away_formation_used'];

    if ($this->getStatus($_response) === ScheduleStatus::FIXTURE) {
      $schedule->started_at = $_response['matchInfo']['started_at']; // trigger start
    }

    if (
      $this->getStatus($_response) === ScheduleStatus::PLAYING ||
      $this->getStatus($_response) === ScheduleStatus::PLAYED ||
      $this->getStatus($_response) === ScheduleStatus::AWARDED
    ) {
      $schedule->period_id = $this->getPeriodId($_response);
      $schedule->score_home = $_response['liveData']['matchDetails']['scores']['total']['home'];
      $schedule->score_away = $_response['liveData']['matchDetails']['scores']['total']['away'];
      $schedule->match_length_sec = $_response['liveData']['matchDetails']['matchLengthSec'] ?? 0;
      if ($endedAt = $this->getScheduleEndedAt($_response)) {
        $schedule->ended_at = $endedAt;
      }
    }

    if (
      $this->getStatus($_response) === ScheduleStatus::PLAYED ||
      $this->getStatus($_response) === ScheduleStatus::AWARDED
    ) {
      // - 종료 상태에서만 에만 존재하는 데이터 update
      $schedule->winner = $this->getWinner($_response);
    }
    $schedule->match_length_min = $matchLengthMin;

    // $gameSchedule->saveQuietly();
    try {
      $schedule->saveQuietly();
    } catch (\Exception $e) {
      logger('middle Babo');
      logger($e);
      throw $e;
    }
    logger($this->getScheduleId($_response) . ':schedule status updated!');
  }

  protected function checkStartedAt($_response)
  {
    // Schedule이 Fixture일 때만 검사됨.(경기가 시작되지 않으면 경기 시작시간 20분 후까지 검사됨)
    if (Carbon::now()->subMinutes(20) < Carbon::parse($_response['matchInfo']['started_at'])) {
      $this->updateScheduleAllAttrs($_response);
    }
  }

  private function isPlayingPeriodId($_response): bool
  {
    $availablePeriodIds = [1, 2, 3, 4, 14]; // 14는 최종 상태(Played) 저장을 위해
    $periodId = $this->getPeriodId($_response);
    logger(sprintf('%s 현재 경기 중이고 period id 는 %s', $this->getScheduleId($_response), $periodId));
    if (in_array($periodId, $availablePeriodIds)) {
      return true;
    }
    return false;
  }

  protected function isNormalEndStatus($_response): bool
  {
    if (
      $this->getStatus($_response) === ScheduleStatus::PLAYED ||
      $this->getStatus($_response) === ScheduleStatus::AWARDED
    ) {
      return true;
    }
    return false;
  }

  protected function isCancelStatus($_response): bool
  {
    if (
      $this->getStatus($_response) === ScheduleStatus::SUSPENDED ||
      $this->getStatus($_response) === ScheduleStatus::CANCELLED ||
      $this->getStatus($_response) === ScheduleStatus::POSTPONED
    ) {
      return true;
    }
    return false;
  }

  protected function isSoccerBreakTime($_response): bool
  {
    if (
      $this->getStatus($_response) === ScheduleStatus::PLAYING &&
      !$this->isPlayingPeriodId($_response)
    ) {
      return true;
    }
    return false;
  }


  // protected function hasLockPossibilty($_response): bool
  // {
  //   // syncgroup의 plate card 업데이트 중에는 Lock을 피하기 위해 일시적으로 live 마무리 작업을 보류하도록 하자.
  //   // lock wait를 가능한 하지않도록 하여 short-scheduler가 최대한 주기적으로 실행되도록 함에 의의가 있다.
  //   if (
  //     $this->getStatus($_response) === ScheduleStatus::PLAYED &&
  //     FantasyMeta::whereSyncFeedNick('PCU')->whereActive(YesNo::YES)->exists()
  //   ) {
  //     logger('live 마무리 보류');
  //     return true;
  //   }
  //   return false;
  // }

  private function isLive($_response): bool
  {
    // response 상태정보가 PLAYED인 경우를 live 상태에 포함하는 이유는 최종 마지막 한번을 LIVE로 처리하여 계산하기 위함. PLAYED에 대해 상태 후 처리 필요
    // 후 처리로 schedule이 PLAYED 상태로 변경되면 더이상 live스케쥴러를 타지 않음. !기회는 한번
    if ($this->getStatus($_response) !== ScheduleStatus::FIXTURE) { // 소스가 Fixture 또는 Playing이지만 Opta에서 가져온 상태는 Playing뿐 아니라 Played 나 Suspended이 될 수 있어 예측할 수 없음.
      // 경기가 진행 중일 땐 period_id를 한번 더 검사한다. (휴식시간엔 false 리턴)
      // if (
      //   // $this->isSoccerBreakTime($_response) || // 쉬는 시간에도 파싱 돌아가게(주석처리)
      //   $this->isCancelStatus($_response)
      // ) {
      //   $this->updateScheduleAllAttrs($_response);
      //   return false;
      // }
      return true; // Fixture와 Playing에서 휴식시간 제외한 모든 상태 live 로직에서 분기처리
    }
    $this->checkStartedAt($_response);
    return false; // Fixture라면 live 상태가 아님.
  }

  protected function makeCommonInfos($_publishData) {}

  private function getSchedulePubSetBase(array $_response, string $_type): array
  {
    return [
      'type' => $_type,
      'schedule_id' => $this->getScheduleId($_response),
      'round' => null,
      'data_updated' => [],
      'target_queue' => $this->scheduleQueueMap[$this->getScheduleId($_response)],
    ];
  }

  private function getGameinfoPubSetBase(array $_response, string $_type): array
  {
    return [
      'type' => $_type,
      'season_name' => $_response['matchInfo']['tournamentCalendar']['name'],
      'league_id' => $_response['matchInfo']['competition']['id'],
      'league_code' => $_response['matchInfo']['competition']['competitionCode'],
      'country' => $_response['matchInfo']['competition']['country']['id'],
      'country_code' => null,
      'data_updated' => [],
      'target_queue' => $this->scheduleQueueMap[$this->getScheduleId($_response)],
    ];
  }

  private function getPubSetBase(array $_datas, string $_type): array
  {
    $commonRowOrigin = $_datas['commonRowOrigin'];
    $scheduleId = $commonRowOrigin['schedule_id'];
    $result = [];
    switch ($_type) {
      case SocketChannelType::FORMATION:
        $result = [
          'type' => $_type,
          'league_id' => $commonRowOrigin['league_id'],
          'season_id' => $_datas['commonRowOrigin']['season_id'],
          'schedule_id' => $commonRowOrigin['schedule_id'],
          'data_updated' => [],
        ];
        break;
      case SocketChannelType::USER_RANK:
        $result = [
          'type' => $_type,
          'game_id' => null,
          'data_updated' => [],
        ];
        break;
      case SocketChannelType::PLAYER_CORE_STAT:
        $result = [
          'type' => $_type,
          'game_id' => null,
          'player_id' => null,
          'data_updated' => [],
        ];
        break;
      case SocketChannelType::PERSONAL_RANK:
        $result = [
          'type' => $_type,
          'game_id' => null,
          'user_id' => null,
          'user_name' => null,
          'data_updated' => [],
        ];
        break;
      case SocketChannelType::USER_LINEUP:
        $result = [
          'type' => $_type,
          'game_join_id' => null,
          'user_id' => null,
          'user_name' => null,
          'data_updated' => [],
        ];
        break;
      case SocketChannelType::LINEUP_DETAIL:
        $result = [
          'type' => $_type,
          'player_id' => null,
          'real_time_score' => null,
          'data_updated' => [],
        ];
        break;
      case SocketChannelType::MOMENTUM:
        $result = [
          'type' => $_type,
          'schedule_id' => $scheduleId,
          'data_updated' => [],
        ];
        break;
      case SocketChannelType::TIMELINE:
        $result = [
          'type' => $_type,
          'schedule_id' => $scheduleId,
          'data_updated' => [],
        ];
        break;
    }
    $result['target_queue'] = $this->scheduleQueueMap[$scheduleId];
    return $result;
  }

  private function getGameIds($_scheduleId): array
  {
    logger('-------------------------------');
    logger('game_schedule' . $_scheduleId);

    $gameIds = [];
    $a = GamePossibleSchedule::where('schedule_id', $_scheduleId)
      ->with('gameSchedule')->first()->toArray()['game_schedule'];
    foreach ($a as $item) {
      $gameIds = array_merge($gameIds, [$item['game_id']]);
    }

    return $gameIds;
  }


  private function getScheduleAttrs($_scheduleId): array
  {
    $schedule = Schedule::withUnrealSchedule()->whereId($_scheduleId)->with('home')->with('away')->first()->toArray();
    $scheduleKeys = [
      "id",
      "home_team_id",
      "away_team_id",
      "home_formation_used",
      "away_formation_used",
      "started_at",
      "status",
      "score_home",
      "score_away",
      "period_id",
      "match_length_min",
      "match_length_sec",
    ];
    $teamKeys = [
      'id',
      'code',
      'name',
      'short_name'
    ];
    foreach ($schedule as $key => $value) {
      if (!in_array($key, $scheduleKeys) && !in_array($key, ['home', 'away'])) {
        unset($schedule[$key]);
      }
      if (in_array($key, ['home', 'away'])) {
        foreach ($schedule[$key] as $tKey => $tValue) {
          if (!in_array($tKey, $teamKeys)) {
            unset($schedule[$key][$tKey]);
          }
        }
      }
    }
    return $schedule;
  }

  private function attachScheduleChangedSet(&$_publishDataSet, $_datas): void
  {
    $scheduleId = $_datas['commonRowOrigin']['schedule_id'];
    // [$min, $sec] = $this->getPlayingTime($_datas);
    $conditions = [
      'id' => $scheduleId,
      'score_home' => $_datas['commonRowOrigin']['total_home'] ?? 0,
      'score_away' => $_datas['commonRowOrigin']['total_away'] ?? 0,
      'status' => $_datas['commonRowOrigin']['status'],
      'period_id' => $_datas['commonRowOrigin']['period_id'] ?? null,
      'match_length_min' => $_datas['commonRowOrigin']['schedule_time'] ?? 0,
      'match_length_sec' =>  0,
    ];

    $doesExist = Schedule::withUnrealSchedule()
      ->where(
        $conditions
      )->exists();

    if (!$doesExist) { // 스케쥴 상태가 변경되었음
      $schedule = $this->getScheduleAttrs($scheduleId);
      $schedule = array_merge($schedule, $conditions);
      $_publishDataSet['data_updated']['schedule'] =  $schedule;
    }
  }


  // private function getPlayingTime(array $_datas): array
  // {
  //   $onePeriodMin = $_datas['specifiedAttrs']['period'][0]['length_min'] ?? 0;
  //   $twoPeriodMin = $_datas['specifiedAttrs']['period'][1]['length_min'] ?? 0;
  //   $onePeriodSec = $_datas['specifiedAttrs']['period'][0]['length_sec'] ?? 0;
  //   $twoPeriodSec = $_datas['specifiedAttrs']['period'][1]['length_sec'] ?? 0;

  //   $extraMin = (int)(($onePeriodSec + $twoPeriodSec) / 60);
  //   $sec = ($onePeriodSec + $twoPeriodSec) - ($extraMin * 60);
  //   $min = $onePeriodMin + $twoPeriodMin + $extraMin;

  //   return ['min' => $min, 'sec' => $sec];
  // }


  private function makePubSetFormation(array $_datas): array|null
  {
    $commonRowOrigin = $_datas['commonRowOrigin'];
    $specifiedAttrs = $_datas['specifiedAttrs']; // 포인트들 추가된
    $leagueId = $commonRowOrigin['league_id'];
    $scheduleId = $commonRowOrigin['schedule_id'];
    $seasonId = $_datas['commonRowOrigin']['season_id'];
    $oldDbStates = OptaPlayerDailyStat::where('schedule_id', $scheduleId)
      ->get(['player_id', 'team_id', 'fantasy_point', 'goals', 'goal_assist', 'own_goals', 'yellow_card', 'red_card', 'total_sub_on', 'game_started'])
      ->keyBy('player_id')
      ->toArray();

    $publishDataSetResult = $this->getPubSetBase($_datas, SocketChannelType::FORMATION);

    // logger($specifiedAttrs['player']);
    // foreach ($oldStates as $oldSet) {
    // }
    // logger($oldStates);

    $prePareForNoOldDataPlayer = [
      'goals' => -999,
      'goal_assist' => -999,
      'own_goals' => -999,
      'yellow_card' => -999,
      'red_card' => -999,
      'fantasy_point' => -999,
      'game_started' => 999,
      'total_sub_on' => 999,
    ];


    $comparisonStats = [
      'goals',
      'goal_assist',
      'own_goals',
      'yellow_card',
      'red_card',
      'fantasy_point',
      'game_started',
      'total_sub_on',
    ];

    if (isset($specifiedAttrs['player'])) {
      foreach ($specifiedAttrs['player'] as $rescentOptaPlayerSet) {
        $playerId = $rescentOptaPlayerSet['player_id'];
        $teamId = $rescentOptaPlayerSet['team_id'];
        if (!isset($oldDbStates[$playerId])) {
          $oldDbPlayerSet = $prePareForNoOldDataPlayer;
        } else {
          $oldDbPlayerSet = $oldDbStates[$playerId];
        }
        foreach ($comparisonStats as $statName) {

          if ($statName === 'fantasy_point') {
            $rescentStat = __setDecimal($rescentOptaPlayerSet[$statName], 1);
            $oldStat = __setDecimal($oldDbPlayerSet[$statName], 1);
          } else {
            $rescentStat = (int)$rescentOptaPlayerSet[$statName];
            $oldStat = (int)$oldDbPlayerSet[$statName];
          }

          if ($rescentStat !== $oldStat) {
            foreach (['home', 'away'] as $teamSide) {
              if ($teamId === $_datas['commonRowOrigin'][$teamSide . '_' . 'team_id']) {
                $publishDataSetResult['data_updated'][$teamSide][$playerId]['player_id'] = $rescentOptaPlayerSet['player_id'];
                // $publishDataSetResult['data_updated'][$teamSide][$playerId]['match_name'] = $rescentOptaPlayerSet['match_name'];
                if ($statName === 'fantasy_point') {
                  $publishDataSetResult['data_updated'][$teamSide][$playerId][$statName] = __setDecimal((float)$rescentOptaPlayerSet[$statName], 1);
                } else {
                  $publishDataSetResult['data_updated'][$teamSide][$playerId][$statName] = (int)$rescentOptaPlayerSet[$statName];
                }
                $publishDataSetResult['data_updated'][$teamSide][$playerId]['total_sub_on'] = (int)$rescentOptaPlayerSet['total_sub_on']; // default
                $publishDataSetResult['data_updated'][$teamSide][$playerId]['game_started'] = (int)$rescentOptaPlayerSet['game_started']; // default
              }
            }
          }
        }
      }
    }

    $this->modPubSetSubstitution($publishDataSetResult, $_datas);
    if (empty($publishDataSetResult['data_updated'])) return null;
    $this->attachScheduleChangedSet($publishDataSetResult, $_datas);
    logger(SocketChannelType::FORMATION);
    logger($publishDataSetResult);
    return $publishDataSetResult;
  }

  private function modPubSetSubstitution(&$_publishDataSetResult, $_datas)
  {
    if (!isset($_datas['specifiedAttrs']['substitute'])) return;
    $scheduleId = $_datas['commonRowOrigin']['schedule_id'];
    $substitute = $_datas['specifiedAttrs']['substitute'];

    $homeSubsChanged = false;
    $awaySubsChanged = false;

    $homeSubsCount = 0;
    $awaySubsCount = 0;

    foreach ($substitute as $idx => $subsNewItem) {
      $teamId = $subsNewItem['team_id'];
      $playerId = $subsNewItem['player_on_id'];

      foreach (['home', 'away'] as $teamSide) {
        if ($teamId === $_datas['commonRowOrigin'][$teamSide . '_' . 'team_id']) {
          logger($teamSide . '_idx:' . $idx);
          ${$teamSide . 'SubsCount'}++;
        }
      }

      if (Substitution::where('schedule_id', $scheduleId)
        ->where('player_on_id', $subsNewItem['player_on_id'])
        ->where('player_off_id', $subsNewItem['player_off_id'])->exists()
      ) continue;

      foreach (['home', 'away'] as $teamSide) {
        if ($teamId === $_datas['commonRowOrigin'][$teamSide . '_' . 'team_id']) {
          ${$teamSide . 'SubsChanged'} = true;
          $_publishDataSetResult['data_updated'][$teamSide][$playerId]['player_id'] = $playerId;
          $_publishDataSetResult['data_updated'][$teamSide][$playerId]['substitution']['out']['time'] = $subsNewItem['time_min'];
          $_publishDataSetResult['data_updated'][$teamSide][$playerId]['substitution']['out']['player_id'] = $subsNewItem['player_off_id'];
          $_publishDataSetResult['data_updated'][$teamSide][$playerId]['substitution']['in'] = null;

          $_publishDataSetResult['data_updated'][$teamSide][$subsNewItem['player_off_id']]['player_id'] = $subsNewItem['player_off_id'];
          $_publishDataSetResult['data_updated'][$teamSide][$subsNewItem['player_off_id']]['substitution']['in']['time'] = $subsNewItem['time_min'];
          $_publishDataSetResult['data_updated'][$teamSide][$subsNewItem['player_off_id']]['substitution']['in']['player_id'] = $subsNewItem['player_on_id'];
        }
      }

      $_publishDataSetResult['data_updated'];
    }

    foreach (['home', 'away'] as $teamSide) {
      if (${$teamSide . 'SubsChanged'}) {
        $_publishDataSetResult['data_updated'][$teamSide . '_' . 'subs_info']['count'] = ${$teamSide . 'SubsCount'};
      }
    }


    return;
  }



  private function makePubSetTimeline($_datas): array|null
  {
    $publishDataSetResult = $this->getPubSetBase($_datas, SocketChannelType::TIMELINE);
    if (
      !isset($_datas['specifiedAttrs']['substitute']) &&
      !isset($_datas['specifiedAttrs']['card']) &&
      !isset($_datas['specifiedAttrs']['goal'])
    ) return null;
    $scheduleId = $_datas['commonRowOrigin']['schedule_id'];
    $substitute = $_datas['specifiedAttrs']['substitute'] ?? [];
    $subsOld = Substitution::where('schedule_id', $scheduleId)->get()->keyBy('slot')->toArray();

    $homeSubsChanged = false; // 지바
    $awaySubsChanged = false; // 지바

    $homeSubsCount = 0; // 지바
    $awaySubsCount = 0; // 지바

    $homeTeamId = $_datas['commonRowOrigin']['home_team_id'];
    $awayTeamId = $_datas['commonRowOrigin']['away_team_id'];

    // ** SUBSTITUTE **
    foreach ($substitute as $slotIdx => $subsNewItem) {
      $slotIdx =  chr((int)$slotIdx + 97);
      $teamId = $subsNewItem['team_id'];
      $playerId = $subsNewItem['player_on_id'];

      foreach (['home', 'away'] as $teamSide) {
        if ($teamId === $_datas['commonRowOrigin'][$teamSide . '_' . 'team_id']) {
          logger($teamSide . '_idx:' . $slotIdx);
          ${$teamSide . 'SubsCount'}++;
        }
      }

      if (isset($subsOld[$slotIdx])) {
        if (
          $subsOld[$slotIdx]['slot'] == $slotIdx &&
          $subsOld[$slotIdx]['player_on_id'] == $subsNewItem['player_on_id'] &&
          $subsOld[$slotIdx]['player_off_id'] == $subsNewItem['player_off_id']
        ) {
          continue;
        }
      }

      foreach (['home', 'away'] as $teamSide) {
        if ($teamId === $_datas['commonRowOrigin'][$teamSide . '_' . 'team_id']) {
          ${$teamSide . 'SubsChanged'} = true;
          $publishDataSetResult['data_updated']['substitution'][$teamSide][$slotIdx]['period_id'] = $subsNewItem['period_id'];
          $publishDataSetResult['data_updated']['substitution'][$teamSide][$slotIdx]['time'] = $subsNewItem['time_min'];
          $publishDataSetResult['data_updated']['substitution'][$teamSide][$slotIdx]['out'] = $subsNewItem['player_off_id'];
          $publishDataSetResult['data_updated']['substitution'][$teamSide][$slotIdx]['in'] = $subsNewItem['player_on_id'];
        }
      }
    }

    foreach (['home', 'away'] as $teamSide) {
      if (${$teamSide . 'SubsChanged'}) {
        $publishDataSetResult['data_updated']['substitution'][$teamSide . '_' . 'subs_info']['count'] = ${$teamSide . 'SubsCount'};
      }
    }

    // ** GOAL **

    $homeGoalCount = 0; // 지바
    $awayGoalCount = 0; // 지바
    $homeGoalChanged = false;
    $awayGoalChanged = false;
    $goalResult = [];
    $eventIdTreated = [];
    $goalNew = $_datas['specifiedAttrs']['goal'] ?? [];
    EventGoal::where('schedule_id', $scheduleId)
      ->get()
      ->map(function ($item) use (&$goalNew, &$goalResult, &$eventIdTreated, $homeTeamId, $awayTeamId) {
        $deleteFlag = true;
        foreach ($goalNew as $idx => $goalNewOne) {
          if ($goalNewOne['opta_event_id'] == $item->opta_event_id) {
            $deleteFlag = false;
            $eventIdTreated[] = $item->opta_event_id;
            if (
              !($item['period_id'] == $goalNewOne['period_id'] &&
                $item['time_min'] == $goalNewOne['time_min'] &&
                $item['scorer_id'] == $goalNewOne['scorer_id'] &&
                $item['type'] == $goalNewOne['type'] &&
                $item['assist_player_id'] == ($goalNewOne['assist_player_id'] ?? null))
            ) {
              logger('변화감지');
              // 변화 감지
              $teamSide = null;
              foreach (['home', 'away'] as $anyTeamSide) {
                if ($item->team_id === ${$anyTeamSide . 'TeamId'}) {
                  $teamSide = $anyTeamSide;
                  break;
                }
              }
              $goalResult[$teamSide][$item->slot]['period_id'] = $goalNewOne['period_id'];
              $goalResult[$teamSide][$item->slot]['time_min'] = $goalNewOne['time_min'];
              $goalResult[$teamSide][$item->slot]['player_id'] = $goalNewOne['scorer_id'];
              $goalResult[$teamSide][$item->slot]['type'] = $goalNewOne['type'];
              $goalResult[$teamSide][$item->slot]['assist_player_id'] = $goalNewOne['assist_player_id'] ?? null;
              $goalResult[$teamSide][$item->slot]['opta_event_id'] = $goalNewOne['opta_event_id'];
            }
          }
        }
        if ($deleteFlag) {
          // 취소 처리
          $goalResult['cancelled'] = $item->opta_event_id;
          $item->delete();
        }
      });
    foreach ($goalNew as $idx => $goalNewOne) {
      $slotIdxG = chr((int)$idx + 97);
      if (in_array($goalNewOne['opta_event_id'], $eventIdTreated)) continue;

      foreach (['home', 'away'] as $anyTeamSide) {
        if ($goalNewOne['team_id'] === ${$anyTeamSide . 'TeamId'}) {
          $teamSide = $anyTeamSide;
          break;
        }
      }

      $goalResult[$teamSide][$slotIdxG]['period_id'] = $goalNewOne['period_id'];
      $goalResult[$teamSide][$slotIdxG]['time_min'] = $goalNewOne['time_min'];
      $goalResult[$teamSide][$slotIdxG]['player_id'] = $goalNewOne['scorer_id'];
      $goalResult[$teamSide][$slotIdxG]['type'] = $goalNewOne['type'];
      $goalResult[$teamSide][$slotIdxG]['assist_player_id'] = $goalNewOne['assist_player_id'] ?? null;
      $goalResult[$teamSide][$slotIdxG]['opta_event_id'] = $goalNewOne['opta_event_id'];
    }

    if (!empty($goalResult)) {
      $publishDataSetResult['data_updated']['goal'] = $goalResult;
    }

    // ** CARD **
    $cardResult = [];
    $eventIdTreated = [];
    $cardNew = $_datas['specifiedAttrs']['card'] ?? [];
    EventCard::where('schedule_id', $scheduleId)->whereNotNull('player_id')
      ->get()
      ->map(function ($item) use (&$cardNew, &$cardResult, &$eventIdTreated, $homeTeamId, $awayTeamId) {
        $deleteFlag = true;
        foreach ($cardNew as $idx => $cardNewOne) {
          if (!isset($cardNewOne['player_id'])) {
            continue;
          }
          if ($cardNewOne['opta_event_id'] == $item->opta_event_id) {
            $deleteFlag = false;
            $eventIdTreated[] = $item->opta_event_id;
            if (
              !($item['period_id'] == $cardNewOne['period_id'] &&
                $item['time_min'] == $cardNewOne['time_min'] &&
                $item['player_id'] == $cardNewOne['player_id'] &&
                $item['type'] == $cardNewOne['type'])
            ) {
              logger('변화감지');
              // 변화 감지
              $teamSide = null;
              foreach (['home', 'away'] as $anyTeamSide) {
                if ($item->team_id === ${$anyTeamSide . 'TeamId'}) {
                  $teamSide = $anyTeamSide;
                  break;
                }
              }
              $cardResult[$teamSide][$item->slot]['period_id'] = $cardNewOne['period_id'];
              $cardResult[$teamSide][$item->slot]['time_min'] = $cardNewOne['time_min'];
              $cardResult[$teamSide][$item->slot]['player_id'] = $cardNewOne['player_id'];
              $cardResult[$teamSide][$item->slot]['type'] = $cardNewOne['type'];
              $cardResult[$teamSide][$item->slot]['opta_event_id'] = $cardNewOne['opta_event_id'];
            }
          }
        }
        if ($deleteFlag) {
          // 취소 처리
          $cardResult['cancelled'] = $item->opta_event_id;
          $item->delete();
        }
      });
    foreach ($cardNew as $idx => $cardNewOne) {
      $slotIdxG = chr((int)$idx + 97);
      if (!isset($cardNewOne['player_id']) || in_array($cardNewOne['opta_event_id'], $eventIdTreated)) continue;

      foreach (['home', 'away'] as $anyTeamSide) {
        if ($cardNewOne['team_id'] === ${$anyTeamSide . 'TeamId'}) {
          $teamSide = $anyTeamSide;
          break;
        }
      }

      $cardResult[$teamSide][$slotIdxG]['period_id'] = $cardNewOne['period_id'];
      $cardResult[$teamSide][$slotIdxG]['time_min'] = $cardNewOne['time_min'];
      $cardResult[$teamSide][$slotIdxG]['player_id'] = $cardNewOne['player_id'];
      $cardResult[$teamSide][$slotIdxG]['type'] = $cardNewOne['type'];
      $cardResult[$teamSide][$slotIdxG]['opta_event_id'] = $cardNewOne['opta_event_id'];
    }

    if (!empty($cardResult)) {
      $publishDataSetResult['data_updated']['card'] = $cardResult;
    }

    ///->>

    if (empty($publishDataSetResult['data_updated'])) return null;
    logger(SocketChannelType::TIMELINE);
    logger($publishDataSetResult);
    return $publishDataSetResult;
  }


  private function getResponsePlayerSet($_datas, $_playerId): null|array
  {
    foreach ($_datas['specifiedAttrs']['player'] as $key => $playerSet) {
      if ($playerSet['player_id'] === $_playerId) {
        return $playerSet;
      }
    }
    return null;
  }

  private function isFirstHalf($_datas): bool
  {
    $status = $_datas['commonRowOrigin']['status'];
    $periodId = $_datas['commonRowOrigin']['period_id'] ?? -999;
    if ($status == ScheduleStatus::PLAYING && $periodId == 1) return true;
    return false;
  }

  private function isSecondtHalf($_datas): bool
  {
    $status = $_datas['commonRowOrigin']['status'];
    $periodId = $_datas['commonRowOrigin']['period_id'] ?? -999;
    if ($status == ScheduleStatus::PLAYING && $periodId == 2) return true;
    return false;
  }

  protected function calGameLineup(
    $_datas,
    $_scheduleId,
    $_playerId,
    $_plateCardId,
    $_beforeStatus,
    $_currentStatus,
    $_fipCalculator,
    $_isFree = false,
  ): array {
    $userLineupPubMultiSet = [];

    $targetGameIds = GameSchedule::where('schedule_id', $_scheduleId)->pluck('game_id')->toArray();

    if ($_isFree) {
      $lineUpInst = FreeGameLineup::withHas('gameJoin.user')
        ->withWhereHas('gameJoin', function ($query) use ($targetGameIds) {
          $query->whereIn('game_id', $targetGameIds);
        })->where([
          // ['schedule_id', $_scheduleId], // live(playing) 중인 스케쥴
          ['plate_card_id', $_plateCardId],
        ]);
    } else {
      $lineUpInst = GameLineup::withHas('gameJoin.user')
        ->withWhereHas('gameJoin', function ($query) use ($targetGameIds) {
          $query->whereIn('game_id', $targetGameIds);
        })->with('userPlateCard', function ($query) {
          $query->withoutGlobalScope('excludeBurned');
        })
        ->where([
          // ['schedule_id', $_scheduleId], // live(playing) 중인 스케쥴
          ['player_id', $_playerId],
        ]);
    }

    $lineUpInst->get()
      ->map(
        function ($gameLineupItem)
        use (
          $_isFree,
          &$userLineupPubMultiSet,
          $_scheduleId,
          $_playerId,
          $_beforeStatus,
          $_currentStatus,
          $_datas,
          $_fipCalculator,
        ) {

          $responsePlayerSet = $this->getResponsePlayerSet($_datas, $_playerId);
          if ($responsePlayerSet === null) return;

          if ($_isFree) {
            $userPlateCardAttrs = $gameLineupItem->toArray();
          } else {
            $userPlateCardAttrs = $gameLineupItem->userPlateCard->toArray();
          }

          $inGamePoint = $this->isCancelStatusOnStatus($_currentStatus) ? 0 : $_fipCalculator->calculate([
            'user_card_attrs' => $userPlateCardAttrs,
            'fantasy_point' => $responsePlayerSet['fantasy_point'],
            'is_mom' => $userPlateCardAttrs['is_mom'],
            'schedule_id' => $_scheduleId,
            'origin_stats' => $_datas['specifiedAttrs']['player'][$_playerId],
            'fp_stats' => $_datas['specifiedAttrs']['playerFp'][$_playerId],
          ]);

          $userLineupPubSet = $this->getPubSetBase($_datas, SocketChannelType::USER_LINEUP);
          $userLineupPubSet['game_join_id'] = $gameLineupItem->game_join_id;
          $userLineupPubSet['user_name'] = $gameLineupItem->gameJoin->user->name;
          $userLineupPubSet['user_id'] = $gameLineupItem->gameJoin->user->id;

          if ((float)$gameLineupItem['m_fantasy_point'] !== (float)$inGamePoint) { // 포인트 변동이 있을 때
            $userLineupPubSet['data_updated'][$_playerId]['m_fantasy_point'] = $inGamePoint;
            $userLineupPubSet['data_updated'][$_playerId]['player_id'] = $_playerId;
            // $userLineupPubSet['data_updated'][$playerId]['player_name'] = $gameLineupInst->plateCard['match_name'];
            // $userLineupPubSet['data_updated'][$playerId]['summary_position'] = $summaryPosition;
            // $userLineupPubSet['data_updated'][$playerId]['position'] = $gameLineupInst->userPlateCard->position;

            // 저장
            $gameLineupItem['m_fantasy_point'] = $inGamePoint;
            $gameLineupItem->save();
          }

          // // goals, goal_assist 변동이 있을 때
          // if ((int)$_goalAssist !== (int)$responsePlayerSet['goal_assist']) {
          //   $userLineupPubSet['data_updated'][$_playerId]['player_id'] = $_playerId;
          //   $userLineupPubSet['data_updated'][$_playerId]['right']['goal_assist'] = $responsePlayerSet['goal_assist'];
          // }
          // if ((int)$_goals !== (int)$responsePlayerSet['goals']) {
          //   $userLineupPubSet['data_updated'][$_playerId]['player_id'] = $_playerId;
          //   $userLineupPubSet['data_updated'][$_playerId]['right']['goals'] = $responsePlayerSet['goals'];
          // }
          // if ((int)$_secondYellow !== (int)$responsePlayerSet['second_yellow']) {
          //   $userLineupPubSet['data_updated'][$_playerId]['player_id'] = $_playerId;
          //   $userLineupPubSet['data_updated'][$_playerId]['right']['second_yellow'] = $responsePlayerSet['second_yellow'];
          // }
          // if ((int)$_redCard !== (int)$responsePlayerSet['red_card']) {
          //   $userLineupPubSet['data_updated'][$_playerId]['player_id'] = $_playerId;
          //   $userLineupPubSet['data_updated'][$_playerId]['right']['red_card'] = $responsePlayerSet['red_card'];
          // }
          // if ((int)$_ownGoals !== (int)$responsePlayerSet['own_goals']) {
          //   $userLineupPubSet['data_updated'][$_playerId]['player_id'] = $_playerId;
          //   $userLineupPubSet['data_updated'][$_playerId]['right']['own_goals'] = $responsePlayerSet['own_goals'];
          // }
          // if (
          //   ($gameLineupItem->schedule->status == ScheduleStatus::FIXTURE && $this->isFirstHalf($_datas) &&
          //     $responsePlayerSet['game_started']) || // 전반 시작 전환 + 스타트업 선수
          //   (!$_beforeTotalSubOn && $responsePlayerSet['total_sub_on']) // 교체 시점 교체선수
          // ) {
          //   // goals, goal_assist 뿌려주기
          //   $userLineupPubSet['data_updated'][$_playerId]['player_id'] = $_playerId;
          //   $userLineupPubSet['data_updated'][$_playerId]['right']['goals'] = $responsePlayerSet['goals'];
          //   $userLineupPubSet['data_updated'][$_playerId]['right']['goal_assist'] = $responsePlayerSet['goal_assist'];
          // }


          // '-' -> 등짝 맞고 -> 0으로(우선 프론트에서 처리)
          // if (
          //   isset($userLineupPubSet['data_updated'][$playerId]['right']['goals']) xor
          //   isset($userLineupPubSet['data_updated'][$playerId]['right']['goal_assist'])
          // ) {
          //   if (!isset($userLineupPubSet['data_updated'][$playerId]['right']['goals'])) {
          //     $userLineupPubSet['data_updated'][$playerId]['right']['goals'] =  $goals;
          //   } else {
          //     $userLineupPubSet['data_updated'][$playerId]['right']['goal_assist'] =  $goalAssist;
          //   }
          // }

          if ($_beforeStatus !== $_currentStatus) { // **상태 변동이 있을 때**
            $userLineupPubSet['data_updated'][$_playerId]['player_id'] = $_playerId;
            $userLineupPubSet['data_updated'][$_playerId]['status'] = $_currentStatus;
            if ($_currentStatus === ScheduleStatus::FIXTURE) {
              $userLineupPubSet['data_updated'][$_playerId]['right']['started_at'] = $gameLineupItem->schedule->started_at;
            }
            // else if (
            //   $currentStatus === ScheduleStatus::PLAYED ||
            //   $currentStatus === ScheduleStatus::AWARDED
            // ) {
            //   $userLineupPubSet['data_updated'][$playerId]['right']['goals'] = $responsePlayerSet['goals'];
            //   $userLineupPubSet['data_updated'][$playerId]['right']['goal_assist'] = $responsePlayerSet['goal_assist'];
            // }
          }

          // game_joins 업데이트는 직접하지 않는다(makePubSetUserRank에서 pubSet 만들면서 한다.)
          // 갱신된 플레이어만 pubSet에 포함되므로 total_point를 주지 않고 front단에서 직접 계산해야 함.

          if (!empty($userLineupPubSet['data_updated'])) {
            $userLineupPubMultiSet[] = $userLineupPubSet;
          }
        }
      );
    return $userLineupPubMultiSet;
  }


  protected function makePubMuliSetUserLineup($_datas): array
  {
    /**
     * @var FantasyCalculator $fipCalculator
     */
    $fipCalculator = app(FantasyCalculatorType::FANTASY_INGAME_POINT, [0]);
    $commonRowOrigin = $_datas['commonRowOrigin'];
    $scheduleId = $commonRowOrigin['schedule_id'];
    $currentStatus = $_datas['commonRowOrigin']['status'];
    $userLineupPubMultiSet = [];
    $playerCoreStatPubMultiSet = [];
    OptaPlayerDailyStat::gameParticipantPlayer()->withWhereHas('schedule', function ($query) {
      $query->withUnrealSchedule();
    })->withHas('plateCard')->where('schedule_id', $scheduleId)
      ->get()
      ->map(function ($playerInst) use (
        &$userLineupPubMultiSet,
        &$playerCoreStatPubMultiSet,
        $scheduleId,
        $_datas,
        $currentStatus,
        $fipCalculator,
      ) {
        $playerInst = $playerInst->toArray();
        $playerId = $playerInst['player_id'];
        $plateCardId = $playerInst['plate_card']['id'];
        $beforeStatus = $playerInst['status']; // schedule 이전 상태
        foreach ([true, false] as $isFree) {
          $calSet = $this->calGameLineup(
            $_datas,
            $scheduleId,
            $playerId,
            $plateCardId,
            $beforeStatus,
            $currentStatus,
            $fipCalculator,
            $isFree,
          );
          $userLineupPubMultiSet = array_merge($userLineupPubMultiSet, $calSet);
        }


        $playerCoreStatPubSet = $this->getPubSetBase($_datas, SocketChannelType::PLAYER_CORE_STAT);
        $responsePlayerSet = $this->getResponsePlayerSet($_datas, $playerId);
        $beforeTotalSubOn = $playerInst['total_sub_on'];
        $goals = $playerInst['goals'];
        $goalAssist = $playerInst['goal_assist'];
        $secondYellow = $playerInst['second_yellow'];
        $redCard = $playerInst['red_card'];
        $ownGoals = $playerInst['own_goals'];

        // goals, goal_assist 변동이 있을 때
        if ((int)$goalAssist !== (int)$responsePlayerSet['goal_assist']) {
          $playerCoreStatPubSet['player_id'] = $playerId;
          $playerCoreStatPubSet['data_updated']['goal_assist'] = $responsePlayerSet['goal_assist'];
        }
        if ((int)$goals !== (int)$responsePlayerSet['goals']) {
          $playerCoreStatPubSet['player_id'] = $playerId;
          $playerCoreStatPubSet['data_updated']['goals'] = $responsePlayerSet['goals'];
        }
        if ((int)$secondYellow !== (int)$responsePlayerSet['second_yellow']) {
          $playerCoreStatPubSet['player_id'] = $playerId;
          $playerCoreStatPubSet['data_updated']['second_yellow'] = $responsePlayerSet['second_yellow'];
        }
        if ((int)$redCard !== (int)$responsePlayerSet['red_card']) {
          $playerCoreStatPubSet['player_id'] = $playerId;
          $playerCoreStatPubSet['data_updated']['red_card'] = $responsePlayerSet['red_card'];
        }
        if ((int)$ownGoals !== (int)$responsePlayerSet['own_goals']) {
          $playerCoreStatPubSet['player_id'] = $playerId;
          $playerCoreStatPubSet['data_updated']['own_goals'] = $responsePlayerSet['own_goals'];
        }
        if (
          ($playerInst['schedule']['status'] == ScheduleStatus::FIXTURE && $this->isFirstHalf($_datas) &&
            $responsePlayerSet['game_started']) || // 전반 시작 전환 + 스타트업 선수
          (!$beforeTotalSubOn && $responsePlayerSet['total_sub_on']) // 교체 시점 교체선수
        ) {
          // goals, goal_assist 뿌려주기
          $playerCoreStatPubSet['player_id'] = $playerId;
          $playerCoreStatPubSet['data_updated']['goals'] = $responsePlayerSet['goals'];
          $playerCoreStatPubSet['data_updated']['goal_assist'] = $responsePlayerSet['goal_assist'];
        }

        if (!empty($playerCoreStatPubSet['data_updated'])) {
          foreach ($this->getGameIds($scheduleId) as $gameId) {
            $playerCoreStatPubSet['game_id'] = $gameId;
            $playerCoreStatPubMultiSet[] = $playerCoreStatPubSet;
          }
        }
      });

    if (!empty($userLineupPubMultiSet)) {
      logger(SocketChannelType::USER_LINEUP);
      logger($userLineupPubMultiSet);
    }

    if (!empty($playerCoreStatPubMultiSet)) {
      logger(SocketChannelType::PLAYER_CORE_STAT);
      logger($playerCoreStatPubMultiSet);
    }
    return array_merge($userLineupPubMultiSet, $playerCoreStatPubMultiSet);
  }

  protected function makePubMultiSetUserRank($_datas): array|null
  {

    // 0. game_linups 업데이트(makePubSetUserLineup에서 선행)
    // 1. game_joins 테이블 업데이트
    // 2. game_joins pubSubDataSet 생성
    $commonRowOrigin = $_datas['commonRowOrigin'];
    $scheduleId = $commonRowOrigin['schedule_id'];
    $gameIds = $this->getGameIds($scheduleId);
    $gameRewardMap = [];
    foreach ($gameIds as $gId) {
      $gameRewardMap[$gId] = [];
      $prizedMap = $this->makePrize($gId);
      rsort($prizedMap);
      $i = 1;
      foreach ($prizedMap as $reward) {
        $gameRewardMap[$gId][$i] = $reward;
        $i++;
      }
    }


    foreach ($gameIds as $gameId) {
      $x = $this->getPubSetBase($_datas, SocketChannelType::USER_RANK);
      $x['game_id'] = $gameId;
      $userRankPubSets[$gameId] = $x;
    }
    $PersonalRankPubSets = [];
    foreach ([GameLineup::class, FreeGameLineup::class] as $model) {
      $joinTable = $model::selectRaw(
        $model::getModel()->qualifyColumn('m_fantasy_point') . ' as m_fantasy_point,' .
          $model::getModel()->qualifyColumn('player_id') . ' as player_id,' .
          GameJoin::getModel()->qualifyColumn('id') . ' as id,' .
          GameJoin::getModel()->qualifyColumn('game_id') . ' as game_id,' .
          GameJoin::getModel()->qualifyColumn('user_id') . ' as user_id'
      )->leftJoin(
        GameJoin::getModel()->getTable(),
        $model::getModel()->qualifyColumn('game_join_id'),
        '=',
        GameJoin::getModel()->qualifyColumn('id'),
      )->leftJoin(
        User::getModel()->getTAble(),
        GameJoin::getModel()->qualifyColumn('user_id'),
        '=',
        User::getModel()->qualifyColumn('id'),
      )->whereIn(GameJoin::getModel()->qualifyColumn('game_id'), $gameIds)
        ->where(User::getModel()->qualifyColumn('status'), UserStatus::NORMAL);

      DB::query()
        ->selectRaw(
          'id,
          game_id, 
          user_id,
          SUM(m_fantasy_point) as point,
          RANK() OVER (partition by game_id order by SUM(m_fantasy_point) DESC) as ranking'
        )
        ->fromSub($joinTable, 'reg')
        ->groupBy(['game_id', 'user_id'])
        ->get()->map(function ($aggreRow) use (&$userRankPubSets, &$PersonalRankPubSets, &$_datas, &$gameRewardMap) {
          $rewardMap = $gameRewardMap[$aggreRow->game_id];
          if (empty($rewardMap)) return;
          $reward = 0;
          if (isset($rewardMap[$aggreRow->ranking])) {
            // $userRankPubSet['data_updated'][$aggreRow->user_id]['reward'] = $rewardMap[$aggreRow->ranking];
            $reward = $rewardMap[$aggreRow->ranking];
          }
          $gjInst = GameJoin::with('user')->whereId($aggreRow->id)->first();
          if (!$gjInst) return; //error 처리필요??

          if (
            ((float)($gjInst->point) !== (float)($aggreRow->point) ||
              (int)($gjInst->ranking) !== (int)($aggreRow->ranking) ||
              (int)($gjInst->reward) !== (int)($reward)) &&
            (int)($aggreRow->ranking <= 20)
          ) {
            // $userRankPubSet = $this->getPubSetBase($_datas, SocketChannelType::USER_RANK);
            // $userRankPubSet['game_id'] = $aggreRow->game_id;

            $dataUpdatedSet = [
              'game_join_id' => $aggreRow->id,
              'user_id' => $aggreRow->user_id,
              'user_name' => $gjInst['user']['name'],
              'point' => __setDecimal($aggreRow->point, 1),
              'ranking' => $aggreRow->ranking,
              'reward' => $reward,
            ];
            if (!empty($rewardMap)) $dataUpdatedSet['reward'] = $reward;
            $userRankPubSets[$aggreRow->game_id]['data_updated'][$aggreRow->user_id] = $dataUpdatedSet;
            // $userRankPubSets[] = $userRankPubSet;
          }

          $updateSet = [
            'id' => $aggreRow->id,
            'point' => $aggreRow->point,
            'ranking' => $aggreRow->ranking,
            'reward' => $reward,
          ];
          if (!empty($rewardMap)) $updateSet['reward'] = $reward;
          $gjInst->point = $updateSet['point'];
          $gjInst->ranking = $updateSet['ranking'];
          $gjInst->reward = $updateSet['reward'];
          $gjInst->save();

          if ($gjInst->wasChanged('ranking') || $gjInst->wasChanged('point')) {
            $personalRankPubSet = $this->getPubSetBase($_datas, SocketChannelType::PERSONAL_RANK);
            $personalRankPubSet['game_id'] = $aggreRow->game_id;
            $personalRankPubSet['user_id'] = $gjInst->user_id;
            $personalRankPubSet['user_name'] = $gjInst->user_name;
            $personalRankPubSet['data_updated']['ranking'] = $gjInst->ranking;
            $personalRankPubSet['data_updated']['point'] = $aggreRow->point;
            $PersonalRankPubSets[] = $personalRankPubSet;
            // 소켓 데이터 세트 만들기
          }
        });
    }

    foreach ($userRankPubSets as $gameId => $oneSet) {
      if (!empty($oneSet['data_updated'])) {
        logger(SocketChannelType::USER_RANK . 'with game_id:' . $gameId);
        logger($oneSet);
      }
    }

    if (!empty($PersonalRankPubSets)) {
      logger(SocketChannelType::PERSONAL_RANK);
      logger($PersonalRankPubSets);
    }

    return array_merge($userRankPubSets, $PersonalRankPubSets);
  }

  private function makePubMultiSetLineupDetail($_datas): array
  {
    /**
     * @var FantasyCalculator $fpCalculator
     */
    $fpCalculator = app(FantasyCalculatorType::FANTASY_POINT, [0]);
    $statOrderMap = $fpCalculator->getOrderNumberRefTable();

    $commonRowOrigin = $_datas['commonRowOrigin'];
    $specifiedAttrs = $_datas['specifiedAttrs']; // 포인트들 추가된
    $leagueId = $commonRowOrigin['league_id'];
    $scheduleId = $commonRowOrigin['schedule_id'];
    $seasonId = $_datas['commonRowOrigin']['season_id'];

    // before points
    $beforFpStats = PlayerDailyStat::where('schedule_id', $scheduleId)->get()->keyBy('player_id')->toArray();

    $result = [];
    foreach ($specifiedAttrs['player'] as $uniqueIdx => $playerSet) {
      $playerResult = $this->getPubSetBase($_datas, SocketChannelType::LINEUP_DETAIL);
      $totalPoint = 0.0;
      $playerId = $playerSet['player_id'];
      // current origin, points
      $originWithPoints = $fpCalculator->makePointSetWithRefName($playerSet, true, true, true, false);
      foreach ($originWithPoints as $refName => $originFantasySet) {
        $totalPoint += $originFantasySet['fantasy'];
        if (isset($beforFpStats[$playerId]) && (float)$beforFpStats[$playerId][$refName] !== (float)$originFantasySet['fantasy']) {
          $originFantasySet['order'] = $statOrderMap[$refName];
          $playerResult['data_updated'][$refName] = $originFantasySet;
        }
      }
      if (!empty($playerResult['data_updated'])) {
        $playerResult['player_id'] = $playerId;
        $playerResult['real_time_score'] = __setDecimal($totalPoint, 1);
        $result[] = $playerResult;
      }
    }
    logger($result);
    return $result;
  }


  private function collectLiveDataLog($_response)
  {
    $scheduleId = $_response['matchInfo']['id'];
    if (Str::startsWith($scheduleId, config('constant.UNREAL_SCHEDULE_PREFIX'))) return;
    $leagueId = $_response['matchInfo']['competition']['id'];
    $liveD = LiveLog::where('schedule_id', $scheduleId)->orderByDesc('collect_num')->first();
    if ($liveD === null) {
      $collection_num = 0;
    } else {
      $collection_num = $liveD->collect_num + 1;
    }
    LiveLog::create([
      'collect_num' => $collection_num,
      'schedule_id' => $scheduleId,
      'schedule_id' => $scheduleId,
      'unreal_schedule_id' => config('constant.UNREAL_SCHEDULE_PREFIX') . $scheduleId,
      'league_id' => $leagueId,
      'status' => $this->getStatus($_response),
      'live_data' => $_response,
    ]);

    // logger(json_decode(LiveLog::first('live_data')->toArray()['live_data'], true));
  }

  protected function getDailyStatsFromDatas($_datas, $_player_id): array
  {
    foreach ($_datas['specifiedAttrs']['playerFp'] as $uniqueKey => $playerSet) {
      if ($playerSet['player_id'] === $_player_id) {
        return $playerSet;
      }
    }
  }

  protected function makeStartedAt(&$_response)
  {
    if (isset($_response['matchInfo']['date']) && isset($_response['matchInfo']['time'])) {
      // $_response['matchInfo']['started_at'] = Carbon::parse($_response['matchInfo']['date'] . $_response['matchInfo']['time'])->format('Y-m-d\TH:i:s\Z');
      $_response['matchInfo']['started_at'] = Carbon::parse($_response['matchInfo']['date'] . $_response['matchInfo']['time'])->toDateTimeString();
    }
  }


  private function broadCastLiveStats(array $_dataSets)
  {
    foreach ($_dataSets as $data) {
      if ($data === null) continue;
      try {
        broadcast(new IngameSocketEvent($data));
      } catch (\Exception $e) {
        logger($e); // 임시 try ~ catch
      }
    }
  }


  protected function getCleanSheetTeamId($_datas): null|string
  {
    $winner = $_datas['commonRowOrigin']['winner'];
    if ($winner !== ScheduleWinnerStatus::DRAW) {
      $oppositeTeamSideMap = ['away' => 'home', 'home' => 'away'];
      $winTeamLostGoal = $_datas['commonRowOrigin']['total_' . $oppositeTeamSideMap[$winner]];
      if ($winTeamLostGoal === 0) {
        return Schedule::withUnrealSchedule()->where('id', $_datas['commonRowOrigin']['schedule_id'])
          ->first()[$winner . '_team_id'];
      }
    }
    return null;
  }

  protected function validatePlayerStats($_datas)
  {
    // live 경기 종료(Played 상태) 시 mins_played의 업데이트가 제대로 되었는지 검증한다.
    $validationState = true;
    $winnerGoalState = false;
    $statName = '';
    if ($_datas['commonRowOrigin']['status'] !== ScheduleStatus::PLAYED) return true;
    $cleanSheetTeamId = $this->getCleanSheetTeamId($_datas);
    foreach ($_datas['specifiedAttrs']['player'] as $idx => $statSet) {
      // mins_played
      if (($statSet['game_started'] === 1 || $statSet['total_sub_on'] === 1) && !isset($statSet['mins_played'])) {
        $validationState = false;
        $statName = 'mins_played';
        break;
      }
      // clean_sheet
      if (($cleanSheetTeamId === $statSet['team_id']) && isset($statSet['clean_sheet']) && (int)$statSet['clean_sheet'] !== 1) {
        $validationState = false;;
        $statName = 'clean_sheet';
        break;
      }
      // winning_goal
      if ($_datas['commonRowOrigin']['winner'] !== ScheduleWinnerStatus::DRAW) {
        if ((isset($statSet['winning_goal'])) && ((int)$statSet['winning_goal'] === 1)) {
          $winnerGoalState = true;
        }
      } else { // draw
        $winnerGoalState = true;
      }
    }
    $messags = [
      0 => sprintf('sechedule id(%s) wating opta stats(%s) fitting', $_datas['commonRowOrigin']['schedule_id'], Str::camel($statName)),
      1 => sprintf('sechedule id(%s) opta stats validation(ok!)', $_datas['commonRowOrigin']['schedule_id']),
    ];
    __telegramNotify(NotifyLevel::DEBUG, 'live wrapup', $messags[(int)$validationState * (int)$winnerGoalState]);
    return $validationState;
  }

  protected function makeTeamSide($_datas, $_response): array
  {
    foreach ($_response['matchInfo']['contestant'] as $teamSet) {
      $_datas['commonRowOrigin'][$teamSet['position'] . '_' . 'team_id'] = $teamSet['id'];
    }
    return $_datas;
  }


  private function isLineupInDB($_response): bool
  {
    $scheduleId = $this->getScheduleId($_response);
    $teamCount = OptaPlayerDailyStat::where('schedule_id', $scheduleId)->get()->unique('team_id')->count();
    if ($teamCount == 2) {
      return true;
    }
    return false;
  }

  private function hasLineup($_response): bool
  {
    if (isset($_response['liveData']['lineUp'])) return true;
    return false;
  }

  private function checkPreMatchLineup($_response, $_commonInfoToStore, $_specifiedInfoToStore)
  {
    $_urlKey = 'preMatchCheck__';
    if ($this->hasLineup($_response) && !$this->isLineupInDB($_response)) {
      logger('preMatch formation 수집 start');
      $datas = $this->makeTeamSide($this->middleCalPointProcess($this->preProcessResponse($_urlKey, $_response)), $_response);
      $formationPubDataSet = $this->makePubSetFormation($datas);
      $this->insertDatas($_commonInfoToStore, $_specifiedInfoToStore, $datas);
      logger('preMatch formation 수집 end');

      $this->broadCastLiveStats([$formationPubDataSet]);
      logger('preMatch formation BroadCast start');
    }
    // Fixture 경기 1시간 전부터 라인업이 없으면 라인업 저장
  }

  private function checkLineupChanged($_response,  $_datas)
  {
    $scheduleId = $this->getScheduleId($_response);
    $players = $_datas['specifiedAttrs']['player'];
    $opdsInst = OptaPlayerDailyStat::where([
      ['schedule_id', $scheduleId],
      ['formation_place', '>', 0],
    ]);
    $lineupCount = $opdsInst->clone()->count();

    if ($lineupCount > 22) {
      $dbLineupPlayerIds = $opdsInst->clone()->pluck('player_id')->toArray();
      $optaLineupPlayerIds = [];

      foreach ($players as $idx => $playerItem) {
        $optaLineupPlayerIds[] = $playerItem['player_id'];
      }
      $disappearedPlayers = array_diff($dbLineupPlayerIds, $optaLineupPlayerIds);
      $opdsInst->whereIn('player_id', $disappearedPlayers)->delete(); // forceDelete() 로 변경 여부 고려
    }
  }

  private function getGameStatus($_gameId): string
  {
    // $gameId = $this->getGameId($this->getScheduleId($_response));
    return $this->getStatusCount($_gameId)['status'];
  }


  private function getRedisGameStatusKey($_gameId): string
  {
    return SocketChannelType::GAMEINFO . '_' . 'status' . '_' . $_gameId;
  }


  private function makePubSetGameinfo($_response): void
  {
    $pubDataSet = $this->getGameinfoPubSetBase($_response, SocketChannelType::GAMEINFO);
    $gameIds = $this->getGameIds($this->getScheduleId($_response)); // !TODO (xyz007) 게임 ID가 multi 로 변경필요.
    foreach ($gameIds as $gameId) {
      $beforeGameStatus = '';
      $currentGameStatus = $this->getGameStatus($gameId);
      if (Redis::exists($this->getRedisGameStatusKey($gameId))) {
        $beforeGameStatus = Redis::get($this->getRedisGameStatusKey($gameId));
      } else {
        Redis::set($this->getRedisGameStatusKey($gameId), $currentGameStatus, 'EX', 3600 * 24 * 7);
      }

      if ($beforeGameStatus !== $currentGameStatus) {
        $pubDataSet['data_updated']['id'] = $gameId;
        $pubDataSet['data_updated']['game_status'] = $currentGameStatus;
      }

      if (!empty($pubDataSet['data_updated'])) {
        logger($pubDataSet);
        broadcast(new GameInfoEvent($pubDataSet));
      }
    }
  }


  public function asyncJob(
    string $_urlKey,
    array $_response,
    array $_commonInfoToStore = null,
    array $_specifiedInfoToStore = null,
    $_realStore = false,
  ): void {
    // logger('abc->');
    // $this->getGameIds($this->getScheduleId($_response));
    $time_pre = microtime(true);
    logger($this->makePubSetGameinfo($_response));
    //->임시
    $this->collectLiveDataLog($_response);
    // <-
    logger($this->getScheduleId($_response) . ' start' . '(' . $this->getStatus($_response) . ')' . '-' . $this->getPeriodId($_response));

    (new LiveMA6CommentaryParser($this->getScheduleId($_response), $this->scheduleQueueMap[$this->getScheduleId($_response)]))->start(true);
    $this->makeStartedAt($_response);
    $this->broadcastSchedulePubSet($_response);
    $isLive = $this->isLive($_response);
    // GameScedule에서 Fixture와 Playing인 것들에 대해서만 수집이 들어감.
    if (!$isLive) {
      logger($this->getScheduleId($_response) . 'is not Live Status' . '(' . $this->getStatus($_response) . ')' . '-' . $this->getPeriodId($_response));
      if ($this->getStatus($_response) === ScheduleStatus::FIXTURE) {
        logger($this->getScheduleId($_response) . 'check PreMatch Lineup' . '(' . $this->getStatus($_response) . ')' . '-' . $this->getPeriodId($_response));
        $this->checkPreMatchLineup($_response, $_commonInfoToStore, $_specifiedInfoToStore);
      }
    } else { // live
      logger($this->getScheduleId($_response) . 'is Live Status' . '(' . $this->getStatus($_response) . ')' . '-' . $this->getPeriodId($_response));
      $this->checkTime('data pre process start');
      $datas = $this->makeTeamSide($this->middleCalPointProcess($this->preProcessResponse($_urlKey, $_response)), $_response);
      $this->checkLineupChanged($_response, $datas);

      // data 체크->
      if (!$_realStore) {
        logger($datas['commonRowOrigin']);
        logger($datas['specifiedAttrs']);
        $this->generateColumnNames();
        dd('-xTestx-');
      }
      // data 체크<-

      // if (!$this->validatePlayerStats($datas)) continue;

      //
      $this->checkTime('makePublishDataSet start');
      // -> db update 전 broadcast를 위한 diff 계산
      $formationPubDataSet = $this->makePubSetFormation($datas);
      $timelinePubDataSet = $this->makePubSetTimeline($datas);
      $lineupPubDataMultiSet = [];
      $userAndPersonalRankPubDataMultiSet = [];
      $lineupDetailPubDataMultiSet = [];
      // if (
      //   $this->getStatus($_response) === ScheduleStatus::PLAYING ||
      //   $this->getStatus($_response) === ScheduleStatus::PLAYED ||
      //   $this->getStatus($_response) === ScheduleStatus::AWARDED
      // ) {
      $lineupPubDataMultiSet = $this->makePubMuliSetUserLineup($datas);
      $userAndPersonalRankPubDataMultiSet = $this->makePubMultiSetUserRank($datas);
      $lineupDetailPubDataMultiSet = $this->makePubMultiSetLineupDetail($datas);
      // }

      // <-

      // 1. get old data(player_daily_stats)
      // 2. compare with current data
      // 3. make publish data
      // logger($datas);
      //

      DB::beginTransaction();
      try {
        Schema::connection('api')->disableForeignKeyConstraints();
        $this->checkTime('updateScheduleAllAttrs start');
        $this->updateScheduleAllAttrs($_response); // 경기 schedule에 관련된 모든 상태 업데이트(중요: 트랜잭션 내에서)
        $this->checkTime('insertDatas start');
        logger('insertDatas start');
        $this->insertDatas($_commonInfoToStore, $_specifiedInfoToStore, $datas);
        logger('insertDatas end');
        $this->checkTime('applyMOM start');
        $this->applyMOM($datas);

        try { // 임시 try ~ catch
          $this->checkTime('collectTeamDailtyStatTimeline start');
          if (!$this->isSoccerBreakTime($_response) && !$this->isCancelStatus($_response)) { // HT에 schedule_time 정보가 없음
            $this->collectTeamDailtyStatTimeline($_response, $datas);
          }
        } catch (\Exception $e) {
          logger($e);
        }
        logger('escape !');

        $this->checkTime('broadCastLiveStats start');
        $this->broadCastLiveStats(
          array_merge(
            [$formationPubDataSet],
            [$timelinePubDataSet],
            $userAndPersonalRankPubDataMultiSet,
            $lineupPubDataMultiSet,
            $lineupDetailPubDataMultiSet,
          )
        );

        // <<--
        logger("before commit");
        DB::commit();
        logger("after commit");
        $this->updateGamePossibleScheduleStatus($_response);
        logger(sprintf("[LIVE LOGIC SUCCESS!](%s)", $this->getScheduleId($_response)));
      } catch (Exception $e) {
        DB::rollBack();
        logger('live scheduler fail, check log');
        report(new OTPInsertException(null, ['feed_nick' => $this->feedNick, 'path' => $this->current_url_extra,], $e));
      } finally {
        Schema::connection('api')->enableForeignKeyConstraints();
      }
    }
    $time_post = microtime(true);
    logger('difftime:' . $time_post - $time_pre);
    $this->notificate($_response['matchInfo']['id'], $time_pre, $time_post, 23); // !구현 필요 - 파라미터로 시작 시간 넘겨 찍기
    logger($this->getScheduleId($_response) . ' end');
    logger($this->scheduleQueueMap);
  }

  protected function addScheduleQueueMap(array $_response, string $_queueName)
  {
    $this->scheduleQueueMap[$this->getScheduleId($_response)] = $_queueName;
  }

  protected function insertOptaDatasToTables(
    array $_responses,
    array $_commonInfoToStore = null,
    array $_specifiedInfoToStore = null,
    $_realStore = false,
  ): void {
    $q = config('constant.LIVE_QUEUES');
    $queueCount = count($q);
    $queuePlace = 0;
    foreach ($_responses as $urlKey => $response) { // 비동기 응답s 처리
      logger($this->getScheduleId($response) . ':' . $this->getStatus($response));
      $this->addScheduleQueueMap($response, $q[$queuePlace]);

      // try {
      //   $this->asyncJob(
      //     $urlKey,
      //     $response,
      //     $_commonInfoToStore,
      //     $_specifiedInfoToStore,
      //     $_realStore
      //   );
      // } catch (\Exception $e) {
      //   logger($e);
      // }

      try {
        dispatch(
          function () use ($urlKey, $response, $_commonInfoToStore, $_specifiedInfoToStore, $_realStore) {
            $this->asyncJob(
              $urlKey,
              $response,
              $_commonInfoToStore,
              $_specifiedInfoToStore,
              $_realStore
            );
          }
        )->catch(function (Throwable $e) {
          logger('job fail');
          logger($e);
        })->onQueue($q[$queuePlace]);
        // ->through([(new WithoutOverlapping($q[$queuePlace] . '_' . $this->getScheduleId($response)))]);
        // sleep(0.5);
        logger('ququeCount:' . $queueCount);
        logger($queuePlace);
        logger($q[$queuePlace]);
        $queuePlace = (++$queuePlace) % $queueCount;
      } catch (\Exception $e) {
        logger($e);
      }
    }
  }

  private function getLivePossibleIds()
  {
    /**
     * 만들어진 game내에서 스케쥴되므로 schedules이 아닌 game_schedules의 status를 바라보고 
     * 이 status는 live 중 또는 daily(scheduler) 내에서 schedules의 상태 업데이트로 인한 트리거로 업데이트됨. 
     * 참고: 실시간 상태가 아닌 DB에 저장된 상태이므로 update는 한 박자 느리며 이를 이용해 단 1번의 Played 상태의 수집이 가능
     */
    return GamePossibleSchedule::whereHas('league', function ($query) {
      return $query->parsingAvalilable();
    })->whereIn('status', [ScheduleStatus::FIXTURE, ScheduleStatus::PLAYING])->whereHas(
      'schedule',
      function ($query) {
        return $query->where('started_at', '<', Carbon::now()->addMinutes(config('constant.COLLECT_MA2_LIVE_START_MINUTE_BEFORE')));
      }
    )->pluck('schedule_id')->toArray();
  }

  private function getUnrealLivePossibleIds()
  {
    return GamePossibleSchedule::whereHas('league', function ($query) {
      return $query->parsingAvalilable();
    })->whereIn('status', [ScheduleStatus::FIXTURE, ScheduleStatus::PLAYING])->whereHas(
      'schedule',
      function ($query) {
        return $query->onlyUnrealSchedule()
          ->whereHas('liveLog', function ($liveQuery) {
            $liveQuery->where('is_checked', false);
          })
          ->where('started_at', '<', Carbon::now()->addMinutes(10));
      }
    )->pluck('schedule_id')->toArray();
  }


  public function startCollectLiveLog()
  {
    $ids = $this->getLivePossibleIds();
    logger(count($ids));
    $idChunks = array_chunk($ids, self::REQUEST_COUNT_AT_ONCE);
    $totalChucks = count($idChunks);
    foreach ($idChunks as $idx => $idChunk) {
      $responses = $this->optaRequest($idChunk);
      foreach ($responses as $idx => $response) {
        if ($this->isLive($response)) {
          $this->collectLiveDataLog($response);
        }
      }
    }
  }

  private function getInjuryTimes($_response): array
  {
    $newInjuryOne = (int)(($_response['liveData']['matchDetails']['period'][0]['announcedInjuryTime'] ?? null) / 60);
    $newInjuryTwo = (int)(($_response['liveData']['matchDetails']['period'][1]['announcedInjuryTime'] ?? null) / 60);

    $scheduleInst = Schedule::withUnrealSchedule()->whereId($this->getScheduleId($_response))->first();

    if ($newInjuryOne != $scheduleInst->injury_one) {
      $result['injury_one'] = $newInjuryOne;
      $scheduleInst->injury_one = $newInjuryOne;
    }
    if ($newInjuryTwo != $scheduleInst->injury_two) {
      $result['injury_one'] = $newInjuryTwo;
      $scheduleInst->injury_two = $newInjuryTwo;
    }
    $scheduleInst->save();

    return [
      'injury_one' => $newInjuryOne,
      'injury_two' => $newInjuryTwo,
    ];
  }

  private function getScheduleMin($_datas): int
  {
    try {
      if (isset($_datas['commonRowOrigin']['schedule_time'])) {
        return $_datas['commonRowOrigin']['schedule_time'];
      } else if (isset($_datas['commonRowOrigin']['match_length_min'])) {
        return $_datas['commonRowOrigin']['match_length_min'];
      }
      return 0;
    } catch (\Exception $e) {
      logger('!!!!!!!!!!!시연전 수정 에러!!!!!!!!!');
      logger($e);
      logger('!!!!!!!!!!!시연전 수정 에러!!!!!!!!!');
    }
    return 0;
    // return $_datas['commonRowOrigin']['status'] === ScheduleStatus::PLAYING ? $_datas['commonRowOrigin']['schedule_time'] : $_datas['commonRowOrigin']['match_length_min'];
  }

  private function getScheduleTotalMin($_response, $_datas): int
  {
    $scheduleMinute = $this->getScheduleMin($_datas);
    $scheduleId = $_datas['commonRowOrigin']['schedule_id'];
    $periodId = $_datas['commonRowOrigin']['period_id'];

    if ((int)$periodId == 1) {
      return $scheduleMinute;  // 전반
    }

    $injuryTimes = $this->getInjuryTimes($_response);

    if ((int)$periodId == 2) {
      $maxBeforePeriodMin = DailyStatTimeline::where([
        ['schedule_id', $scheduleId],
        ['period_id', 1],
      ])->max('schedule_minute');

      if ($maxBeforePeriodMin === null) {
        return $scheduleMinute;
      }

      $onePeriodInjuryMin = max($maxBeforePeriodMin - 45, $injuryTimes['injury_one'],  0);
      return $scheduleMinute + $onePeriodInjuryMin; // 후반
    } else if ((int)$periodId == 14) {
      $maxBeforePeriodMin = DailyStatTimeline::where([
        ['schedule_id', $scheduleId],
        ['period_id', 2],
      ])->max('schedule_total_minute');

      if ($maxBeforePeriodMin === null) {
        return $scheduleMinute;
      }

      return $maxBeforePeriodMin + 1; // 종료
    } else {
      return $scheduleMinute + 100; // debuging
    }
  }



  private function collectTeamDailtyStatTimeline($_respons, $_datas)
  {
    // Played면 schedule_time이 사라지고 match_length_min이 생기지만 이 두 정보는 일치하지 않음.(어떻게 처리할지 생각해보기)
    // $matchMins = $_datas['commonRowOrigin']['schedule_time'];
    // $preriodId = $_datas['commonRowOrigin']['period_id'];
    $scheduleMinute = $this->getScheduleMin($_datas);
    $scheduleTotalMinute = $this->getScheduleTotalMin($_respons, $_datas);
    $periodId = $_datas['commonRowOrigin']['period_id'];
    $scheduleId = $_datas['commonRowOrigin']['schedule_id'];
    $status = $_datas['commonRowOrigin']['status'];

    $skipCols = [
      'id',
      'season_id',
      'schedule_id',
      'team_id',
      'team_side',
      'period_id',
      'status',
      'schedule_minute',
      'schedule_total_minute',
      'momentum_value',
      'momentum_y'
    ];

    $this->checkTime('_a');
    $columnsAgr = (new DailyStatTimeline)->getTableColumns(true);
    $this->checkTime('_b');
    $columnsAgr = array_filter($columnsAgr, function ($col) use ($skipCols) {
      return !in_array($col, $skipCols);
    });
    $this->checkTime('_c');

    $statsAggregated = [];
    OptaPlayerDailyStat::withWhereHas('schedule', function ($query) {
      $query->withUnrealSchedule();
    })->where('schedule_id', $scheduleId)
      ->get()
      ->map(function ($playerStats) use (&$statsAggregated, $columnsAgr, $status, $periodId, $scheduleMinute, $scheduleTotalMinute) {
        $playerStats = $playerStats->toArray();
        if (!isset($statsAggregated[$playerStats['team_id']])) {
          foreach ($columnsAgr as $colName) {
            $statsAggregated[$playerStats['team_id']][$colName] = 0;
          }

          if ($playerStats['schedule']['away_team_id'] === $playerStats['team_id']) {
            $statsAggregated[$playerStats['team_id']]['team_side'] = 'away';
          } else {
            $statsAggregated[$playerStats['team_id']]['team_side'] = 'home';
          }

          $statsAggregated[$playerStats['team_id']]['schedule_id'] = $playerStats['schedule_id'];
          $statsAggregated[$playerStats['team_id']]['season_id'] = $playerStats['season_id'];
          $statsAggregated[$playerStats['team_id']]['team_id'] = $playerStats['team_id'];
          $statsAggregated[$playerStats['team_id']]['status'] = $status;
          $statsAggregated[$playerStats['team_id']]['period_id'] = $periodId;
          $statsAggregated[$playerStats['team_id']]['schedule_minute'] = $scheduleMinute;
          $statsAggregated[$playerStats['team_id']]['schedule_total_minute'] = $scheduleTotalMinute;
        }
        foreach ($columnsAgr as $colName) {
          $statsAggregated[$playerStats['team_id']][$colName] += (int)$playerStats[$colName];
        }
      });
    $this->checkTime('_d');

    foreach ($statsAggregated as $teamId => $updateSet) { // team별 계산
      /**
       * @var FantasyCalculator $fmCalculator 
       */
      try {
        $fmCalculator = app(FantasyCalculatorType::FANTASY_MOMENTUM, [0]);
        $momentum_value = $fmCalculator->calculate($updateSet);
        $statsAggregated[$teamId]['momentum_value'] = $momentum_value;
      } catch (\Exception $e) {
        logger($e);
      }
    }

    $oldOne = DailyStatTimeline::where([
      ['schedule_id', $scheduleId],
      ['schedule_total_minute', $scheduleTotalMinute - 1], // 이전 경기
    ])
      ->orderByDesc('schedule_total_minute')
      ->limit(2)
      ->get(['team_id', 'team_side', 'schedule_total_minute', 'momentum_value']);


    $beforeHomeMomentumValue = 0;
    $beforeAwayMomentumValue = 0;
    $currentHomeMomentumValue = 0;
    $currentAwayMomentumValue = 0;

    if (!empty($oldOne)) {
      $oldOne = $oldOne->keyBy('team_side')->toArray();
      $beforeHomeMomentumValue = $oldOne['home']['momentum_value'] ?? 0;
      $beforeAwayMomentumValue = $oldOne['away']['momentum_value'] ?? 0;
    }

    foreach ($statsAggregated as $updateSet) {
      if ($updateSet['team_side'] === 'away') {
        $currentAwayMomentumValue = $updateSet['momentum_value'];
      } else {
        $currentHomeMomentumValue = $updateSet['momentum_value'];
      }
    }

    $currentMomentumY = ($currentHomeMomentumValue - $beforeHomeMomentumValue) - ($currentAwayMomentumValue - $beforeAwayMomentumValue);
    foreach ($statsAggregated as $teamId => $updateSet) {
      $statsAggregated[$teamId]['momentum_y'] = $currentMomentumY;
      $updateSet['momentum_y'] = $currentMomentumY;
      DailyStatTimeline::updateOrCreateEx(
        [
          'schedule_id' => $updateSet['schedule_id'],
          'team_id' => $updateSet['team_id'],
          'period_id' => $updateSet['period_id'],
          'schedule_minute' => $updateSet['schedule_minute'],
        ],
        $updateSet,
        false,
        true,
      );
    }

    $pubData = $this->getPubSetBase($_datas, SocketChannelType::MOMENTUM);
    $pubData['data_updated'][] = [$scheduleTotalMinute => $currentMomentumY];
    broadcast(new IngameSocketEvent($pubData));

    $this->checkTime('_e');
  }


  private function checkTime(string $_keyName)
  {
    $deltaFromBefore = count($this->timeCheckerArr) ? microtime(true) - $this->timeCheckerArr[count($this->timeCheckerArr) - 1]['microtime'] : 0;
    // if ($deltaFromBefore > 1) {
    $this->timeCheckerArr[] = [
      'subject' => $_keyName,
      'start_time' => (string)Carbon::now(),
      'microtime' => microtime(true),
      'delta_from_before' => $deltaFromBefore,
    ];
    // }
  }

  private function notificate($_scheduleId, float $_timePre, float $_timePost, $_timeCompare)
  {
    $exec_time = $_timePost - $_timePre;
    if ($exec_time > $_timeCompare) {
      __telegramNotify(NotifyLevel::INFO, sprintf('(%s)Live Time Check((total(%s seconds)) over %s seconds) at %s', $_scheduleId, $exec_time, $_timeCompare, (string)Carbon::now()), $this->timeCheckerArr);
    }
    $this->timeCheckerArr = [];
  }


  protected function parse(bool $_act): bool
  {
    $ids = $this->getLivePossibleIds();

    // logger(count($ids));
    logger(json_encode($ids));
    // throw (new OTPInsertException(null, []));
    //logger('x');
    // optaParser 설정 -->>
    $this->setKeysToIgnore([
      'VAR',
    ]);
    $this->setKGsToCustom(['matchInfo/contestant', 'liveData/lineUp', 'liveData/goal', 'liveData/card', 'liveData/substitute']);
    // $this->setGlueChildKeys([]);
    // optaParser 설정 <<--
    $this->setKeyNameTransMap(['matchStatus' => 'status', 'matchInfoId' => 'matchId', 'touches' => 'touchesOpta']);


    $idChunks = array_chunk($ids, self::REQUEST_COUNT_AT_ONCE);
    foreach ($idChunks as $idx => $idChunk) {
      $responses = $this->optaRequest($idChunk);
      $this->insertOptaDatasToTables(
        $responses,
        null,
        [
          [
            'specifiedInfoMap' => ['player' => OptaPlayerDailyStat::class],
            'conditions' => ['schedule_id', 'player_id']
          ],
          // [
          //   'specifiedInfoMap' => ['teamStats' => OptaTeamDailyStat::class], // liveWrapupDraft에서 수집
          //   'conditions' => ['schedule_id', 'team_id']
          // ],
          [
            'specifiedInfoMap' => [self::playerDailySpecifiedKey => PlayerDailyStat::class],
            'conditions' => ['schedule_id', 'player_id'] // update condidions
          ],
          // [
          //   'specifiedInfoMap' => ['teamStats' => TeamDailyStat::class],  // liveWrapupDraft에서 수집
          //   'conditions' => ['schedule_id', 'team_id']
          // ],
          [
            'specifiedInfoMap' => ['substitute' => Substitution::class],
            'conditions' => ['schedule_id', 'slot',],
          ],
          [
            'specifiedInfoMap' => ['goal' => EventGoal::class],
            'conditions' => ['schedule_id', 'opta_event_id'],
          ],
          [
            'specifiedInfoMap' => ['card' => EventCard::class],
            'conditions' => ['schedule_id', 'opta_event_id'],
          ],
        ],
        $_act,
      );
    }
    return true;
  }

  public function unrealParse(bool $_act = false): bool
  {
    $ids = $this->getUnrealLivePossibleIds();
    logger(json_encode($ids));
    // logger(count($ids));
    // throw (new OTPInsertException(null, []));
    //logger('x');
    // optaParser 설정 -->>
    $this->setKeysToIgnore([
      'VAR',
    ]);
    $this->setKGsToCustom(['matchInfo/contestant', 'liveData/lineUp', 'liveData/goal', 'liveData/card', 'liveData/substitute']);
    // $this->setGlueChildKeys([]);
    // optaParser 설정 <<--
    $this->setKeyNameTransMap(['matchStatus' => 'status', 'matchInfoId' => 'matchId', 'touches' => 'touchesOpta']);

    foreach ($ids as $id) {
      $liveInst = (LiveLog::where([
        'unreal_schedule_id' => $id,
        'is_checked' => false,
      ])->limit(1)->first());
      $liveInst->is_checked = true;
      $liveInst->save();


      $unrealDatas = $liveInst['live_data'];
      $unrealDatas['matchInfo']['id'] = config('constant.UNREAL_SCHEDULE_PREFIX') . $unrealDatas['matchInfo']['id'];

      $this->insertOptaDatasToTables(
        ['unreal_' . $unrealDatas['matchInfo']['id']  => $unrealDatas],
        null,
        [
          [
            'specifiedInfoMap' => ['player' => OptaPlayerDailyStat::class],
            'conditions' => ['schedule_id', 'player_id']
          ],
          // [
          //   'specifiedInfoMap' => ['teamStats' => OptaTeamDailyStat::class], // liveWrapupDraft에서 수집
          //   'conditions' => ['schedule_id', 'team_id']
          // ],
          [
            'specifiedInfoMap' => [self::playerDailySpecifiedKey => PlayerDailyStat::class],
            'conditions' => ['schedule_id', 'player_id'] // update condidions
          ],
          // [
          //   'specifiedInfoMap' => ['teamStats' => TeamDailyStat::class],  // liveWrapupDraft에서 수집
          //   'conditions' => ['schedule_id', 'team_id']
          // ],
          [
            'specifiedInfoMap' => ['substitute' => Substitution::class],
            'conditions' => ['schedule_id', 'slot'],
          ],
          [
            'specifiedInfoMap' => ['goal' => EventGoal::class],
            'conditions' => ['schedule_id', 'opta_event_id'],
          ],
          [
            'specifiedInfoMap' => ['card' => EventCard::class],
            'conditions' => ['schedule_id', 'opta_event_id'],
          ],
        ],
        $_act,
      );
    }
    return true;
  }
}
