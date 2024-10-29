<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class QuestCollectionType extends Enum
{
  const LOGIN = 'login';  // 로그인
  const PLATE_BUY = 'plate_buy'; // 플레이트 카드 구매
  const UPGRADE = 'upgrade';  // 플레이트 카드 강화
  const PARTICIPATION = 'participation'; // 게임 참여
  const WEEKLY_QUEST = 'weekly_quest';  // 위클리 퀘스트
  const TRANSFER = 'transfer';  // 마켓 구매 및 판매
}
