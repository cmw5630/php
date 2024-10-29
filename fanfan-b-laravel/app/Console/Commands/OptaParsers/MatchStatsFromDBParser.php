<?php

namespace App\Console\Commands\OptaParsers;

use App\Exceptions\Custom\Parser\OTPInsertException;
use App\Libraries\Classes\Exception;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\Schedule;
use App\Models\data\OptaTeamDailyStat;
use LogEx;

class MatchStatsFromDBParser extends BaseOptaParser
{
  protected const CHUNK_SIZE = 100;

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'matchstatsFromDB';
    $this->feedNick = 'MA2';
  }

  protected function customParser($_parentKey, $_key, $_value)
  {
    //
  }

  protected function customCommonParser($_parentKey, $_key, $_value)
  {
    //
  }

  protected function parse(bool $_act)
  {
    $ids = Schedule::whereHas('season.league', function ($leagueQuery) {
      $leagueQuery->whereIn('league_code', [
        'EPL', 'KL1', 'ALP', 'BSA', 'FL1', 'SPD', 'ISA'
      ]);
    })
      ->oldest('started_at')->pluck('id')->toArray();
    __loggerEx($this->feedType, 'schedule total count : ' . count($ids));


    // 100 개씩 쪼갬
    $idChunks = array_chunk($ids, self::CHUNK_SIZE);
    $totalChucks = count($idChunks);

    $targetModel = OptaPlayerDailyStat::class;
    $this->setColumns((new $targetModel)->getTableColumns(true));
    __loggerEx($this->feedType, 'PlayerDailyStat start->');
    foreach ($idChunks as $idx => $idChunk) {
      __loggerEx($this->feedType, 'loop $i : ' . $idx + 1 . ' / ' . $totalChucks);
      $playerStats = __originTable('player_game_stats')
        ->whereIn('matchId', $idChunk)
        ->get()
        ->toArray();

      $condition = [
        'schedule_id',
        'player_id'
      ];

      $this->storeData($playerStats, $targetModel, $condition);
    }
    __loggerEx($this->feedType, '<-PlayerDailyStat end');

    __loggerEx($this->feedType, 'TeamDailyStat start->');
    $targetModel = OptaTeamDailyStat::class;
    $this->setColumns((new $targetModel)->getTableColumns(true));
    foreach ($idChunks as $idx => $idChunk) {
      __loggerEx($this->feedType, 'loop $i : ' . $idx + 1 . ' / ' . $totalChucks);
      $teamStats = __originTable('team_game_stats')
        ->whereIn('matchId', $idChunk)
        ->get()
        ->toArray();

      $condition = [
        'schedule_id',
        'team_id'
      ];

      $this->storeData($teamStats, $targetModel, $condition);
    }
    __loggerEx($this->feedType, '<-TeamDailyStat end');
  }

  private function storeData($_data, $_model, $_conditions)
  {
    foreach ($_data as $item) {
      $stat = [];
      foreach ($item as $key => $val) {
        $stat[$this->ubTransKeysName($this->correctKeyName(null, $key))] = $val;
      }

      $storeCondition = [];
      foreach ($_conditions as $condition) {
        $storeCondition[$condition] = $stat[$condition];
      }

      $stat = $this->correctColumnsAndDatetimeType($stat);

      try {
        $_model::withTrashed()->updateOrCreateEx(
          $storeCondition,
          array_diff($stat, $storeCondition),
        );
      } catch (Exception $e) {
        // LogEx::error($this->feedType, 'Error Info(Critical) - ' . '위치 : ' . 'common insert' . ' / ' . 'feed : ' . $this->feedType . ' / ' . 'error message : ' . $e->getMessage());
        report(new OTPInsertException(null, ['feed_nick' => $this->feedNick, 'path' => $this->current_url_extra,], $e, $stat));
      }
    }
  }
}
