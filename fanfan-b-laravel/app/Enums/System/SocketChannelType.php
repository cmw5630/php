<?php

namespace App\Enums\System;

use BenSampo\Enum\Enum;

final class SocketChannelType extends Enum
{
  // INGAME_LIVE type
  const FORMATION = 'formation';
  const SUBSTITUTION = 'substitution';
  const USER_RANK = 'user_rank';
  const PERSONAL_RANK = 'personal_rank';
  const USER_LINEUP = 'user_lineup';
  const PLAYER_CORE_STAT = 'player_core_stat';
  const LINEUP_DETAIL = 'lineup_detail';
  const COMMENTARY = 'commentary';
  const MOMENTUM = 'momentum';
  //
  // Schedule
  const SCHEDULE = 'schedule';
  //
  // gameinfo
  const GAMEINFO = 'gameinfo';
  //
  // timeline
  const TIMELINE = 'timeline';
  //
  //simulation sequence
  const SEQUENCE = 'sequence';
  const STATUS = 'status';
}
