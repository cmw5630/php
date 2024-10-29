<?php

namespace App\Console\Commands;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\System\NotifyLevel;
use App\Models\meta\FantasyMeta;
use Illuminate\Console\Command;

class PullSyncGroup extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected  $signature = 'sync-group {groupName?} {--init}';

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
    // option 처리
    if ($this->option()['init']) {
      $fantaysMeta = FantasyMeta::get();
      foreach ($fantaysMeta as $parserInst) {
        $parserInst->active = 'no';
        $parserInst->save();
      }
      __telegramNotify(NotifyLevel::INFO, 'sync-group', sprintf('init sync-group jobs!'));
      return;
    }
    //<--option 처리
    $syncGroup = $this->arguments()['groupName'];
    if (!in_array($syncGroup, FantasySyncGroupType::getValues())) {
      logger('FantasySyncGroupType에 없는 argument입니다.');
      dd('FantasySyncGroupType에 없는 argument입니다.');
      return;
    }
    $fantasyMetaTable = FantasyMeta::where('sync_group', $syncGroup)->get()->keyby('sync_order')->toArray();
    $fantasyMetaTable = __sortByKeys($fantasyMetaTable, ['keys' => ['sync_order'], 'hows' => ['asc']]);

    foreach ($fantasyMetaTable as $data) {
      $class = $data['class_name'];
      $parserInst = (new $class);
      if (!($parserInst->setSyncGroup($syncGroup)->start(true))) {
        // __telegramNotify(NotifyLevel::CRITICAL, 'sync-group', sprintf('parsing syncgroup(%s) is aleady activated or stopped', $syncGroup));
        dd(sprintf('parsing sync-group(%s) is aleady activated or stopped', $syncGroup));
        break;
      }
      unset($parserInst);
    }
    return 0;
  }
}
