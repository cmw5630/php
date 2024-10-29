<?php

namespace App\Libraries\Traits;

use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Models\game\Game;
use App\Models\game\GameJoin;
use App\Models\game\GamePossibleSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use Str;

trait GameTrait
{
  use CommonTrait;

  public function getStatusCount($_param)
  {
    // !TODO (xyz007) game_schedules -> possible_schedules
    $result['Fixture'] = 0;
    $result['Played'] = 0;
    $result['Cancelled'] = 0;
    $result['Playing'] = 0;

    if (is_numeric($_param)) {
      $schedules = GamePossibleSchedule::withWhereHas('schedule', function ($query) {
        $query->withUnrealSchedule();
      })->whereHas('gameSchedule', function ($gameSchedule) use ($_param) {
        $gameSchedule->where('game_id', $_param);
      })
        ->get()
        ->toArray();
    } else {
      $schedules = $_param;
      $schedules = __sortByKey($schedules, 'started_at', 'ASC');
    }
    $allCnt = count($schedules);

    foreach ($schedules as $schedule) {
      if ($schedule['status'] === ScheduleStatus::FIXTURE) {
        $result[ScheduleStatus::FIXTURE]++;
      } else if ($schedule['status'] === ScheduleStatus::PLAYED || $schedule['status'] === ScheduleStatus::AWARDED) {
        $result[ScheduleStatus::PLAYED]++;
      } else if ($schedule['status'] === ScheduleStatus::CANCELLED || $schedule['status'] === ScheduleStatus::POSTPONED || $schedule['status'] === ScheduleStatus::SUSPENDED) {
        $result[ScheduleStatus::CANCELLED]++;
      } else if ($schedule['status'] === ScheduleStatus::PLAYING) {
        $result[ScheduleStatus::PLAYING]++;
      }
    }

    if ($allCnt === $result[ScheduleStatus::FIXTURE]) {
      $result['status'] = ScheduleStatus::FIXTURE;
    } else if ($allCnt === $result[ScheduleStatus::PLAYED]) {
      $result['status'] = ScheduleStatus::PLAYED;
    } else if ($allCnt === $result[ScheduleStatus::CANCELLED]) {
      $result['status'] = ScheduleStatus::CANCELLED;
    } else if (now()->isBefore($schedules[0]['started_at'] ?? $schedules[0]['schedule']['started_at'])) {
      $result['status'] = ScheduleStatus::FIXTURE;
    } else {
      $result['status'] = ScheduleStatus::PLAYING;
    }

    return $result;
  }

  public function getRanking($_userId)
  {
    return GameJoin::where('user_id', $_userId)
      ->selectRaw('user_id, game_id, RANK() OVER(PARTITION BY game_id ORDER BY point DESC) AS rnum')
      ->get()->keyBy('game_id')->toArray();
  }

  public function getDateStringSet()
  {
    $now = now();
    $startDayOfWeek = Carbon::parse(config('constant.OPEN_DATE'))->dayOfWeek;
    if ($now->dayOfWeek === $startDayOfWeek) {
      $thisWeek['start'] = $now;
    } else {
      $thisWeek['start'] = $now->previous($startDayOfWeek);
    }
    $thisWeek['end'] = $thisWeek['start']->clone()->addDays(6);
    $nextWeek['start'] = $thisWeek['end']->clone()->addDay();
    $nextWeek['end'] = $nextWeek['start']->clone()->addDays(6);

    $dateStringSet['this_week'] = array_map(fn($val) => $val->toDateString(), $thisWeek);
    $dateStringSet['next_week'] = array_map(fn($val) => $val->toDateString(), $nextWeek);

    return $dateStringSet;
  }

  public function makePrize($_gameId)
  {
    $gameBase = Game::where('id', $_gameId)->whereNull('rewarded_at');
    // $gameBase = Game::where('id', $_gameId);

    if (!$gameBase->clone()->exists()) {
      logger('이미 상금을 준 게임');
      return [];
    }

    // 게임정보
    $gameInfo = $gameBase->first()->toArray();

    // 총 상금/인원
    $rewardsRank = $joinAllPerson = GameJoin::where('game_id', $_gameId)->count();
    if ($joinAllPerson > 5) {
      $rewardsRank = bcmul(bcmul($joinAllPerson, $gameInfo['prize_rate']), 0.01);
    }
    $allRewards = $gameInfo['rewards'];

    $redisKeyName = 'main_game_prize_' . $_gameId . '_' . $joinAllPerson;
    if (Redis::exists($this->getRedisCachingKey($redisKeyName))) {
      return json_decode(Redis::get($this->getRedisCachingKey($redisKeyName)), true);
    }

    //top5
    $topPerson = (int) $rewardsRank;
    if ($rewardsRank > 5) {
      $topPerson = 5;
    }
    $top5Rate = config('constant.INGAME_REWARDS_TOP5_RATE');
    $sumTop5Rewards = 0;
    $rewardsByrank = [];
    foreach ($top5Rate as $rank => $rate) {
      $rewards = (int) bcmul($allRewards, bcmul($rate, 0.01, 2));
      array_push($rewardsByrank, $rewards);
      $sumTop5Rewards = (int) bcadd($sumTop5Rewards, $rewards);
    }

    // 남은 인원/상금
    $constTargetTotalCnt = $remainCnt = $rewardsRank - $topPerson;
    $remainRewards = $allRewards - $sumTop5Rewards;

    if ($remainCnt > 0) {
      // 구간별 인원/상금 뽑기 + 랭킹별 상금 저장
      $remainPerson = 0;
      foreach (config('constant.INGAME_REWARDS_SECTION_RATE') as $section => $rate) {
        $person = bcmul($constTargetTotalCnt, bcmul($rate['P'], 0.01, 10), 10);
        if ($person < 1) {
          $person = 1;
        } else {
          $person = (int)round($person);
        }

        $nextRemainCnt = max($remainCnt - $person, 0);
        if ($nextRemainCnt === 0) $person = $remainCnt;

        $reward = (int)bcmul($remainRewards, bcmul($rate['R'], 0.01, 10));

        for ($i = $remainPerson; $i < $remainPerson + $person; $i++) {
          $rewardsByrank[5 + $i] = $reward;
        }

        $remainPerson += $person;
        $realSectionCnt[$section] = ['P' => $person, 'R' => $reward];

        $remainCnt = $nextRemainCnt;
        if ($nextRemainCnt === 0) {
          break;
        }
      }
    }

    // 전체랭킹 동순위 체크
    $duple = GameJoin::where([
      ['game_id', $_gameId],
      ['ranking', '<=', $rewardsRank]
    ])->selectRaw('ranking, COUNT(ranking) AS dupleCnt')->groupBy('ranking')
      ->havingRaw('COUNT(ranking) > 1');
    if ($duple->exists()) {  // 동순위 있음
      $duple->get()->map(function ($info) use ($rewardsRank, &$rewardsByrank) {
        if ($info->ranking === $rewardsRank) {
          $totalDupleRewards = $rewardsByrank[$rewardsRank - 1];
          $rewardsByrank[$rewardsRank - 1] = (int) bcdiv($totalDupleRewards, $info->dupleCnt);
        } else {
          $totalDupleRewards = 0;
          for ($i = $info->ranking; $i < $info->ranking + $info->dupleCnt; $i++) {
            $totalDupleRewards = (int) bcadd($totalDupleRewards, $rewardsByrank[$i - 1]);
          }
          if ($totalDupleRewards > 0) {
            $dupleRewards = (int) bcdiv($totalDupleRewards, $info->dupleCnt);
            for ($i = $info->ranking; $i < $info->ranking + $info->dupleCnt; $i++) {
              $rewardsByrank[$i - 1] = $dupleRewards;
            }
          }
        }
      });
    }

    // dd($rewardsByrank);

    // redis 삭제
    $prefix = Str::lower(env('APP_NAME') . '_' . 'database_');
    $redisRankArr = Redis::keys('main_game_prize_' . $_gameId . '_*');

    foreach ($redisRankArr as $redisRank) {
      $redisKeyName = Str::replace($prefix, '', $redisRank);
      Redis::del($redisKeyName);
    }

    Redis::set($this->getRedisCachingKey($redisKeyName), json_encode($rewardsByrank), 'EX', 86400);

    return $rewardsByrank;
  }
}
