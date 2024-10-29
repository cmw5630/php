<?php

namespace App\Services\Simulation;

use App\Enums\GradeCardLockStatus;
use App\Enums\Opta\Card\PlateCardStatus;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\YesNo;
use App\Enums\Simulation\SimulationScheduleStatus;
use App\Enums\SimulationCalculator\SimulationCalculatorType;
use App\Enums\SimulationCalculator\SimulationCategoryType;
use App\Models\data\League;
use App\Models\simulation\SimulationApplicant;
use App\Models\simulation\SimulationApplicantStat;
use App\Models\simulation\SimulationLineupMeta;
use App\Models\simulation\SimulationOverall;
use App\Models\simulation\SimulationSchedule;
use App\Models\simulation\SimulationSeason;
use App\Models\simulation\SimulationUserLineup;
use App\Models\simulation\SimulationUserLineupMeta;
use App\Models\simulation\SimulationUserRank;
use App\Models\user\UserPlateCard;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Throwable;
use DB;
use Exception;
use Illuminate\Http\Response;
use Schema;

class SimulationService
{
  protected ?Authenticatable $user;
  protected int $limit;

  public function __construct(?Authenticatable $_user)
  {
    $this->limit = 20;
    $this->user = $_user;
  }

  public function checkApplicant()
  {
    $applicant = SimulationApplicant::with([
      'userLeague' => function ($userLeagueQuery) {
        $userLeagueQuery->with(['season'])->whereHas('season', function ($query) {
          $query->currentSeasons();
        });
      }
    ])
      ->where([
        ['user_id', $this->user->id],
        ['active', YesNo::YES]
      ])->first();

    $result['is_applicant'] = !is_null($applicant);
    $result['league_id'] = $applicant?->userLeague?->league_id;
    $result['season_ready_at'] = null;

    if (!is_null($applicant) && is_null($result['league_id'])) {
      $tz = config('simulationpolicies.server')[$applicant->server]['timezone'];
      $currentSeason = SimulationSeason::currentSeasons()->where('server', $applicant->server)
        ->first();
      // 현재 진행중인 시즌 시작일로부터 다음 시즌 리그 편성 시간을 구함
      $result['season_ready_at'] = $currentSeason->first_started_at->tz($tz)
        ->next(CarbonInterface::SUNDAY)->setHour(10)->tz('UTC')->toDateTimeString();
    }

    //신청서 작성해야함
    if ($result['is_applicant'] === false) {
      $applicants = SimulationApplicant::where('active', YesNo::YES);
      $applicantsCount = $applicants->count('id');
      $serversCount = $applicants->selectRaw('server, COUNT(id) as cnt')
        ->groupBy('server')
        ->get()
        ->pluck('cnt', 'server')
        ->toArray();


      foreach (array_keys(config('simulationpolicies.server')) as $server) {
        $result['usage_rate'][$server] = 0;
        if (isset($serversCount[$server])) {
          $result['usage_rate'][$server] = BigDecimal::of($serversCount[$server])->dividedBy(
            $applicantsCount,
            2,
            RoundingMode::HALF_UP
          )->multipliedBy(100)->toInt();
        }
      }

      return $result;
    } else {
      $result['usage_rate'] = null;

      return $result;
    }
  }

  public function getUserLineup($request)
  {
    try {
      $lineupMeta = $teamCnt = [];
      $overall = [
        'total' => 0,
        'cnt' => 0,
        'avg' => 0,
      ];
      $suspension = [
        'is_exists' => false,
        'reason' => [
          'yellow_card' => false,
          'red_card' => false
        ]
      ];
      foreach (PlayerPosition::getAllPositions() as $position) {
        $overall[$position] = [
          'total' => 0,
          'cnt' => 0,
          'avg' => 0,
        ];
      }

      $positionConfig = config('formation-by-sub-position');

      $nextScheduleId = $this->getMySchedule($request['user_id'], 'next')->value('id');
      // 시뮬레이션 포지션
      $simulCategories = SimulationCategoryType::getValues();
      $positionPenalty = config('fantasyoverall.sub_position');

      SimulationUserLineup::with([
        'userPlateCard.plateCardWithTrashed',
        'userPlateCard.refCardValidation' => function ($query) use ($nextScheduleId) {
          // Todo: Scope화
          $query->whereRaw('JSON_SEARCH(banned_schedules, \'all\', ?) IS NOT NULL', [$nextScheduleId]);
        },
        'simulationOverall',
        'userPlateCard.simulationLineup' => function ($lineupQuery) {
          $lineupQuery->whereHas('lineupMeta.schedule.season', function ($query) {
            $query->currentSeasons();
          })->selectRaw('user_plate_card_id, COUNT(*) as cnt, CAST(SUM(goal) AS float) AS goals, CAST(SUM(assist) AS float) AS assists, CAST(ROUND(AVG(rating),1) AS float) AS rating')
            ->groupBy('user_plate_card_id');
        },
      ])
        ->whereHas('userLineupMeta.applicant', function ($query) {
          $query->where('user_id', $this->user->id);
        })
        ->orderBy('game_started')
        ->get()
        ->map(function ($item) use (&$teamCnt, &$lineupMeta, &$overall, &$suspension, $simulCategories, $positionPenalty, $positionConfig) {

          if (empty($lineupMeta)) {
            $lineupMeta = [
              'user_lineup_meta_id' => $item->userLineupMeta->id,
              'applicant_id' => $item->userLineupMeta->applicant_id,
              'formation_used' => $item->userLineupMeta->formation_used,
              'substitution_count' => $item->userLineupMeta->substitution_count,
              'playing_style' => $item->userLineupMeta->playing_style,
              'defensive_line' => $item->userLineupMeta->defensive_line,
              'is_first' => $item->userLineupMeta->is_first,
            ];
          }

          if ($item->formation_place < 12) {
            $slotPosition = $positionConfig['formation_used'][$item->userLineupMeta->formation_used][$item->formation_place];
          } else {
            $slotPosition = null;
          }

          $lineupInfo = [
            'id' => $item->id,
            'user_plate_card_id' => $item->user_plate_card_id,
            'player_id' => $item->player_id,
            'formation_place' => $item->formation_place,
            'game_started' => $item->game_started,
            'slot_no' => $positionConfig['slot_by_place'][$item->userLineupMeta->formation_used][$item->formation_place],
            'slot_position' => $slotPosition,
            'position' => $item->position,
            'sub_position' => $item->sub_position,
            'second_position' => $item->second_position,
            'third_position' => $item->third_position,
            'card_grade' => $item->userPlateCard->card_grade,
            'draft_level' => $item->userPlateCard->draft_level,
            'headshot_path' => $item->userPlateCard->plateCardWithTrashed->headshot_path,
            'team_club_name' => $item->userPlateCard->plateCardWithTrashed->team_club_name,
            'team_code' => $item->userPlateCard->plateCardWithTrashed->team_code,
          ];

          foreach ([
            ...config('commonFields.player'),
            ...config('commonFields.combined_player')
          ] as $field) {
            $lineupInfo[$field] = $item->userPlateCard->plateCardWithTrashed->{$field};
          }

          // 팀 오버롤, 포지션 별 오버롤 평균
          $lineupInfo['final_overall'] = null;
          if ($item->simulationOverall) {
            $finalOverall = $item->simulationOverall->final_overall[$item->simulationOverall->sub_position] ?? null;
            $lineupInfo['final_overall'] = $finalOverall;
            $overall['cnt']++;
            $overall['total'] += $finalOverall;
            $overall['avg'] = BigDecimal::of($overall['total'])->dividedBy(BigDecimal::of($overall['cnt']), 1, RoundingMode::HALF_UP)->toFloat();

            $overall[$item->position]['cnt']++;
            $overall[$item->position]['total'] += $item->simulationOverall->final_overall[$item->userPlateCard->simulationOverall->sub_position];
            $overall[$item->position]['avg'] = BigDecimal::of($overall[$item->position]['total'])->dividedBy(BigDecimal::of($overall[$item->position]['cnt']), 1, RoundingMode::HALF_UP)->toFloat();

            $lineupMeta['overall']['club'] = $overall['avg'];
            $lineupMeta['overall']['position'][$item->position] = $overall[$item->position]['avg'];
          }

          // 포지션별 오버롤
          $finalOverall = $item->simulationOverall->final_overall;

          $overalls['position'] = $item->simulationOverall->sub_position;
          $overalls['overall'] = (int) $finalOverall[$overalls['position']];

          foreach ($simulCategories as $category) {
            $overalls[$category] = $item->simulationOverall->{$category . '_overall'};
          }
          $overallPosition[] = $overalls;
          $overalls = null;

          $secondPosition = $item->simulationOverall->second_position;
          $thirdPosition = $item->simulationOverall->third_position;

          if (!is_null($secondPosition)) {
            $secondOverall = (int) $finalOverall[$secondPosition];
            $overalls['position'] = $secondPosition;
            $overalls['overall'] = $secondOverall;
            foreach ($simulCategories as $category) {
              $overalls[$category] = $item->simulationOverall->{$category . '_overall'} - $positionPenalty['second_position'];
            }
            $overallPosition[] = $overalls;
            $overalls = null;
          }

          if (!is_null($thirdPosition)) {
            $thirdOverall = (int) $finalOverall[$thirdPosition];
            $overalls['position'] = $thirdPosition;
            $overalls['overall'] = $thirdOverall;
            foreach ($simulCategories as $category) {
              $overalls[$category] = $item->simulationOverall->{$category . '_overall'} - $positionPenalty['third_position'];
            }
            $overallPosition[] = $overalls;
          }

          $lineupInfo['overall_position'] = $overallPosition;

          // 팀별 카드 수 카운트
          if (!isset($teamCnt[$item->userPlateCard->draft_team_id])) {
            $teamCnt[$item->userPlateCard->draft_team_id] = [
              'all' => 0,
              'game_started' => 0,
              'team_names' => $item->userPlateCard->draft_team_names,
            ];
          }
          $teamCnt[$item->userPlateCard->draft_team_id]['all']++;
          if ($item->game_started) {
            $teamCnt[$item->userPlateCard->draft_team_id]['game_started']++;
          }

          // 출전정지
          // Todo: 메소드화
          if (!is_null($item->userPlateCard->refCardValidation)) {
            $suspension['is_exists'] = true;
            if ($item->userPlateCard->refCardValidation->yellow_card_count >= 5) {
              $suspension['reason']['yellow_card'] = true;
            }
            if ($item->userPlateCard->refCardValidation->red_card_count >= 1) {
              $suspension['reason']['red_card'] = true;
            }
          }

          $lineupInfo['suspension'] = $suspension;
          unset($item->refCardValidation);

          // 카드 시즌정보
          $item->season_stat = null;
          if (count($item->userPlateCard->simulationLineup) > 0) {
            $seasonStat['game_played'] = $item->userPlateCard->simulationLineup->first()?->cnt ?? 0;
            $seasonStat['goal'] = $item->userPlateCard->simulationLineup->first()?->goals ?? 0;
            $seasonStat['assist'] = $item->userPlateCard->simulationLineup->first()?->assists ?? 0;
            $seasonStat['rating'] = $item->userPlateCard->simulationLineup->first()?->rating ?? 0;
            $lineupInfo['season_stat'] = $seasonStat;
          }

          $lineupMeta['lineup'][] = $lineupInfo;
        });
    } catch (Throwable $exception) {
      throw $exception;
    }

    // 팀 스택 추출
    $teamStackPolicy = config('simulationwdl.additional_team');
    $teamStackCandidate = null;

    // game_started 정책 중 제일 낮은 명수를 구해서 1을 빼줌. 부등호 비교 연산자 때문
    $min = min(array_keys($teamStackPolicy['game_started'])) - 1;

    foreach ($teamCnt as $item) {
      if ($item['game_started'] > $min) {
        $min = $item['game_started'];
        $teamStackCandidate = $item;
      }
    }

    $teamStack = null;
    if (!is_null($teamStackCandidate)) {
      foreach ($teamStackPolicy as $type => $policy) {
        foreach ($policy as $cutline => $addArr) {
          if ($teamStackCandidate[$type] >= $cutline) {
            $teamStack['add'] = $addArr['add'];
            $teamStack['level'] = $addArr['level'];
          }
        }
      }
    }
    $lineupMeta['team_stack'] = $teamStack;
    return $lineupMeta;
  }

  public function registerDefaultUserLineup($applicant)
  {
    try {
      $defaultFormation = config('constant.DEFAULT_LINEUP_FORMATION');
      $positionByformation = config('formation-by-sub-position.formation_used')[$defaultFormation];
      $substitutionCount = config('simulationpolicies.substitution_count_formations')[$defaultFormation];

      // 각 포지션 별 슬롯 + 교체 슬롯
      $totalCount = count($positionByformation) + array_sum(array_values($substitutionCount));

      $totalPosition = [
        'game_started' => array_count_values($positionByformation),
        'substitution' => $substitutionCount
      ];

      $userLineupMeta = new SimulationUserLineupMeta();
      $userLineupMeta->applicant_id = $applicant->id;
      $userLineupMeta->save();

      Schema::connection('simulation')->disableForeignKeyConstraints();
      $selectedPlateCardIds = [];
      foreach (['game_started', 'substitution'] as $type) {
        if ($type === 'substitution') $formationPlace = 12;
        foreach ($totalPosition[$type] as $position => $limit) {
          $formationPlaces = array_keys($positionByformation, $position);
          $formationPlaceIndex = 0;

          $result = UserPlateCard::doesntHave('simulationUserLineup.plateCardWithTrashed')
            ->withWhereHas('simulationOverall',
              function ($query) use ($type, $position) {
                $query->selectRaw('user_plate_card_id,player_id,sub_position')
                  ->when($type === 'game_started', function ($overallQuery) use ($position) {
                    $overallQuery->where('sub_position', $position)
                      ->orWhere('second_position', $position)
                      ->orWhere('third_position', $position);
                  });
              })
            ->whereHas('draftSeason', function ($query) {
              $query->where('league_id', League::defaultLeague()->id);
            })
            ->where([
              ['user_id', $this->user->id],
              ['status', PlateCardStatus::COMPLETE],
              ['is_open', true],
            ])
            ->where(function ($query) {
              $query->where('lock_status', '!=', GradeCardLockStatus::MARKET)
                ->orWhereNull('lock_status');
            })
            ->when($type === 'substitution', function ($query) use ($position) {
              $query->where('position', $position);
            })
            ->whereNotIn('plate_card_id', $selectedPlateCardIds)
            ->inRandomOrder()
            ->limit($limit)
            ->get();

          if ($result->count() < $limit) {
            return false;
          }
          $selectedPlateCardIds = array_merge($selectedPlateCardIds, $result->pluck('plate_card_id')->toArray());

          $result->map(function ($item) use ($type, $position, $userLineupMeta, $formationPlaces, &$formationPlaceIndex, &$formationPlace, &$totalCount) {
            if (!__startUserPlateCardLock($item->id, GradeCardLockStatus::SIMULATION)) {
              throw new Exception('마켓에 등록된 player.(임시 텍스트)', Response::HTTP_BAD_REQUEST);
            }
            $userLineup = new SimulationUserLineup();
            $userLineup->user_lineup_meta_id = $userLineupMeta->id;
            $userLineup->user_plate_card_id = $item->id;
            $userLineup->player_id = $item->simulationOverall->player_id;
            $userLineup->formation_place = $formationPlaces[$formationPlaceIndex++] ?? $formationPlace++;
            $userLineup->game_started = $type === 'game_started';
            $userLineup->position = $item->position;
            $userLineup->sub_position = $type === 'game_started' ? $position : $item->simulationOverall->sub_position;
            $userLineup->save();

            $totalCount--;
            info('라인업 제출');
          });
        }
      }
      Schema::connection('simulation')->enableForeignKeyConstraints();

      if ($totalCount > 0) {
        return false;
      }

      // 라인업 attack/defence_power 계산
      /** 
       *  @var SimulationCalculator $simulatioCalculator
       */
      $simulationCalculator = app(SimulationCalculatorType::SIMULATION);
      $simulationCalculator->getAttDefPower($applicant->id);

      return true;
    } catch (Throwable $th) {
      return false;
    }
  }


  //-->

  private function getNextBeginnerSchedule(): int
  {
    /**
     * 최하위 티어의 스케쥴
     * 1. 필요하면 없으면 새로운 리그-시즌-라운드 생성
     * 2. 기존 등록한 사용자가 bot과 붙을 경우 bot을 없애고 그 자리로 들어갈지 결정
     * 2-1. bot이 언제 생성되어야 하나?
     */
    // 
    //
    return 3;
  }


  // 유저메타 가져오기
  public function getUserLineupMeta($_applicantId)
  {
    return SimulationUserLineupMeta::with([
      'userLineup.simulationOverall',
    ])
      ->where('applicant_id', $_applicantId)
      ->first();
  }

  // 모든 경기를 체크했는지
  public function resultCheckedAll($_applicantId)
  {
    return SimulationLineupMeta::where([
      'is_result_checked' => false,
      'applicant_id' => $_applicantId,
      // 현재 시즌만?
    ])
      ->whereHas('schedule', function ($query) {
        $query->where('status', SimulationScheduleStatus::PLAYED);
      })
      ->exists();
  }

  // 곧 시작될 게임이 있는지
  public function getSoonFixture($_applicantId)
  {
    // 시작 몇분 전부터 노출 될 것인지
    $countdownTime = now()->addWeek();

    return SimulationSchedule::where([
      'status' => SimulationScheduleStatus::FIXTURE,
    ])
      ->where(function ($where) use ($_applicantId) {
        $where->where('home_applicant_id', $_applicantId)
          ->orWhere('away_applicant_id', $_applicantId);
      })
      ->where('started_at', '<=', $countdownTime)
      ->oldest('started_at')
      ->first();
  }

  // notification message for lobby
  public function showNextTier($applicant)
  {
    $tz = config('simulationpolicies.server')[$applicant->server]['timezone'];
    $now = now($tz);
    // 기준 내멋대로 정함 : 일요일 10시 이후
    if ($now->dayOfWeek === 0 && $now->hour >= 10) {
      return SimulationUserRank::where([
        'applicant_id' => $applicant->id,
        'league_id' => $applicant->userLeague->league_id,
      ])
        ->first();
    }

    return null;
  }

  public function getUserCards($filter)
  {
    $this->limit = $filter['per_page'];
    $filter['league'] = League::defaultLeague()->id;
    $filter['other'] = null;

    $nextScheduleId = $this->getMySchedule($filter['user_id'], 'next')->value('id');

    $suspension = [
      'is_exists' => false,
      'reason' => [
        'yellow_card' => false,
        'red_card' => false
      ]
    ];

    $simulCategories = SimulationCategoryType::getValues();
    $columns = '';
    foreach ($simulCategories as $category) {
      $columns .= $category . '_overall,';
    }

    $sub = SimulationOverall::query()
      ->selectRaw("user_plate_card_id,
      sub_position,second_position,third_position,final_overall," . $columns . "
      CAST(JSON_UNQUOTE(JSON_EXTRACT(final_overall, CONCAT('$.', sub_position))) as unsigned) as overall")
      ->when($filter['position_type'] === 'sub_position', function ($query) use ($filter) {
        $query->where(function ($positionQuery) use ($filter) {
          $positionQuery->where('sub_position', $filter['position_value'])
            ->orWhere('second_position', $filter['position_value'])
            ->orWhere('third_position', $filter['position_value']);
        });
      }, function ($query) use ($filter) {
        $subPositions = config('simulationpolicies.substitution_sub_position')[$filter['position_value']];
        $query->where(function ($positionQuery) use ($subPositions) {
          $positionQuery->whereIn('sub_position', $subPositions)
            ->orWhereIn('second_position', $subPositions)
            ->orWhereIn('third_position', $subPositions);
        });
      });

    return UserPlateCard::with([
      'plateCardWithTrashed:id,player_id,headshot_path,' . implode(',', config('commonFields.player')),
      'plateCardWithTrashed.refPlayerOverall:player_id,sub_position,second_position,third_position,final_overall',
      'draftSelection:user_plate_card_id,schedule_id',
      'refCardValidation' => function ($query) use ($nextScheduleId) {
        $query->whereRaw('JSON_SEARCH(banned_schedules, \'all\', ?) IS NOT NULL', [$nextScheduleId]);
      },
      'simulationLineup' => function ($lineupQuery) {
        $lineupQuery->whereHas('lineupMeta.schedule.season', function ($query) {
          $query->currentSeasons();
        })->selectRaw('user_plate_card_id, COUNT(*) as cnt, CAST(SUM(goal) AS float) AS goals, CAST(SUM(assist) AS float) AS assists, CAST(ROUND(AVG(rating),1) AS float) AS rating')
          ->groupBy('user_plate_card_id');
      }
    ])->where([
      ['user_id', $this->user->id],
      ['is_open', true]
    ])
      ->where(function ($query) {
        $query->where('lock_status', '!=', GradeCardLockStatus::MARKET)
          ->orWhereNull('lock_status');
      })
      ->when($filter['player_name'], function ($nameQuery, $name) {
        $nameQuery->whereHas('plateCard', function ($plateCardQuery) use ($name) {
          $plateCardQuery->nameFilterWhere($name);
        });
      })
      ->rightJoinSub($sub, 'overall', function ($join) {
        $userPlateCardTbl = UserPlateCard::getModel()->getTable();
        $join->on($userPlateCardTbl . '.id', '=', 'overall.user_plate_card_id');
      })
      ->when($filter['grade'], function ($query) use ($filter) {
        $query->whereIn('card_grade', $filter['grade']);
      })
      ->gradeFilters($filter)
      ->orderByDesc('overall')
      ->orderBy('player_name')
      ->paginate($this->limit, ['*'], 'page', $filter['page'])
      ->map(function ($item) use (&$suspension, $simulCategories) {
        $item->plate_card = $item->plateCardWithTrashed;

        // 출전정지
        if (!is_null($item->refCardValidation)) {
          $suspension['is_exists'] = true;
          if ($item->refCardValidation->yellow_card_count >= 5) {
            $suspension['reason']['yellow_card'] = true;
          }
          if ($item->refCardValidation->red_card_count >= 1) {
            $suspension['reason']['red_card'] = true;
          }
        }
        $item->suspension = $suspension;
        unset($item->refCardValidation);

        // 카드 시즌정보
        $item->season_stat = null;
        if (count($item->simulationLineup) > 0) {
          $seasonStat['game_played'] = $item->simulationLineup->first()?->cnt ?? 0;
          $seasonStat['goal'] = $item->simulationLineup->first()?->goals ?? 0;
          $seasonStat['assist'] = $item->simulationLineup->first()?->assists ?? 0;
          $seasonStat['rating'] = $item->simulationLineup->first()?->rating ?? 0;
          $item->season_stat = $seasonStat;
        }
        unset($item->simulationLineup);

        // 포지션별 오버롤
        $overalls['position'] = $item->sub_position;
        $overalls['overall'] = (int) $item->overall;
        foreach ($simulCategories as $category) {
          $overalls[$category] = $item->{$category . '_overall'};
        }
        $overallPosition[] = $overalls;
        $overalls = null;

        $secondPosition = $item->second_position;
        $thirdPosition = $item->third_position;

        $finalOverall = json_decode($item->final_overall, true);
        if (!is_null($secondPosition)) {
          $secondOverall = (int) $finalOverall[$secondPosition];
          $overalls['position'] = $secondPosition;
          $overalls['overall'] = $secondOverall;
          foreach ($simulCategories as $category) {
            $overalls[$category] = $item->{$category . '_overall'} - 10;
          }
          $overallPosition[] = $overalls;
          $overalls = null;
        }

        if (!is_null($thirdPosition)) {
          $thirdOverall = (int) $finalOverall[$thirdPosition];
          $overalls['position'] = $thirdPosition;
          $overalls['overall'] = $thirdOverall;
          foreach ($simulCategories as $category) {
            $overalls[$category] = $item->{$category . '_overall'} - 10;
          }
          $overallPosition[] = $overalls;
        }

        $item->overall_position = $overallPosition;
        unset($item->final_overall);
        unset($item->draftSelection);
        unset($item->plateCardWithTrashed->refPlayerOverall);
        unset($item->plateCardWithTrashed);

        return $item;
      });
  }

  function getRankInfo($_seasonId, $_leagueId, $_isSeasonEnd)
  {
    // Todo: 탈퇴 회원 처리 해야함. 임시로 탈퇴회원정보도 가져오도록 조치
    if ($_isSeasonEnd) {
      return SimulationUserRank::with('applicant.user.userMeta')
        ->whereHas('league', function ($leagueQuery) use ($_leagueId, $_seasonId) {
          $leagueQuery->where('id', $_leagueId)
            ->where('season_id', $_seasonId);
        })
        ->orderBy('ranking')
        ->get()
        ->map(function ($item) {
          return [
            'ranking' => $item->ranking,
            'count_played' => $item->count_played,
            'count_won' => $item->count_won,
            'count_draw' => $item->count_draw,
            'count_lost' => $item->count_lost,
            'points' => $item->points,
            'goal' => $item->goal,
            'goal_against' => $item->goal_against,
            'rating_avg' => $item->rating_avg,
            'goal_difference' => BigDecimal::of($item->goal)->minus($item->goal_against)->toInt(),
            'photo_path' => $item->applicant->user->userMeta->photo_path,
            'user_name' => $item->applicant->user->name,
            'club_code_name' => $item->applicant->club_code_name,
            'is_me' => $item->applicant->user->id === $this->user->id,
            'status' => $item->status,
          ];
        });
    } else {
      return SimulationApplicantStat::with([
        'applicant.user' => function ($query) {
          $query->withoutGlobalScope('excludeWithdraw')
            ->with('userMeta');
        }
      ])
        ->where('league_id', $_leagueId)
        ->selectRaw('*, RANK() OVER (order by points DESC) as ranking')
        ->get()
        ->map(function ($item) {
          return [
            'ranking' => $item->ranking,
            'count_played' => $item->count_played,
            'count_won' => $item->count_won,
            'count_draw' => $item->count_draw,
            'count_lost' => $item->count_lost,
            'points' => $item->points,
            'goal' => $item->goal,
            'goal_against' => $item->goal_against,
            'rating_avg' => $item->rating_avg,
            'goal_difference' => BigDecimal::of($item->goal ?? 0)->minus($item->goal_against ?? 0)->toInt(),
            'photo_path' => $item->applicant->user->userMeta->photo_path,
            'user_name' => $item->applicant->user->name,
            'club_code_name' => $item->applicant->club_code_name,
            'is_me' => $item->applicant->user->id === $this->user->id,
            'status' => null,
          ];
        })
        ->sortBy('user_name')
        ->sortBy('ranking')
        ->values();
    }
  }

  public function getUserHistory($userId, $limit = 1)
  {
    return SimulationUserRank::with([
      'league.season',
      'league.division.tier',
    ])
      ->whereHas('applicant', function ($query) use ($userId) {
        $query->where('user_id', $userId);
      })
      ->latest()
      ->limit($limit)
      ->get()
      ->map(function ($item) {
        $dataSets = [
          'ranking' => $item->ranking,
          'season_week' => $item->league->season->week,
          'league_no' => $item->league->league_no,
          'division_no' => $item->league->division->division_no,
          'tier' => $item->league->division->tier->name,
          'tier_level' => $item->league->division->tier->level,
          'status' => $item->status,
        ];
        if ($item->league->season->active === YesNo::YES) {
          $sub = SimulationApplicantStat::where([
            'league_id' => $item->league_id,
          ])
            ->selectRaw('rank() over (order by points desc) as ranking, applicant_id');
          $ranking = DB::query()->fromSub($sub, 's')->where('applicant_id', $item->applicant_id)
            ->value('ranking');

          $dataSets['ranking'] = $ranking;
        }
        return $dataSets;
      });
  }

  public function getMySchedule($userId, $mode = 'past')
  {
    return SimulationSchedule::where(function ($query) use ($userId) {
        $query->whereHas('home', function ($home) use ($userId) {
          $home->where('user_id', $userId);
        })
          ->orWhereHas('away', function ($home) use ($userId) {
            $home->where('user_id', $userId);
          });
      })
      ->when($mode === 'past', function ($pastQuery) {
        $pastQuery->has('lineupMeta')
          ->latest('started_at');
      }, function ($elseQuery) {
        $elseQuery->where([
          ['status', SimulationScheduleStatus::FIXTURE],
          ['started_at', '>', now()]
        ])
          ->oldest('started_at');
      });
  }
}
