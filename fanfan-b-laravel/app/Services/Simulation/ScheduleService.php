<?php

namespace App\Services\Simulation;

class ScheduleService
{
  function generateFixture($teams)
  {
    $schedules = [];
    $teamCount = count($teams);
    $teamId = [];
    for ($i = 0; $i < $teamCount; $i++) {
      $teamId[$i] = $i;
    }

    for ($i = 0; $i < $teamCount - 1; $i++) {
      for ($j = 0; $j < $teamCount / 2; $j++) {
        $schedules[] = [$teams[$teamId[$j]], $teams[$teamId[$teamCount - $j - 1]]];
      }
      for ($j = 0; $j < $teamCount - 1; $j++) {
        $teamId[$j] = ($teamId[$j] + 1) % ($teamCount - 1);
      }
    }
    $fixtures = array_chunk($schedules, 10);
    $reverse = [];
    foreach ($fixtures as $round => $fixture) {
      foreach ($fixture as $item) {
        $reverse[$round][] = array_reverse($item);
      }
    }
    $fixtures = [...$fixtures, ...$reverse];

    foreach ($fixtures as $round) {
      $fixtures[] = $round;
    }

    return $fixtures;
  }
}