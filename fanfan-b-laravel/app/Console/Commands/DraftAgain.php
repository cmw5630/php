<?php

namespace App\Console\Commands;

use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Libraries\Traits\DraftTrait;
use App\Models\game\DraftComplete;
use App\Models\game\DraftSelection;
use App\Models\game\GameSchedule;
use Illuminate\Console\Command;

class DraftAgain extends Command
{
  use DraftTrait;
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected  $signature = 'draftagain {--gameid=} {--scheduleid=}'; // !TODO:xzy(멀티게임 로직 변경)

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Command draftagain';

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
    if (isset($options['gameid']) && isset($options['scheduleid'])) {
      dd('--gameid= 또는 --scheduleid= 중 하나의 옵션만 가능해!.');
    } else if (!isset($options['gameid']) && !isset($options['scheduleid'])) {
      dd('--gameid= 또는 --scheduleid= 중 하나의 옵션을 적용해!.');
    }

    GameSchedule::when(isset($options['gameid']), function ($query) use ($options) {
      $query->where('game_id', (int)$options['gameid']);
    })->whereHas('gamePossibleSchedule', function ($query) {
      $query->where(function ($query2) {
        $query2->where('status', ScheduleStatus::PLAYED)
          ->orWhere('status', ScheduleStatus::AWARDED);
      });
    })->get()
      ->map(function ($item) use ($options) {
        $scheduleId = $item['schedule_id'];
        if ($options['scheduleid']) {
          if ($options['scheduleid'] != $scheduleId) return;
        }
        DraftSelection::where('schedule_id', $scheduleId)->get()
          ->map(function ($selectionItem) {
            $userPlateCardId = $selectionItem['user_plate_card_id'];
            if (DraftComplete::where('user_plate_card_id', $userPlateCardId)->exists()) { // 재강화이므로 반드시 검사
              $this->forceModifyDraftCompletedCard($userPlateCardId);
            }
          });
      });
    dd('재강화 완료!');
    return 0;
  }
}
