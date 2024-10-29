<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PlateCardFailLogType extends Enum
{
  const NEW = 'new'; // new player
  const OVERSQUAD = 'oversquad'; // 데이터상 판매되어야할 player 정보.
  const OVERCARD = 'overcard'; // 데이터상 판매되지 말아야할 카드 plate_card.
  const OVERACTIVE = 'overactive'; // sqauds에 현재 시즌에 2개 이상의 active를 갖는 선수
}
