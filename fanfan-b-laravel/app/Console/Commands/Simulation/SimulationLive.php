<?php

namespace App\Console\Commands\Simulation;

use App\Enums\Simulation\SimulationEndingType;
use App\Enums\System\SocketChannelType;
use App\Events\SimulationSocketEvent;
use App\Models\simulation\SimulationSchedule;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Throwable;

class SimulationLive extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'simulation:live {--mode=}';

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
    $mode = $this->options()['mode'] ?? 'live';
    if (!($mode === 'live' || $mode === 'test')) {
      $mode = 'default'; // 무작위 입력 대응
    }

    $scheduleIds = [];
    SimulationSchedule::when($mode === 'live', function ($query) {
      $query->playings();
    })->when($mode === 'default', function ($query) {
      $query->limit(0);
    })->when($mode === 'test', function ($query) {
      $query->whereId('sc4417d557054c46d18b774c45bf9d75d9');
    })
      ->whereHas('sequenceMeta', function ($query) {
        $query->where(['is_checked' => false,])->limit(1);
      })->with(['sequenceMeta' => function ($query) {
        $query->where([
          'ending' => SimulationEndingType::FIRST_HALF_START,
          'is_checked' => false,
        ]);
      }])
      ->get()->map(function ($item) use (&$scheduleIds) {
        if (!empty($item->sequenceMeta->toArray())) {
          $item->real_started_at = now();
          $item->save();
        }
        $scheduleIds[] = $item['id'];
      });

    // map 내에서 transaction 걸면 이상 동작. !주의
    foreach ($scheduleIds as $id) {
      DB::beginTransaction();
      try {
        /**
         * @var int $minId
         */
        $minId = 99999999999;
        SimulationSchedule::where('id', $id)
          ->withWhereHas('sequenceMeta', function ($query) {
            $query->with(['refSimulationSequence', 'step.commentaryTemplate:id,comment'])
              ->orderBy('time_sum')
              ->orderBy('id')
              ->where(['is_checked' => false,])
              ->limit(10);
          })
          ->get()
          ->map(function ($item) use (&$minId) {
            $data = [
              'type' => SocketChannelType::SEQUENCE,
              'schedule_id' => $item->id,
              'round' => $item->round,
              'target_queue' => 'sim_seq',
              'server_time' => Carbon::now()->toDateTimeString(),
              'started_at' => Carbon::parse($item->started_at)->toDateTimeString(),
              'real_started_at' => Carbon::parse($item->real_started_at)->toDateTimeString(),
              'first_extra_minutes' => $item->first_extra_minutes,
              'second_extra_minutes' => $item->second_extra_minutes,
              // 'ref_simulation_sequence' => null,
            ];
            $data['real_started_at'] = $gameStartTime = Carbon::parse($item->real_started_at)->toDateTimeString();
            $item->sequenceMeta->map(function ($seq)
            use (
              &$data,
              $gameStartTime,
              &$minId,
            ) {
              if ($seq->id < $minId) {
                $minId = $seq->id;
              }
              $sequenceStartOffset =  Carbon::parse($seq['time_sum'])->diffInSeconds($seq['time_taken']);
              $sequenceStartTime = Carbon::parse($gameStartTime)->addSeconds($sequenceStartOffset);

              // 50초 내에 시작될 시퀀스만
              if ($sequenceStartTime->diffInSeconds(Carbon::parse($data['server_time']), false) >= -50) {
                $seqNo = ($seq->id - ($minId - 1));
                $seqCopy = $seq->toArray();

                // dd($seqCopy['step']);
                foreach ($seqCopy['step'] as $idx => &$step) {
                  try {
                    if (!isset($step['commentary_template']['comment'])) continue;
                    $cTemplate = $step['commentary_template']['comment'];
                    $cParams = $step['ref_params']['comment'] ?? [];
                    preg_match_all('/\{\{([^\}\}]+)\}\}/', $cTemplate, $datasetMatches);
                    $step['comment'] = __pregReplacement('/\{\{([^\}\}]+)\}\}/', $cParams, $cTemplate);
                  } catch (Throwable $e) {
                    // 버그 임시 처리 -->>
                    logger($e);
                    logger($step);
                    $step['commentary_template_id'] = null;
                    $step['commentary_template'] = null;
                    $step['ref_params'] = null;
                    unset($step['comment']);
                    // <<-- 버그 입시처리
                  }
                }
                // $seqCopy['step'][] = ['is_last_step' => 1]; // true

                for ($i = 0; $i <= 16; $i++) {
                  unset($seqCopy['ref_simulation_sequence']['step' . $i]);
                }
                unset($seqCopy['id']);
                unset($seqCopy['ref_simulation_sequence']['event_split']);
                unset($seqCopy['ref_sequence_id']);
                unset($seqCopy['ref_scenario_id']);
                unset($seqCopy['seq']);
                unset($seqCopy['schedule_id']);
                unset($seqCopy['ref_simulation_sequence']['ref_sequence_id']);
                unset($seqCopy['ref_simulation_sequence']['ref_scenario_id']);
                unset($seqCopy['ref_simulation_sequence']['seq']);
                unset($seqCopy['ref_simulation_sequence']['id']);

                $data['data'][$seqNo] = $seqCopy['ref_simulation_sequence'];
                $data['data'][$seqNo]['sequence'] = $seqCopy;
                unset($data['data'][$seqNo]['sequence']['ref_simulation_sequence']);

                $seq->is_checked = true;
                $seq->save();
              }
            });
            if (!empty($data['data'])) {
              broadcast(new SimulationSocketEvent($data));
              logger('schedule_id:' . $data['schedule_id'] . '(broadcasting)');
            }
          });
        DB::commit();
      } catch (Throwable $e) {
        DB::rollBack();
        logger($e);
      }
    }

    return 0;
  }
}
