<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class GameType extends Enum
{
  const NORMAL = 'normal';      // 일반게임
  const FREE = 'free';          // 무료게임
  const SPONSOR = 'sponsor';    // 무료-스폰서
  const SURVIVOR = 'survivor';  // 스페셜레이스-서바이벌
  const LIMITED = 'limited';    // 스페셜레이스-리미티드
  const TEST = 'test';    // 가상 라이브
}
