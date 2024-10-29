<?php

namespace App\Enums\FantasyCalculator;

use BenSampo\Enum\Enum;

class FantasyDraftCategoryType extends Enum
{
  const SUMMARY = 'summary';
  const ATTACKING = 'attacking';
  const PASSING = 'passing';
  const DEFENSIVE = 'defensive';
  const DUEL = 'duel';
  const GOALKEEPING = 'goalkeeping';
}
