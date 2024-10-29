<?php

namespace App\Enums\FantasyCalculator;

use BenSampo\Enum\Enum;

final class FantasyCalculatorType extends Enum
{
  const FANTASY_POINT = 'fantasyPoint';
  const FANTASY_RATING = 'fantasyRating';
  const FANTASY_POINT_C = 'fantasyPointC';
  const FANTASY_RATING_C = 'fantasyRatingC';
  const FANTASY_CARD_GRADE = 'fantasyCardGrade';
  const FANTASY_DRAFT = 'fantasyDraft';
  const FANTASY_DRAFT_EXTRA = 'fantasyDraftExtra';
  const FANTASY_POWER_RANKING = 'fantasyPowerRanking';
  const FANTASY_INGAME_POINT = 'fantasyIngamePoint';
  const FANTASY_POINT_REWARD = 'fantasyPointReward';
  const FANTASY_MOMENTUM = 'fantasyMomentum';
  const FANTASY_PROJECTION = 'fantasyProjection';
  const FANTASY_BURN = 'fantasyBurn';
  const FANTASY_FREE_GAME = 'fantasyFreeGame';
  const FANTASY_OVERALL = 'fantasyOverall';
  // const FANTASY_PLATE_CARD_PRICE = 'fantasyPlateCardPrice'; // Fantasy Calculator에서 계산하기 복잡하여 따로 계산한다.
}
