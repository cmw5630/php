<?php

namespace App\Enums\Opta\Card;

use BenSampo\Enum\Enum;

class CardGrade extends Enum
{
  // 마지막에 NONE 필수
  const GOAT = 'goat';
  const FANTASY = 'fantasy';
  const ELITE = 'elite';
  const AMAZING = 'amazing';
  const DECENT = 'decent';
  const NORMAL = 'normal';
  const NONE = 'none';

  /*
  * old grades
  const FANTASY = 'fantasy';
  const CRACK = 'crack';
  const PRESTIGE = 'prestige';
  const ELITE = 'elite';
  const AWESOME = 'awesome';
  const NORMAL = 'normal';
  const NONE = 'none';
  */

  public static function getGrades()
  {
    $arr = self::getValues();
    array_pop($arr);
    return $arr;
  }
}
