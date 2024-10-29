<?php

namespace App\Console\Commands\DataControll\PlayerStatusEventHandlers;

use App\Enums\Opta\Squad\PlayerChangeStatus;
use App\Models\data\Squad;

class RevivedHandler extends PlayerStatusHandlerAbstract
{
  public $changedType = PlayerChangeStatus::REVIVED;
  // public $obseverName = "REVIVED_LISTENER";

  public function __construct()
  {
    parent::__construct();
  }

  public function update(array $_statusLogRow)
  {
    logger($_statusLogRow);
    logger($this->changedType);
    /**
     * COMEBACK, REVIVED 이벤트는 트리거로 생성된 로그를 통해서만 실행된다.
     * (PlateCardUpdator의 __constructor에서 $nonActivePlayerLogs는 status!='active' or active!='yes'에 대해서만 가져오므로 )
     */
    // player_id, season_id, team_id 가 모두 같은 plate_cards restore 처리
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

    $this->upsertOnePlateCard(
      $squadRow->toArray()
    );
  }
}
