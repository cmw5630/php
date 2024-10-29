<?php

namespace App\Console\Commands\DataControll\PlayerStatusEventHandlers;

use App\Enums\Opta\Squad\PlayerChangeStatus;
use App\Models\data\Squad;

/**
 * COMEBACK, REVIVED 이벤트는 트리거로 생성된 로그를 통해서만 실행된다.
 * (PlateCardUpdator의 insertedPlayerStatusLogsChecker 메소드의 $nonActivePlayerLogs는 status!='active' or active!='yes'에 대해서만 가져오므로 )
 */
// plate_cards에서 로그와 일치하는 player_id에 대해 plate_cards 의 season_id, team_id 를 로그정보와 동일하게 업데이트
/** 
 * 참고
 * 한 선수에 대해 로그에 COMEBACK과 DEACTIVATED 정보가 동일 시점에 처리해야할 때가 있을 것이다.
 * 두 이벤트를 처리할 때 순서를 고려하지 않아도 언제나 결과는 같다.
 * DEACTIVATED의 경우 plate_cards 테이블에서 
 * season_id, team_id, player_id(id)가 같은 경우만 타겟팅하여 delete시키기 때문. 
 * COMEBACK은 player_id(id)에 대해서 업데이트 하기 때문.
 */
class ComebackHandler extends PlayerStatusHandlerAbstract
{
  public $changedType = PlayerChangeStatus::COMEBACK;

  public function __construct()
  {
    parent::__construct();
  }

  public function update(array $_statusLogRow)
  {
    logger($_statusLogRow);
    logger($this->changedType);
    $squadRow = Squad::withTrashed()
      ->currentSeason()
      ->activePlayers()
      ->where(
        [
          'player_id' => $_statusLogRow['player_id'],
          'team_id' => $_statusLogRow['team_id'],
        ]
      )
      ->exceptLeague(config('constant.LEAGUE_CODE.UCL'))
      ->infosForSearch()
      ->orderBy('updated_at', 'desc')
      // ->with(['league:id,league_code'])
      ->first();

    if ($squadRow) {
      $this->upsertOnePlateCard($squadRow->toArray());
    } else {
      logger('check log');
      logger($_statusLogRow);
    }
  }
}
