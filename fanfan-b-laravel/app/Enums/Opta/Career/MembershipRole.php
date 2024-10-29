<?php

namespace App\Enums\Opta\Career;

use BenSampo\Enum\Enum;

final class MembershipRole extends Enum
{
  const PLAYER = 'player';
  const REFEREE = 'referee';
  const COACH = 'coach';
  const STAFF = 'staff';
  const ASSISTANT_COACH = 'assistant coach';
}
