<?php

namespace App\Console\Commands\Simulation;

use App\Enums\Simulation\SimulationScheduleStatus;
use App\Enums\System\SocketChannelType;
use App\Events\SimulationSocketEvent;
use App\Libraries\Traits\SimulationTrait;
use App\Models\simulation\SimulationSchedule;
use Artisan;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SimulationStatusChecker extends Command
{
  use SimulationTrait;
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'simulation:statuscheck';

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
    /*
    * 정상 상태 처리
    **/
    // Fixture -> Playing
    $data = [
      'type' => SocketChannelType::STATUS,
      'schedule_id' => null,
      'target_queue' => 'sim_seq',
      'server_time' => Carbon::now()->toDateTimeString(),
      'start_time' => null,
    ];
    SimulationSchedule::gameStartedFixture()->lockForUpdate()
      ->get()->map(function ($item) use ($data) {
        $data['schedule_id'] = $item->schedule_id;
        $data['start_time'] = $item->started_at;
        $item->status = SimulationScheduleStatus::PLAYING;
        $item->save();
        broadcast(new SimulationSocketEvent($data));
      });

    // Playing -> Played(어떤 경기는(타이밍에 따라서) 경기 종료 시점에 simulation live 로직 내에서 경기가 종료(Played)상태로 변함)
    $schedules = SimulationSchedule::liveEndedPlaying();
    $schedules->clone()->update(['status' => SimulationScheduleStatus::PLAYED]);

    // unchecked_game redis 삭제
    $schedules->lockForUpdate()->get()
      ->map(function ($info) {
        $this->deleteUncheckedGame($info->id);
      });

    /*
    * 비정상 상태 처리
    **/
    /**
     *  경기 시작 30분 지난 종료(Played)되지 않은 경기 종료 처리()
     * 1. 시나리오 분리 작업이 된 경우
     * 2. 시나리오 분리 작업이 안된 경우
     */

    // 2. gameOverNotReady Scope 사용 (시뮬레이션 제작 작업 포함)
    Artisan::call('simulation:make --mode=gameover');

    // 1. 시뮬레이션 제작은 완료된 경우 라이브 완료 여부 상관없이 (Playing 상태만 Played로 바꿈)
    SimulationSchedule::gameOverReady()
      ->update(['status' => SimulationScheduleStatus::PLAYED]);


    // user_lineup locked 처리
    SimulationSchedule::currentUserLineupNotLocked()
      ->update(['is_user_lineup_locked' => true]);


    // 연구 실패..
    // SimulationSchedule::currentUserLineupLocked()
    //   ->get()
    //   ->map(function ($item) {

    //     return;
    //   });

    return 0;
  }
}
