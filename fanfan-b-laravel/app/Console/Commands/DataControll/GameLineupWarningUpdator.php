<?php

namespace App\Console\Commands\DataControll;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\game\Game;
use App\Models\game\GameLineup;
use App\Models\log\TeamChangeHistory;
use DB;
use Throwable;

class GameLineupWarningUpdator
{
  use FantasyMetaTrait;

  protected $feedNick;

  public function __construct()
  {
    $this->feedNick = 'GLWU';
  }

  private function update()
  {
    $playerChangeTeamMap = [];
    TeamChangeHistory::with(['player.plateCard' => function ($query) {
      $query->withTrashed();
    }])->where('is_treated', false)
      ->get()
      ->map(function ($item) use (&$playerChangeTeamMap) {
        $currentTeamId = null;
        if (is_null($item->player->plateCard)) return;
        if (is_null($item->player->plateCard->delete_at)) {
          $x = $item->player->plateCard()->whereHas('season', function ($query) {
            $query->currentSeasons();
          })->first();
          if (is_null($x)) {
            // 멀리 이적
          } else {
            // 현재 시즌 내 이적
            $currentTeamId = $item->player->plateCard->team_id;
          }
        } else {
          // 멀리 이적
        }
        $playerChangeTeamMap[$item->player->id]['current_team_id'] = $currentTeamId;
        $item->is_treated = true;
        $item->save();
      });

    $teamScheduleMap = [];
    Game::with('gameSchedule.gamePossibleSchedule.schedule')->isEnded(false)->get()
      ->map(function ($item) use (&$teamScheduleMap) {
        $scheduleId = $item->id;
        foreach ($item->gameSchedule as $gp) {
          $homeTeamId = $gp->gamePossibleSchedule->schedule->home_team_id;
          $awayTeamId = $gp->gamePossibleSchedule->schedule->away_team_id;
          $teamScheduleMap[$homeTeamId] = $gp->schedule_id;
          $teamScheduleMap[$awayTeamId] = $gp->schedule_id;
        }
      });

    GameLineup::whereHas('gameJoin.game', function ($query) {
      $query->isEnded(false);
    })->whereIn('player_id', array_keys($playerChangeTeamMap))->get()
      ->map(function ($item) use ($playerChangeTeamMap, $teamScheduleMap) {
        if ($item->team_id === $playerChangeTeamMap[$item->player_id]['current_team_id']) return;
        $item->is_team_changed = 1;
        $item->schedule_id = $teamScheduleMap[$playerChangeTeamMap[$item->player_id]['current_team_id']] ?? null;
        $item->changed_team_id = $playerChangeTeamMap[$item->player_id]['current_team_id'];
        $item->save();
        // 알람
        $socketData = [
          'template_id' => 'lineup-player-warning',
          'target_user_id' => $item->gameJoin->user_id,
          'dataset' => [],
        ];

        $alarm = app('alarm', ['id' => $socketData['template_id']]);
        $alarm->params($socketData['dataset'])->send([$socketData['target_user_id']]);
      });
  }

  public function start(): bool
  {

    logger('GLWU start```');
    switch ($this->parserMode) {
      case ParserMode::SYNC:
        logger('syncmode start--->>>1');
        if (!$this->setUpSyncFantasyParsing($this->feedNick)) return false;
        logger('syncmode start--->>>2');
        switch ($this->syncGroup) {
          case FantasySyncGroupType::ALL:
            # code...
            break;
          case FantasySyncGroupType::DAILY:
            break;
          case FantasySyncGroupType::CONDITIONALLY:
            break;
          default:
            # code...
            break;
        }

        // case ParserMode::PARAM:
        //   if ($this->getParam('mode') === 'all') {
        //     $ids = $this->getAllIds();
        //   }
        //   # code...
        //   break;
        // default:
        //   # code...
        //   break;
    }
    DB::beginTransaction();
    try {
      logger('syncmode start--->>>3');
      $this->update();
      logger('syncmode start--->>>4');
      logger('lineup update success');
      DB::commit();
    } catch (Throwable $th) {
      logger('lineup update fail');
      logger($th);
      DB::rollBack();
      return false;
    }

    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}
