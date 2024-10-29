<?php

namespace App\Enums\Opta\Transfer;

use BenSampo\Enum\Enum;

final class TransferType extends Enum
{
  const TRANSFER = 'Transfer';
  const FREE_TRANSFER = 'Free Transfer';
  const BACK_FROM_LOAN = 'Back from Loan';
  const LOAN = 'Loan';
  const UNKNOWN = 'Unknown';
  const PLAYER_SWAP = 'Player Swap';
  const FREE_AGENT = 'Free Agent';
  const TRIAL = 'Trial';
}
