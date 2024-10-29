<?php

namespace App\Console\Commands\DataControll;

use App\Enums\Opta\Season\SeasonNameType;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Libraries\Traits\OptaDataTrait;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\Season;
use App\Models\game\PlayerDailyStat;
use DB;

class DeprecatedFPQuantileUpdator extends PlateCardBase
{
  use FantasyMetaTrait;
  use OptaDataTrait;

  protected $feedNick;

  public function __construct()
  {
    parent::__construct();
    $this->feedNick = 'FPQU';
  }



  public function start(): bool
  {
    switch ($this->parserMode) {
      case ParserMode::SYNC:
        if (!$this->setUpSyncFantasyParsing($this->feedNick)) return false;
        break;
      case ParserMode::PARAM:
        break;
      default:
        break;
    }


    // dd(OptaPlayerDailyStat::where('team_id', '5z1b7fuvpqe6vnigvopomvecu')->where('schedule_id', 'heeomb7d1d2jf9p8zhi701ec')->count());
    // dd(OptaPlayerDailyStat::where('team_id', '5z1b7fuvpqe6vnigvopomvecu')->orderBy('player_id')->pluck('player_id')->toArray());

    // dd(OptaPlayerDailyStat::count());

    $sub = PlayerDailyStat::select(DB::raw('*, ROW_NUMBER() over(order by fantasy_point) as nrank'))
      ->where(function ($query) {
        $query->where('game_started', true)
          ->orWhere('total_sub_on', true);
      });

    $totalCount = $sub->count();

    dd(DB::query()->fromSub($sub, 'sub')
      ->whereIn(
        'sub.nrank',
        [
          1,
          __setDecimal($totalCount * 1 / 4, 0, 'round'),
          __setDecimal($totalCount * 2 / 4, 0, 'round'),
          __setDecimal($totalCount * 3 / 4, 0, 'round'),
        ]
      )->get()->toArray());

    Season::idsOf([SeasonWhenType::BEFORE], SeasonNameType::SINGLE);

    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}
