<?php

namespace App\Console\Commands\Simulation;

use App\Enums\Opta\YesNo;
use App\Enums\Simulation\SimulationScheduleStatus;
use App\Enums\Simulation\SimulationTeamSide;
use App\Libraries\Traits\SimulationTrait;
use App\Models\simulation\SimulationApplicant;
use App\Models\simulation\SimulationApplicantStat;
use App\Models\simulation\SimulationDivision;
use App\Models\simulation\SimulationDivisionStat;
use App\Models\simulation\SimulationLeague;
use App\Models\simulation\SimulationLeagueStat;
use App\Models\simulation\SimulationSchedule;
use App\Models\simulation\SimulationSeason;
use App\Models\simulation\SimulationUserLeague;
use App\Services\Simulation\ScheduleService;
use Carbon\CarbonInterface;
use DB;
use Illuminate\Console\Command;
use Throwable;

class MakeLeagueFixture extends Command
{
  use SimulationTrait;
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'SIM:make-fixture {--server=}';

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
    logger($server . ' 전체 스케쥴 생성 시작');
    // 속도 조금이라도 빠르게 하기 위해 제약조건 잠시 비활성화
    // DB::connection('simulation')->statement('SET FOREIGN_KEY_CHECKS=0;');
    $tz = config('simulationpolicies')['server'][$server]['timezone'];
    $now = now($tz);
    $seasonStartDate = $now->next(CarbonInterface::MONDAY)->toDateString();
    $nextSeason = SimulationSeason::whereDate('first_started_at', $seasonStartDate)
      ->firstWhere('server', $server);

    if (is_null($nextSeason)) {
      return 0;
    }

    $divisions = SimulationDivision::get();
    $simulationService = new ScheduleService;
    foreach ($divisions as $division) {
      $divisionStat = new SimulationDivisionStat;
      $divisionStat->division_id = $division->id;
      $divisionStat->season_id = $nextSeason->id;
      $divisionStat->save();

      SimulationUserLeague::whereHas(
        'applicant',
        function ($query) use ($nextSeason, $server) {
          $query->where([
            ['active', YesNo::YES],
            ['server', $server]
          ]);
        }
      )
        ->where([
          'division_id' => $division->id,
          'season_id' => $nextSeason->id,
        ])
        ->whereNull('league_id')
        ->pluck('applicant_id')
        ->shuffle()
        ->chunk(20)
        ->each(function ($chunk, $idx) use (
          $simulationService,
          $nextSeason,
          $division,
          $tz
        ) {
          DB::beginTransaction();
          try {
            if (count($chunk) < 20) {
              // 봇 추가 로직
              return false;
            }
            // 리그 생성
            $league = new SimulationLeague;
            $league->season_id = $nextSeason->id;
            $league->division_id = $division->id;
            $league->league_no = $idx + 1;
            $league->save();

            $leagueStat = new SimulationLeagueStat;
            $leagueStat->season_id = $nextSeason->id;
            $leagueStat->league_id = $league->id;
            $leagueStat->save();

            $targetApplicantIds = $chunk->values();
            $targetUserIds = SimulationApplicant::whereIn('id', $targetApplicantIds)->pluck('user_id', 'id');

            // 랭킹데이터 생성
            foreach ($targetApplicantIds as $id) {
              $top3 = $this->setBasePlayer($targetUserIds[$id]);
              $applicantStat = new SimulationApplicantStat;
              $applicantStat->season_id = $nextSeason->id;
              $applicantStat->league_id = $league->id;
              $applicantStat->applicant_id = $id;
              $applicantStat->best_goal_players = $top3['goal'];
              $applicantStat->best_assist_players = $top3['assist'];
              $applicantStat->best_save_players = $top3['save'];
              $applicantStat->best_rating_players = $top3['rating'];
              $applicantStat->save();
            }

            SimulationUserLeague::whereIn('applicant_id', $targetApplicantIds)
              ->where('season_id', $nextSeason->id)
              ->update([
                'league_id' => $league->id
              ]);

            $fixtures = $simulationService->generateFixture($targetApplicantIds);
            $startAt = $nextSeason->first_started_at->tz($tz)->clone();

            foreach ($fixtures as $round => $matches) {
              foreach ($matches as $match) {
                $schedule = new SimulationSchedule;
                $schedule->league_id = $league->id;
                $schedule->season_id = $nextSeason->id;
                $schedule->round = $round + 1;
                $schedule->status = SimulationScheduleStatus::FIXTURE;
                $schedule->started_at = $startAt->clone()->setTimezone(config('app.timezone'));
                foreach ($match as $side => $team) {
                  $schedule->{SimulationTeamSide::getValues()[$side] . '_applicant_id'} = $team;
                }
                $schedule->save();
                logger($schedule->started_at);
                logger(($round + 1) . ' 스케쥴 생성 완료');
              }
              // for next round
              $startAt = $startAt->addHour();
              if ($startAt->hour > 22) {
                $startAt = $startAt->addDay()->setHour(10);
              }
              logger('팀구성 완료');
            }

            DB::commit();
          } catch (Throwable $th) {
            logger($th);
            DB::rollback();
          }
        });
    }
    // DB::connection('simulation')->statement('SET FOREIGN_KEY_CHECKS=1;');
    logger($server . ' 전체 스케쥴 생성 완료');
    return 0;
  }
}
