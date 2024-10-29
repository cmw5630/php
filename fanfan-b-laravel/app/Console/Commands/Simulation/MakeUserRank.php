<?php

namespace App\Console\Commands\Simulation;

use App\Enums\Opta\YesNo;
use App\Enums\Simulation\SimulationRankStatus;
use App\Enums\Simulation\SimulationScheduleStatus;
use App\Models\simulation\SimulationApplicant;
use App\Models\simulation\SimulationApplicantStat;
use App\Models\simulation\SimulationDivision;
use App\Models\simulation\SimulationLeague;
use App\Models\simulation\SimulationSchedule;
use App\Models\simulation\SimulationSeason;
use App\Models\simulation\SimulationUserLeague;
use App\Models\simulation\SimulationUserRank;
use Brick\Math\BigDecimal;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Throwable;
use DB;

class MakeUserRank extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'SIM:make-user-rank {--server=} {--season=}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Command description';

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    $options = $this->options();
    $server = $options['server'] ?? 'europe';
    $seasonId = $options['season'];

    logger(sprintf('%s %s 유저 랭크 생성 시작', $server, $seasonId));

    try {
      $now = now(config('simulationpolicies')['server'][$server]['timezone']);
      $firstStartedAt = $now->next(CarbonInterface::MONDAY)->startOfDay()->addHours(10);
      // $firstStartedAt = $now->addDay()->previous(CarbonInterface::MONDAY)->setHours(10);
      // 마지막 경기는 첫경기 + 5일, + 10시간
      $lastStartedAt = $firstStartedAt->clone()->addDays(5)->addHours(10);
      [$nextSeasonId,] = SimulationSeason::updateOrCreateEx([
        'server' => $server,
        'first_started_at' => $firstStartedAt->clone()->tz(config('app.timezone')),
        'last_started_at' => $lastStartedAt->clone()->tz(config('app.timezone')),
        'week' => $firstStartedAt->week,
      ], []);

      $leagues = SimulationLeague::with('division.tier')
        ->whereHas('season',
          function ($query) use ($server, $seasonId) {
            $query->where('server', $server)
              ->when($seasonId, function ($query, $seasonId) {
                $query->where('id', $seasonId);
              }, function ($query) {
                $query->currentSeasons()
                  ->whereDoesntHave('schedules', function ($scheduleQuery) {
                    $scheduleQuery->where('status', '!=', SimulationScheduleStatus::PLAYED);
                  });
              });
          })->get();

      // Todo: 저번주 신규 신청자 유저리그 부여 필요
      // 다음 시즌 생성
      if ($leagues->count() > 0 ) {
        // 티어전체를 가져옴
        $divisions = SimulationDivision::with('tier')
          ->get()
          ->sortByDesc(function ($item) {
            return [$item->tier->level, $item->division_no];
          })
          ->values()
          ->map(function ($item) {
            return [
              'tier_id' => $item->tier_id,
              'tier_level' => $item->tier->level,
              'division_id' => $item->id,
              'division_no' => $item->division_no,
            ];
          });

        foreach ($leagues as $league) {
          $applicantStats = SimulationApplicantStat::where('league_id', $league->id)
            ->selectRaw('RANK() OVER (ORDER BY points DESC, (CAST(goal AS SIGNED) - CAST(goal_against AS SIGNED)) DESC, goal DESC) AS ranking, 
          applicant_id, league_id, count_played, count_won, count_draw, count_lost, points, goal, goal_against, rating_avg')
            ->orderBy('ranking')
            ->get();

          // 동률 그룹 추출
          $tiedGroups = $applicantStats->groupBy('ranking')
            ->filter(function ($group) {
              return $group->count() > 1;
            });

          foreach ($tiedGroups as $tiedGroup) {
            $applicantIds = $tiedGroup->pluck('applicant_id')->toArray();

            $simulationSchedule = SimulationSchedule::with([
              'lineupMeta'
            ])
              ->where([
                ['league_id', $league->id],
                ['status', SimulationScheduleStatus::PLAYED]
              ])
              ->where(function ($query) use ($applicantIds) {
                $query->whereIn('home_applicant_id', $applicantIds)
                  ->orWhereIn('away_applicant_id', $applicantIds);
              })
              ->get();

            $miniLeagueStats = array_fill_keys($applicantIds, [
              'points' => 0,
              'goal' => 0,
              'goal_against' => 0,
              'goals_difference' => 0,
              'away_goal' => 0
            ]);

            // 미니리그 스탯 계산
            foreach ($simulationSchedule as $schedule) {
              $homeId = $schedule->home_applicant_id;
              $awayId = $schedule->away_applicant_id;
              $lineupMeta = $schedule->lineupMeta->keyBy('team_side');
              $homeScore = $lineupMeta['home']->score ?? 0;
              $awayScore = $lineupMeta['away']->score ?? 0;

              $miniLeagueStats[$homeId] = array_merge($miniLeagueStats[$homeId], [
                'goal' => $miniLeagueStats[$homeId]['goal'] + $homeScore,
                'goal_against' => $miniLeagueStats[$homeId]['goal_against'] + $awayScore,
              ]);

              $miniLeagueStats[$awayId] = array_merge($miniLeagueStats[$awayId], [
                'goal' => $miniLeagueStats[$awayId]['goal'] + $awayScore,
                'goal_against' => $miniLeagueStats[$awayId]['goal_against'] + $homeScore,
                'away_goal' => $miniLeagueStats[$awayId]['away_goal'] + $awayScore
              ]);

              if ($schedule->winner !== 'draw') {
                $miniLeagueStats[${$schedule->winner . 'Id'}]['points'] += 3;
              } else {
                $miniLeagueStats[$homeId]['points'] += 1;
                $miniLeagueStats[$awayId]['points'] += 1;
              }
            }

            //goal와 goal_against 최종값을 계산 후 goals_difference 계산
            foreach ($miniLeagueStats as &$stat) {
              $stat['goals_difference'] = BigDecimal::of($stat['goal'])->minus($stat['goal_against'])->toInt();
            }

            $miniLeagueStats = collect($miniLeagueStats)->sortByDesc(function ($item) {
              return [
                $item['points'],
                $item['goals_difference'],
                $item['goal'],
                $item['away_goal']
              ];
            });

            // 동률 그룹 정렬 후 tied_rank 재계산
            $tiedRanking = $tiedGroup->first()->ranking;
            $tiedApplicantIds = $miniLeagueStats->keys()->toArray();
            foreach ($tiedApplicantIds as $index => $applicantId) {
              $tiedApplicant = $tiedGroup->firstWhere('applicant_id', $applicantId);
              if ($tiedApplicant) {
                $tiedApplicant->tied_rank = $tiedRanking + $index;
              }
            }
          }

          // 루키는 모두다 승격처리
          // 어드벤스 division 5면 강등 X
          // 티어 프리미어면 승격 X

          $updownDivision = $this->getUpdownDivision($divisions, $league->division);

          DB::beginTransaction();
          // 리그별로 데이터 정렬 및 최종 결과 생성
          $applicantStats
            ->map(function ($item) use ($nextSeasonId, $updownDivision, $league) {
              $ranking = $item->tied_rank ?? $item->ranking;
              $status = null;
              if ($league->division->tier->level === 6) {
                $status = SimulationRankStatus::PROMOTION;
              } else {
                if ($ranking >= 1 && $ranking <= 4) {
                  if (isset($updownDivision[SimulationRankStatus::PROMOTION])) {
                    $status = SimulationRankStatus::PROMOTION;
                  }
                } else if ($ranking >= 17 && $ranking <= 20) {
                  $status = SimulationRankStatus::MAINTAIN;
                  if (isset($updownDivision[SimulationRankStatus::RELEGATION])) {
                    $status = SimulationRankStatus::RELEGATION;
                  }
                } else {
                  $status = SimulationRankStatus::MAINTAIN;
                }
              }

              SimulationUserRank::updateOrCreateEx([
                'applicant_id' => $item->applicant_id,
                'league_id' => $item->league_id,
              ], [
                'ranking' => $ranking,
                'count_played' => $item->count_played,
                'count_won' => $item->count_won,
                'count_draw' => $item->count_draw,
                'count_lost' => $item->count_lost,
                'points' => $item->points,
                'goal' => $item->goal,
                'goal_against' => $item->goal_against,
                'rating_avg' => $item->rating_avg,
                'status' => $status,
              ]);

              if ($item->applicant->active === YesNo::YES) {
                // 유저리그 디비전 부여
                SimulationUserLeague::updateOrCreateEx([
                  'applicant_id' => $item->applicant_id,
                  'season_id' => $nextSeasonId,
                ], [
                  // 봇 개발 전으로 인한 강제 루키 디비전 부여
                  'division_id' => 'di316e8010a1614a87b0ac611242637ae3',
                  // 'division_id' => $updownDivision[$status]['division_id']
                ]);
              }
            });
        }

        // SimulationUserRank::insert($result);
        DB::commit();
      } else {
        DB::beginTransaction();

        // 초기일 때
        $target = SimulationApplicant::where([
          'active' => YesNo::YES,
          'server' => $server,
        ])
          ->get();

        try {
          foreach ($target as $applicant) {
            SimulationUserLeague::updateOrCreateEx([
              'applicant_id' => $applicant->id,
              'season_id' => $nextSeasonId,
            ], [
              // 봇 개발 전으로 인한 강제 루키 디비전 부여
              'division_id' => 'di316e8010a1614a87b0ac611242637ae3',
            ]);
          }
          DB::commit();
        } catch (Throwable $e) {
          throw $e;
        }

        logger(sprintf('%s %s 유저 랭크 생성 완료', $server, $nextSeasonId));
      }
    } catch (Throwable $th) {
      logger(sprintf('%s %s 유저 랭크 생성 실패', $server, $nextSeasonId));

      logger($th);
      DB::rollBack();
    }
    return 0;
  }

  private function getUpdownDivision($_divisions, $_target)
  {
    $result = [
      SimulationRankStatus::MAINTAIN => [
        'tier_level' => $_target->tier->level,
        'division_no' => $_target->division_no,
      ]
    ];

    if ($_target->division_no === 1) {
      // 승격
      if ($_target->tier->level !== 1) {
        // 1티어일때는 승격이 없음
        $result[SimulationRankStatus::PROMOTION]['tier_level'] = $_target->tier->level - 1;
        $result[SimulationRankStatus::PROMOTION]['division_no'] = 5;
      }
      // 강등
      if ($_target->tier->level !== 6) {
        // 6티어일때는 강등이 없음
        $result[SimulationRankStatus::RELEGATION]['tier_level'] = $_target->tier->level;
        $result[SimulationRankStatus::RELEGATION]['division_no'] = $_target->division_no + 1;
      }
    } else if ($_target->division_no === 5) {
      // 승격
      $result[SimulationRankStatus::PROMOTION]['tier_level'] = $_target->tier->level;
      $result[SimulationRankStatus::PROMOTION]['division_no'] = $_target->division_no - 1;
      // 강등
      if ($_target->tier->level !== 5) {
        // 5티어일때는 강등이 없음
        $result[SimulationRankStatus::RELEGATION]['tier_level'] = $_target->tier->level + 1;
        $result[SimulationRankStatus::RELEGATION]['division_no'] = 1;
      }
    } else {
      $result[SimulationRankStatus::PROMOTION]['tier_level'] = $_target->tier->level;
      $result[SimulationRankStatus::PROMOTION]['division_no'] = $_target->division_no - 1;
      $result[SimulationRankStatus::RELEGATION]['tier_level'] = $_target->tier->level;
      $result[SimulationRankStatus::RELEGATION]['division_no'] = $_target->division_no + 1;
    }

    foreach (SimulationRankStatus::getValues() as $status) {
      if (!isset($result[$status])) {
        continue;
      }
      $division = $_divisions->where('tier_level', $result[$status]['tier_level'])
        ->where('division_no', $result[$status]['division_no'])
        ->first();
      $result[$status]['tier_id'] = $division['tier_id'];
      $result[$status]['division_id'] = $division['division_id'];
    }


    return $result;
  }
}
