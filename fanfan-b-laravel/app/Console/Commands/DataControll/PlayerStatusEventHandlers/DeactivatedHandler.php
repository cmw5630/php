<?php

namespace App\Console\Commands\DataControll\PlayerStatusEventHandlers;

use App\Enums\Opta\Squad\PlayerChangeStatus;

class DeactivatedHandler extends PlayerStatusHandlerAbstract
{
  public $changedType = PlayerChangeStatus::DEACTIVATED;
  // public $obseverName = "DEACTIVATED_LISTENER";

  public $plateCardTools;

  public function __construct()
  {
    parent::__construct();
  }

  public function update(array $_statusLogRow)
  {
    logger($_statusLogRow);
    logger($this->changedType);
    // 우선 로그와 player_id, season_id, team_id 가 모두 일치하는 plate_cards softDelete 처리 -> plate_cards 테이블 update 이벤트 -> 변경 로그(plate_card_player_histories 테이블)
    // 1. DEACTIVATED 로그와 player_id, season_id, team_id가 일치하는 선수가 plate_cards에 있다면(앞선 작업인 plate_cards 업데이트,인서트 작업에서 선수정보가 업데이트될 내용이 없던 것이므로) 현재 b2g에서 서비스하는 리그가 아닌 곳으로 이적했다는 의미
    //   - 해당 선수의 user_plate_cards를 CHOICE_MODE로 변경
    // 2. DEACTIVATED 로그와 player_id, season_id, team_id가 일치하는 선수가 plate_cards에 없다면 
    //   - 현재 b2g에서 서비스하는 리그내로 이적했거나(player 정보가 이적된(팀, 시즌) 정보로 업데이트됨)
    //   - 또는 이미 해당로그에 대해서 선수 상태가 처리되어 softDeleted 되었음을 의미함(squads 재수집 시 발생).
    //   do nothing

    if ($this->deleteTargetPlateCard($_statusLogRow)) {
      logger('CHOICE MODE');
      /** 참고
       * user_plate_cards의 해당(로그의) player_id에 해당하는 카드를 모두 CHOICE_MODE로 변경 (lockForUpudate)
       * (강화중인카드, 게임에 라인업으로 이미 구성된 경우 등의 사이드이펙트 고려해야함.)
       *  - 강화 등록된 카드 user_plate_cards 의 status가 CHOICE_MODE로 변경되면 강화 취소 처리
       *  - 이미 라인업으로 구성된 카드 user_plate_cards 의 status가 CHOICE_MODE로 변경되었다면 게임은 그대로 참가, 게임 종료 후 CHOICE_MODE는 라인업 구성 불가
       */
    }
  }
}
