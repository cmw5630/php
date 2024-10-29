<?php

namespace App\Console\Commands\DataControll;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Player\PlayerType;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\OptaPlayerSeasonStat;
use App\Models\data\Squad;
use App\Models\game\Player;
use DB;
use Exception;

class PlayerUpdator
{
  use FantasyMetaTrait;

  protected $feedNick;

  protected $isCompleted = false;

  public function __construct()
  {
    $this->feedNick = 'PYU';
  }

  private function makePlayerDefaultSeasonStat()
  {
    // squads에 들어온 현재시즌 선수에 대한 season stat 기본 생성
    DB::beginTransaction();
    try {
      $opsColumns = (new OptaPlayerSeasonStat())->getTableColumns(true);
      Squad::withTrashed()
        ->whereHas('season', function ($query) {
          $query->currentSeasons();
        })
        ->where('type', PlayerType::PLAYER)
        ->doesntHave('optaPlayerSeasonStat')->get()
        ->map(function ($item) use ($opsColumns) {
          $row = [];
          foreach ($opsColumns as $colName) {
            if ($colName === 'id') continue;
            $row[$colName] = 0;
          }
          $row['season_id'] = $item->season_id;
          $row['team_id'] = $item->team_id;
          $row['player_id'] = $item->player_id;
          $row['last_updated'] = null;
          OptaPlayerSeasonStat::create($row);
        });
      DB::commit();
      logger('PlayerSeasonStat Default update 성공');
    } catch (Exception $e) {
      DB::rollBack();
      logger('PlayerSeasonStat Default update 실패(Rollback)');
      return false;
    }
  }


  public function start(): bool
  {
    /**
     * 플레이어 수집은 SYNC 모드에서만 의미 있으며, 그냥 호출해도 문제는없음.
     */

    switch ($this->parserMode) {
      case ParserMode::SYNC:
        if (!$this->setUpSyncFantasyParsing($this->feedNick)) return false;
        switch ($this->syncGroup) {
          case FantasySyncGroupType::ALL:
            break;
          case FantasySyncGroupType::DAILY:
            break;
          case FantasySyncGroupType::CONDITIONALLY:
            break;
          default:
            # code...
            break;
        }

      case ParserMode::PARAM:
        if ($this->getParam('mode') === 'all') {
        }
        # code...
        break;
      default:
        # code...
        break;
    }

    /**
     * squads 에서 status='active', active='yes' 인 데이터 plate_cards에 넣기
     * 넣은 데이터는 squads에서 deleted_at 처리
     * status_active_...테이블 참조하여 적절하게 처리하는 로직 구현 
     * 역시 status_active..테이블에서 처리한 데이터 deleted_at 처리
     */

    $this->isCompleted = true;

    $squadColumns = (new Squad)->getTableColumns(true);
    $playerColumns = (new Player)->getTableColumns(true);

    // 1.
    DB::beginTransaction();
    try {

      Squad::withTrashed()
        ->where('type', PlayerType::PLAYER)
        ->whereNotIn(
          'player_id',
          Player::pluck('id')->toArray()
        )->get($squadColumns)
        ->unique('player_id')
        ->each(function ($squadOneRow) use ($playerColumns) {
          $oneRow = [];
          foreach ($playerColumns as $playerColName) {
            if ($playerColName === 'id') {
              $oneRow['id'] = $squadOneRow['player_id'];
              continue;
            }
            $oneRow[$playerColName] = $squadOneRow[$playerColName];
          }
          Player::create($oneRow);
        });


      DB::commit();
      logger('Player 테이블 update 성공');
    } catch (Exception $e) {
      DB::rollBack();
      logger('Player 테이블 생성 실패(RollBack)');
      return false;
    }

    // 2.
    $this->makePlayerDefaultSeasonStat();

    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}
