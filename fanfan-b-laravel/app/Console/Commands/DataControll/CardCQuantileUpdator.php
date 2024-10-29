<?php

namespace App\Console\Commands\DataControll;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Card\OriginGrade;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Season\SeasonNameType;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\Season;
use App\Models\game\PlayerDailyStat;
use App\Models\meta\RefCardcQuantile;
use App\Models\meta\RefPlateGradePrice;
use DB;

class CardCQuantileUpdator
{
  use FantasyMetaTrait;

  protected $feedNick;

  protected $leagueActiveSeasonMap;

  protected $gradePercentileMap;

  public function __construct()
  {
    $this->feedNick = 'CCQU';
    $this->leagueActiveSeasonMap = Season::getBeforeCurrentMapCollection()->keyBy('league_id')->toArray();
    // percentile은 plate_c나 card_c나 항상 동일하게 사용한다.
    $this->gradePercentileMap = RefPlateGradePrice::get(['grade', 'percentile_point'])->keyBy('grade')->toArray();
  }


  protected function getPlayingSeasonId($_leagueId)
  {
    return $this->leagueActiveSeasonMap[$_leagueId]['current_id'];
  }

  public function start(): bool
  {
    switch ($this->parserMode) {
      case ParserMode::SYNC:
        if (!$this->setUpSyncFantasyParsing($this->feedNick)) return false;
        switch ($this->syncGroup) {
          case FantasySyncGroupType::ALL:
            # code...
            break;
          case FantasySyncGroupType::DAILY:
            break;
          default:
            # code...
            break;
        }

        // case ParserMode::PARAM:
        //   if ($this->getParam('mode') === 'all') {
        //     $ids = $this->getAllIds();
        //   }
        //   # code...
        //   break;
        // default:
        //   # code...
        //   break;
    }

    $beforeIds = Season::idsOf([SeasonWhenType::BEFORE], SeasonNameType::ALL, $_baseOffsetYear = 3);
    PlayerDailyStat::where('status', ScheduleStatus::PLAYED)
      ->gameParticipantPlayer()
      ->whereIn('season_id', $beforeIds)
      ->select(DB::raw('league_id, summary_position, card_c, ROW_NUMBER() over(partition by league_id, summary_position order by card_c desc) as nrank'))
      ->get()->groupBy(['league_id', 'summary_position'])
      ->flatMap(function ($groupsByPosition, $leagueId) {
        $groupsByPosition->flatMap(function ($positionGroup, $position) use ($leagueId) {
          $count = $positionGroup->count();
          $quantileRow = [
            'league_id' => $leagueId,
            'playing_season_id' => $this->getPlayingSeasonId($leagueId),
            'summary_position' => $position
          ];
          foreach ($this->gradePercentileMap as $grade => $item) {
            if ($grade === OriginGrade::SS) {
              // $ss = 999;
              continue;
            }
            $quantileRow['quantile' . '_' . $grade] = $positionGroup[__setDecimal($count * $item['percentile_point'] / 100, 0) + 1]['card_c'];
          }
          RefCardcQuantile::updateOrCreateEx([
            'playing_season_id' => $quantileRow['playing_season_id'],
            'summary_position' => $quantileRow['summary_position'],
          ], $quantileRow);
        });
      });

    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}
