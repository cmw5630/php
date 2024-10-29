<?php

namespace App\Console\Commands\DataControll\Live;

use App\Enums\GameType;
use App\Enums\GradeCardLockStatus;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\PointRefType;
use App\Enums\PointType;
use App\Enums\System\NotifyLevel;
use App\Libraries\Traits\CommonTrait;
use App\Libraries\Traits\GameTrait;
use App\Libraries\Traits\LogTrait;
use App\Models\data\Season;
use App\Models\game\Game;
use App\Models\game\GameJoin;
use App\Models\game\GameLineup;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Redis;
use Str;

class LiveGameChecker
{
  use LogTrait, CommonTrait, GameTrait;
  public function __construct() {}

  private function leagueStartCheck()
  {
    $alarmList = [];
    $alarmList['dday'] = Season::with('league')
      ->has('league')
      ->whereBetween(
        'start_date',
        [now()->addDay()->toDateString(), now()->addDays(7)->toDateString()]
      )
      ->get();

    $alarmList['today'] = Season::with('league')
      ->has('league')
      ->where('start_date', now()->toDateString())
      ->get();

    foreach ($alarmList as $type => $list) {
      foreach ($list as $item) {
        if ($type === 'dday') {
          $redisKeyName = 'alarm_before_league_open_' . $item->league->league_code;
          $socketData = [
            'template_id' => 'stadium-before-league-open',
            'dataset' => [
              'league' => $item->league->league_code,
              'league_id' => $item->league_id,
              'dday' => Carbon::parse($item->start_date)->diffInDays(now()->startOfDay()),
            ],
          ];
        } else {
          $redisKeyName = 'alarm_league_open_' . $item->league->league_code;
          $socketData = [
            'template_id' => 'stadium-league-open',
            'dataset' => [
              'league' => $item->league->league_code,
              'league_id' => $item->league_id,
            ],
          ];
        }

        if (Redis::exists($this->getRedisCachingKey($redisKeyName))) {
          continue;
        }


        $alarm = app('alarm', ['id' => $socketData['template_id']]);
        $alarm->params($socketData['dataset'])->send();

        Redis::set($this->getRedisCachingKey($redisKeyName), 1, 'EX', 86400);
      }
    }
  }

  private function cardLockCheck()
  {
    /**
     * 1. 종료된 game 얻기.
     * 2. An종료된 game 얻기.
     */
    $endedGames = Game::isEnded(true)
      ->isIngameLockReleased(false) // lock_status 처리 안한 game
      ->pluck('id')->toArray();

    // $anEndedGames = Game::isEnded(false)
    //   ->pluck('id')->toArray();

    // $anEndedCardIds = GameLineup::whereHas('gameJoin.game', function ($query) use ($anEndedGames) {
    //   $query->whereIn('id', $anEndedGames);
    // })->pluck('user_plate_card_id');

    if (!empty($endedGames)) {
      GameLineup::whereDoesntHave('gameJoin', function ($fQuery) {
        $fQuery->withoutGlobalScopes()->whereHas('game', function ($query) {
          $query->isEnded(false);
        });
      })->whereHas('gameJoin', function ($fQuery) {
        $fQuery->withoutGlobalScopes()->whereHas('game', function ($query) {
          $query->isEnded(true)->isIngameLockReleased(false);
        });
      })->get()->map(function ($lineup) {
        __endUserPlateCardLock($lineup->user_plate_card_id, GradeCardLockStatus::INGAME, $lineup->schedule_id);
      });

      Game::whereIn('id', $endedGames)->update(['is_ingame_lock_released' => true]);
      __telegramNotify(
        NotifyLevel::INFO,
        'ingame lock_status released success',
        ['game_ids' => $endedGames],
      );
    }
  }

  private function givePrize($_gameId)
  {
    // redis 삭제
    $rankCursor = '0';
    $scoreCursor = '0';
    $prefix = Str::lower(env('APP_NAME') . '_' . 'database_');
    $keysToDelete = [];

    do {
      // user_rank_가 들어가는 키 조회
      [$rankCursor, $rankKeys] = Redis::scan($rankCursor, ['MATCH' => $prefix . 'user_rank_*']);

      // 조회된 키가 있으면 삭제할 키배열에 추가
      if (!empty($rankKeys)) {
        $keysToDelete = array_merge($keysToDelete, $rankKeys);
      }

      // user_score_가 들어가는 키 조회
      [$scoreCursor, $scoreKeys] = Redis::scan($scoreCursor, ['MATCH' => $prefix . 'user_score_*']);

      // 조회된 키가 있으면 삭제할 키배열에 추가
      if (!empty($scoreKeys)) {
        $keysToDelete = array_merge($keysToDelete, $scoreKeys);
      }
      // rank, score 둘 다 스캔이 끝날 때까지 반복
    } while ($rankCursor !== '0' || $scoreCursor !== '0');

    //flip으로 중복 제거 후 key만 추출
    $keysToDelete = array_keys(array_flip($keysToDelete));

    if (!empty($keysToDelete)) {
      foreach ($keysToDelete as $key) {
        Redis::del(Str::replace($prefix, '', $key));
      }
    }

    // 상금지급
    GameJoin::where([
      ['game_id', $_gameId],
      ['reward', '>', 0]
    ])->whereHas('game', function ($query) {
      $query->whereNull('rewarded_at');
    })
      ->get()
      ->map(function ($info) {
        $this->plusUserPointWithLog(
          $info->reward,
          PointType::GOLD,
          PointRefType::REWARD,
          'gameId : ' . $info->game_id,
          $info->user_id
        );
      });

    // game Update
    $game = Game::where('id', $_gameId)->whereNull('rewarded_at')->first();
    $game->rewarded_at = now();
    $game->save();
  }

  private function gameStartCheck(): void
  {
    Game::whereDate('start_date', now()->toDateString())
      ->get()
      ->map(function ($game) {
        $statusCount = $this->getStatusCount($game->id);

        if ($statusCount['status'] !== ScheduleStatus::PLAYING) {
          return null;
        }

        $redisKeyName = 'alarm_game_start_' . $game->id;
        if (Redis::exists($this->getRedisCachingKey($redisKeyName))) {
          return null;
        }

        $socketData = [
          'template_id' => 'stadium-game-start',
          'dataset' => [
            'game_id' => $game->id,
            'round' => $game->game_round_no,
          ],
        ];
        $alarm = app('alarm', ['id' => $socketData['template_id']]);
        $alarm->params($socketData['dataset'])->send($game->gameJoin->pluck('user_id')->toArray());

        Redis::set($this->getRedisCachingKey($redisKeyName), 1, 'EX', 86400);
      });
  }

  private function gameEndCheck(): void
  {
    Game::isEnded(false)
      ->withWhereHas('gameSchedule', function ($query) {
        $query->with('gamePossibleSchedule'); // deleted_at 된 경기는 처리하지 않음으로 변경.
      })->get()
      ->map(function ($game) {
        $endedAts = [];
        foreach ($game['gameSchedule']->toArray() as $gs) {
          $gpSchedule = $gs['game_possible_schedule'];
          if (in_array($gpSchedule['status'], [
            ScheduleStatus::FIXTURE,
            ScheduleStatus::PLAYING,
          ])) {
            return;
          }
          if (
            $gpSchedule['ended_at'] && in_array($gpSchedule['status'], [
              ScheduleStatus::PLAYED,
              ScheduleStatus::AWARDED,
            ])
          ) {
            $endedAts = array_merge($endedAts, [$gpSchedule['ended_at']]);
          }
        }
        if (!empty($endedAts)) {
          $game->completed_at = Carbon::parse(max($endedAts));
        } else {
          $game->completed_at = Carbon::now();
        }
        logger(sprintf('game id(%s) end :', $game->id));
        if ($game->mode !== GameType::TEST) {
          $this->givePrize($game->id);
        }
        $socketData = [
          'template_id' => 'stadium-game-end',
          'dataset' => [
            'game_id' => $game->id,
            'round' => $game->game_round_no,
          ],
        ];
        $alarm = app('alarm', ['id' => $socketData['template_id']]);
        $alarm->params($socketData['dataset'])->send($game->gameJoin->pluck('user_id')->toArray());
        $game->save();
      });
  }

  public function start()
  {
    DB::beginTransaction();
    try {
      $this->gameStartCheck();
      $this->gameEndCheck();
      $this->cardLockCheck();
      $this->leagueStartCheck();
      DB::commit();
      logger('LiveChecker complete!');
    } catch (\Exception $e) {
      logger('LiveChecker Rollback');
      logger($e);
      __telegramNotify(NotifyLevel::CRITICAL, 'live game check failed', '--');
      DB::rollBack();
    }
  }
}
