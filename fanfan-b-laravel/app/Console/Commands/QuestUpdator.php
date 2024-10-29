<?php

namespace App\Console\Commands;

use App\Enums\QuestActiveType;
use App\Enums\QuestCycleType;
use App\Enums\System\NotifyLevel;
use App\Libraries\Traits\GameTrait;
use App\Models\game\Quest;
use App\Models\game\QuestType;
use DB;
use Illuminate\Console\Command;
use Throwable;

class QuestUpdator extends Command
{
  use GameTrait;

  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected  $signature = 'quest-update';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Command quest-update';

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
    __telegramNotify(NotifyLevel::INFO, 'questUpdate', 'questupdate start');

    // 주에 할당된 퀘스트가 있는지 체크 후 생성, 호출 시점이 언제됐던 다음주차까지 만듦.
    $dateStringSet = $this->getDateStringSet();

    $quests = Quest::all();

    try {
      // 이주간의 퀘스트 만들기
      foreach ($dateStringSet as $week) {
        foreach ($quests as $quest) {
          QuestType::updateOrCreate([
            'quest_id' => $quest->id,
            'order_no' => $quest->order_no,
            'start_date' => $week['start'],
            'end_date' => $week['end'],
          ], []);
        }
      }
    } catch (Throwable $e) {
      __telegramNotify(NotifyLevel::INFO, 'questUpdate', 'questupdate error');
      return 0;
    }
    __telegramNotify(NotifyLevel::INFO, 'questUpdate', 'questupdate end - success');
    return 0;
  }
}
