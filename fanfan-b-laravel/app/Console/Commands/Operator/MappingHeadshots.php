<?php

namespace App\Console\Commands\Operator;

use App\Models\game\PlateCard;
use DB;
use Illuminate\Console\Command;
use Storage;
use Str;

class MappingHeadshots extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'OP:headshots';

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
    $path = '/headshots/';
    if ($path[0] === '/') {
      $path = Str::substr($path, 1);
    }

    $storage = Storage::disk('dev');
    $originalPath = $path . 'original/';
    foreach ($storage->allFiles($originalPath) as $file) {
      $errorMsg = [];
      [$leagueCode, $teamCode, $fileName] = explode('/', Str::after($file, $originalPath));
      if (count(explode('--', Str::before($fileName, '.'))) < 2) {
        $errorMsg[] = '이름 이상 '.$teamCode.'/'.Str::before($fileName, '.');
        logger(implode(' ', $errorMsg));
        continue;
      }
      
      [$firstName, $lastName] = explode('--', Str::before($fileName, '.'));
      __loggerEx('headshot', $firstName.$lastName.' 시작');
      $plateCard = PlateCard::where([
        ['league_code', $leagueCode],
        ['team_code', $teamCode],
      ])
        ->where(function ($query) use ($firstName, $lastName){
          $query->where([
            [DB::raw('lower(short_first_name)'), Str::replace('_', ' ', $firstName)],
            [DB::raw('lower(short_last_name)'), Str::replace('_', ' ', $lastName)],
          ])
            ->orWhere([
              [DB::raw('lower(first_name)'), Str::replace('_', ' ', $firstName)],
              [DB::raw('lower(last_name)'), Str::replace('_', ' ', $lastName)],
            ])
            ->orWhere('match_name',
              Str::replace('_', ' ', $firstName) . ' ' . Str::replace('_', ' ', $lastName));
        })
        ->get();

      $errorMsg[] = implode(':', [$leagueCode, $teamCode, $firstName, $lastName]);
      if (count($plateCard) === 0) {
        $errorMsg[] = '카드 없음';
        logger(implode(' ', $errorMsg));
        continue;
      } elseif ($plateCard->count() > 1) {
        $errorMsg[] = '2명 이상';
        logger(implode(' ', $errorMsg));
        continue;
      }

      $updateCard = $plateCard->first();
      __loggerEx('headshot', $updateCard);
      $oldFile = $file;
      $newFile = $path . $leagueCode . '/' . $teamCode . '/' . $updateCard->player_id . '.' . Str::after(
        $file,
        '.'
      );
      __loggerEx('headshot', $errorMsg);
      __loggerEx('headshot', ['old' => $oldFile, 'new' => $newFile ]);
      $storage->delete($newFile);
      $storage->move($oldFile, $newFile);

      $updateCard->headshot_path = Str::after($newFile, $path);
      $updateCard->save();
    }

    return 0;
  }
}
