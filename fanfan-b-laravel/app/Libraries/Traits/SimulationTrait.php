<?php

namespace App\Libraries\Traits;

use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\SimulationCalculator\SimulationCategoryType;
use App\Models\game\DraftComplete;
use App\Models\simulation\SimulationApplicant;
use App\Models\simulation\SimulationLineupMeta;
use App\Models\simulation\SimulationOverall;
use App\Models\simulation\SimulationSchedule;
use App\Models\user\UserPlateCard;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Exception;
use Illuminate\Support\Facades\Redis;
use Schema;

trait SimulationTrait
{
  use CommonTrait;

  public function getServerTime($_server, $_time = null)
  {
    if (is_null($_time)) {
      $_time = now();
    }

    $timezone = config('simulationpolicies')['server'][$_server]['timezone'] ?? null;

    if ($_server === 'europe' || !isset($timezone)) {
      return $_time;
    }

    return $_time->tz($timezone);
  }

  // 1. 등급 및 mom 가산점
  public function getGradeMomPoint($config, $grade, $isMom)
  {
    // 1. 등급가산점
    $gradePoint = $config['grade'][$grade];

    $allPoint = $gradePoint;

    // 2. mom 가산점
    $momPoint = $config['mom'][$isMom];
    $allPoint += $momPoint;

    return $allPoint;
  }

  public function getCategoryLevelPoint($config, $allPoint, $_userPlateCard, $baseOverall, &$updateArr)
  {
    // 3. 카테고리별 레벨
    $draftSkill = DraftComplete::where('user_plate_card_id', $_userPlateCard->id)->first();
    foreach (SimulationCategoryType::getValues() as $category) {
      $column = $category . '_level';
      if (!is_null($_userPlateCard->$column)) {
        ${$category . 'Point'} = $config['category'][$_userPlateCard->$column];

        // 4. 특수스탯 적중
        foreach (config('fantasydraft.FANTASYDRAFT_REFERENCE_TABLE_V0.Categories')[$category] as $stat => $info) {
          if ($draftSkill->$stat > 0) {
            $overallColumn = $config['special_skill']['stat'][$stat][0];
            if (in_array($overallColumn, SimulationCategoryType::getValues())) {
              $overallColumn .= 'Stat';
            }
            if (!isset(${$overallColumn . 'Point'})) ${$overallColumn . 'Point'}  = 0;
            ${$overallColumn . 'Point'} += $draftSkill->$stat;

            if (count($config['special_skill']['stat'][$stat]) > 1) {
              $overallColumn2 = $config['special_skill']['stat'][$stat][1];
              if (!isset(${$overallColumn2 . 'Point'})) ${$overallColumn2 . 'Point'}  = 0;
              // duels_won 은 컬럼2개 값으로 보므로
              // 2,3점 항목을 맞췄어도 각각 1,2점의 가산점을 부여받음.
              ${$overallColumn2 . 'Point'} += $draftSkill->$stat - 1;
              ${$overallColumn . 'Point'} -= 1;
            }
          }
        }
      }
    }

    $columns = config('fantasyoverall.column');
    $categoryCntArr = array_count_values($columns);
    foreach ($columns as $stat => $category) {
      $base = $baseOverall->$stat;
      // if (is_null($baseOverall)) {
      //   $base = 45;
      // }

      if (!isset($overall[$category]['total'])) $overall[$category]['total'] = 0;
      if (!isset($updateArr[$category . '_overall'])) $updateArr[$category . '_overall']  = 0;
      if ($category !== SimulationCategoryType::PHYSICAL) {
        if (!isset(${$category . 'Point'})) ${$category . 'Point'} = 0;
        $tempStat = $stat;
        if (in_array($stat, SimulationCategoryType::getValues())) {
          $tempStat .= 'Stat';
        }
        if (!isset(${$tempStat . 'Point'})) ${$tempStat . 'Point'} = 0;

        $additonalPoint = $allPoint + ${$category . 'Point'} + ${$tempStat . 'Point'};

        $point = $base + $additonalPoint;
        if ($_userPlateCard->is_free) {
          $point = $this->applyPenalty($point);
        }
        $updateArr[$stat] = ['overall' => $point, 'add' => $additonalPoint];

        $overall[$category]['total'] += $point;
        $updateArr[$category . '_overall']  =  BigDecimal::of($overall[$category]['total'])->dividedBy(BigDecimal::of($categoryCntArr[$category]), 0, RoundingMode::HALF_UP)->toInt();
      } else {
        if ($_userPlateCard->is_free) {
          $base = $this->applyPenalty($base);
        }
        $physicalPoint = $allPoint;
        $updateArr[$stat] = ['overall' => $base + $physicalPoint, 'add' => $physicalPoint];
        $overall[$category]['total'] += $base + $physicalPoint;
        $updateArr[$category . '_overall'] =  BigDecimal::of($overall[$category]['total'])->dividedBy(BigDecimal::of($categoryCntArr[$category]), 0, RoundingMode::HALF_UP)->toInt();
      }
    }

    return $updateArr;
  }

  // 7.sub_position 찾기
  public function getFindPosition($_userPlateCard, $_baseOverall, &$updateArr)
  {
    // formaion_used 찾기
    $isFree = $_userPlateCard->is_free;
    if (!$isFree) {
      $updateArr['player_id'] = $_userPlateCard->draftSelection->player_id;

      $stat = $_userPlateCard->draftSelection->schedule->optaPlayerDailyStat->where('player_id', $updateArr['player_id'])->first()?->toArray();

      if (!is_null($stat) && !is_null($stat['formation_used'])) {
        $formationUsed = $stat['formation_used'];
      } else {
        if ($_userPlateCard->draftSelection->schedule->home_team_id === $_userPlateCard->draft_team_id) {
          $formationUsed = $_userPlateCard->draftSelection->schedule->home_formation_used;
        } else if ($_userPlateCard->draftSelection->schedule->away_team_id === $_userPlateCard->draft_team_id) {
          $formationUsed = $_userPlateCard->draftSelection->schedule->away_formation_used;
        }
      }

      //place_index 찾기
      if (!is_null($stat) && $stat['formation_place'] > 0 && !$isFree) {
        logger("HERE1");
        $updateArr['sub_position'] = config('formation-by-sub-position.formation_used')[$formationUsed][$stat['formation_place']];

        if ($_baseOverall->sub_position === $updateArr['sub_position']) {
          $updateArr['second_position'] = $_baseOverall->second_position;
          $updateArr['third_position'] = $_baseOverall->third_position;
        } else {
          $updateArr['second_position'] = $_baseOverall->sub_position;
          if ($_baseOverall->second_position === $updateArr['sub_position']) {
            $updateArr['third_position'] = $_baseOverall->third_position;
          } else if ($_baseOverall->third_position === $updateArr['sub_position']) {
            $updateArr['third_position'] = $_baseOverall->second_position;
          }
        }
      }
    } else {
      $updateArr['player_id'] = $_userPlateCard->plateCardWithTrashed->player_id;
    }

    if (!isset($updateArr['sub_position'])) {
      $updateArr['sub_position'] = $_baseOverall->sub_position;
      $updateArr['second_position'] = $_baseOverall->second_position;
      $updateArr['third_position'] = $_baseOverall->third_position;
    }
    return $updateArr;
  }

  // 6.final_overall 계산하기
  public function getFinalOverall(&$updateArr)
  {
    foreach (config('fantasyoverall.final') as $position => $stats) {
      $overall = 0;
      $minus = 0;
      if (isset($updateArr['second_position']) && $position === $updateArr['second_position']) $minus = config('fantasyoverall.sub_position')['second_position'];
      if (isset($updateArr['third_position']) && $position === $updateArr['third_position']) $minus = config('fantasyoverall.sub_position')['third_position'];
      foreach ($stats as $stat => $coefficient) {
        $overall = BigDecimal::of($overall)->plus(BigDecimal::of($updateArr[$stat]['overall'] + $minus)->multipliedBy(BigDecimal::of($coefficient), 1, RoundingMode::HALF_UP));
      }
      $overall = $overall->toScale(0, RoundingMode::HALF_UP);
      $finalOverall[$position] = $overall;
    }
    $updateArr['final_overall'] = $finalOverall;

    return $updateArr;
  }

  // 인게임카드 오버롤 가산점 계산
  public function setIngameCardOverall(int $_userPlateCardId)
  {
    $userCardInfo = UserPlateCard::withoutGlobalScope('excludeBurned')
      ->with([
        'draftSelection.schedule:id,home_team_id,away_team_id,home_formation_used,away_formation_used',
        'refPlayerOverall',
        'plateCardWithTrashed'
      ])
      ->where('id', $_userPlateCardId)
      ->first();

    try {
      // baseOverall
      $baseOverall = $userCardInfo->refPlayerOverall;
      if (is_null($baseOverall)) {
        $baseOverall = $userCardInfo->plateCardWithTrashed->currentRefPlayerOverall;
      }

      if (!is_null($baseOverall)) {
        Schema::connection('simulation')->disableForeignKeyConstraints();
        $config = config('fantasyoverall.additional');

        // 1. 등급 및 mom 가산점
        $allPoint = $this->getGradeMomPoint($config, $userCardInfo->card_grade, $userCardInfo->is_mom);

        $updateArr = [
          'user_id' => $userCardInfo->user_id,
          'user_plate_card_id' => $userCardInfo->id,
          'season_id' => $userCardInfo->draft_season_id,
          //'player_id' => $userCardInfo->draftSelection->player_id,
        ];

        // 3. 카테고리별 레벨
        $updateArr = $this->getCategoryLevelPoint($config, $allPoint, $userCardInfo, $baseOverall, $updateArr);

        // 7.sub_position 찾기
        // formaion_used 찾기
        $updateArr = $this->getFindPosition($userCardInfo, $baseOverall, $updateArr);

        // 6.final_overall 계산하기
        $updateArr = $this->getFinalOverall($updateArr);

        SimulationOverall::create($updateArr);
        // SimulationOverall::updateOrCreateEx(
        //   ['user_plate_card_id' => $_userPlateCardId],
        //   $updateArr
        // );
      }
    } catch (Exception $e) {
      logger($_userPlateCardId);
      logger($e);
      throw ($e);
    } finally {
      Schema::connection('simulation')->enableForeignKeyConstraints();
    }
  }

  public function setBasePlayer($_userId)
  {
    $bestPlayer = [
      'goal' => null,
      'assist' => null,
      'save' => null,
      'rating' => null,
    ];

    $stats = array_keys($bestPlayer);
    foreach ([PlayerPosition::ATTACKER, PlayerPosition::MIDFIELDER, PlayerPosition::GOALKEEPER, PlayerPosition::DEFENDER] as $idx => $position) {
      $overallByPosition = SimulationOverall::with('userPlateCard.plateCardWithTrashed')
        ->whereHas('userPlateCard', function ($query) use ($_userId) {
          $query->where('user_id', $_userId);
        })
        ->whereHas('userPlateCard.plateCardWithTrashed', function ($query) use ($position) {
          $query->where('position', $position)
            ->whereHas('league', function ($leagueQuery) {
              $leagueQuery->where('league_code', config('constant.DEFAULT_LEAGUE'));
            });
        })
        ->selectRaw("*, JSON_UNQUOTE(JSON_EXTRACT(final_overall, CONCAT('$.', sub_position))) as overall")
        ->orderByRaw("JSON_UNQUOTE(JSON_EXTRACT(final_overall, CONCAT('$.', sub_position))) desc")
        ->limit(3)
        ->get();
      foreach ($overallByPosition as $overall) {
        $playerInfo = [
          'goals' => 0,
          'saves' => 0,
          'assists' => 0,
          'position' => $position,
          'draft_level' => $overall->userPlateCard->draft_level,
          'card_grade' => $overall->userPlateCard->card_grade,
          'player_name' => $overall->userPlateCard->player_name,
          'sub_position' => $overall->sub_position,
          'final_overall' => $overall->overall,
          'headshot_path' => $overall->userPlateCard->plateCardWithTrashed->headshot_path,
          'card_grade_order' => config('constant.DRAFT_CARD_GRADE_ORDER')[$overall->userPlateCard->card_grade],
          'user_plate_card_id' => $overall->user_plate_card_id,
          'goal_position_order' => config('constant.SIMULATION_POSITION_ORDER')['goal'][$position],
          'assist_position_order' => config('constant.SIMULATION_POSITION_ORDER')['assist'][$position],
        ];
        $bestPlayer[$stats[$idx]][] = $playerInfo;
      }
    }
    return $bestPlayer;
  }

  public function getUncheckedGames($_userId)
  {
    $applicant = SimulationApplicant::where('user_id', $_userId)->first();
    $weekday = now(config('simulationpolicies.server')[$applicant->server]['timezone'])->englishDayOfWeek;

    $redisKeyName = 'simulation_unchecked_game_' . $applicant->id;
    if (Redis::exists($redisKeyName)) {
      $unCheckedGame = json_decode(Redis::get($redisKeyName), true);
    } else {
      $unCheckedGames = SimulationSchedule::whereHas('lineupMeta', function ($query) use ($applicant) {
        $query->where([
          ['applicant_id', $applicant->id],
          ['is_result_checked', false]
        ]);
      })->whereHas('season', function ($query) {
        $query->currentSeasons();
      })->where('status', ScheduleStatus::PLAYED)
        ->whereRaw("DATE_FORMAT(started_at, '%W') = '{$weekday}'")
        ->oldest('started_at')
        ->get();

      $unCheckedGame =  [
        'count' => $unCheckedGames->count(),
        'id' => $unCheckedGames->first()?->id
      ];

      Redis::set($redisKeyName, json_encode($unCheckedGame), 'EX', 60 * 60);
    }
    return $unCheckedGame;
  }

  public function deleteUncheckedGame($_scheduleId)
  {
    SimulationLineupMeta::where('schedule_id', $_scheduleId)
      ->get()
      ->map(function ($info) {
        $redisKeyName = 'simulation_unchecked_game_' . $info->applicant_id;
        if (Redis::exists($redisKeyName)) {
          // redis 삭제
          Redis::del($redisKeyName);
        }
      });
    return true;
  }

  function applyPenalty($point)
  {
    $penalties = config('fantasyoverall.overall_penalty');
    foreach ($penalties as $standard => $penalty) {
      if ($point >= $standard) {
        return $point + $penalty;
      }
    }
    return $point;
  }
}
