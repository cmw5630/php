<?php

namespace App\Enums\Opta\Schedule;

use BenSampo\Enum\Enum;

final class ScheduleStatus extends Enum
{
  const AWARDED = 'Awarded';
  const CANCELLED = 'Cancelled';
  const FIXTURE = 'Fixture';
  const PLAYED = 'Played';
  const PLAYING = 'Playing';
  const POSTPONED = 'Postponed';
  const SUSPENDED = 'Suspended';
  const NORMAL = [self::AWARDED, self::FIXTURE, self::PLAYED, self::PLAYING];

  /**
   * Get all or a custom set of the enum values.
   *
   * @param  string|array<string>|null  $keys
   *
   * @return array<int, TValue>
   */
  public static function getValues(string|array|null $keys = null): array
  {
    // NORMAL 제거
    $consts = static::getConstants();
    unset($consts['NORMAL']);

    if ($keys === null) {
      return array_values($consts);
    }

    return array_map(
      [static::class, 'getValue'],
      is_array($keys) ? $keys : func_get_args(),
    );
  }
}
