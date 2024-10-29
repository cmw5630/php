<?php

namespace App\Enums\Opta\Squad;

use App\Enums\Opta\Player\PlayerStatus;

final class PlayerChangeStatus extends PlayerStatus
{
  const DEACTIVATED = 'deactivated';
  const COMEBACK = 'comeback';
  const REVIVED = 'revived';
  const OPTAMISTAKE = 'optamistake';
  const UNKNOWN = 'unknown';
}
