<?php

namespace App\Console\Commands\DataControll;

use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\FantasyCalculator\FantasyPointCategoryType;
use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\ParserMode;
use App\Libraries\Classes\FantasyCalculator;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\game\PlayerDailyStat;
use App\Models\meta\RefAvgFp;
use DB;
use Schema;

//시즌별/포지션별/카테고리별, 라운드 진행 누적(현재시즌만)
class FpCategoryAverageRefUpdator
{
  use FantasyMetaTrait;
  protected $feedNick;
  public function __construct()
  {
    $this->feedNick = 'FPARU';
  }

  private function getDailyIds()
  {
    /**
     * @var FantasyCalculator $fpCalculator
     */
    $fpCalculator = app(FantasyCalculatorType::FANTASY_POINT, [0]);

    // $currentId = Season::idsOf([SeasonWhenType::CURRENT], SeasonNameType::ALL);
    // PlayerDailyStat::selectRaw('')
    // whereIn('season_id', $currentId)->gameParticipantPlayer()->groupBy('summary_position')
  }

  private function update($_isDaily)
  {
    /**
     * @var FantasyCalculator $fpCalculator
     */
    $fpCalculator = app(FantasyCalculatorType::FANTASY_POINT, [0]);
    $queryList = [];
    $queryString = '';
    foreach ($fpCalculator->getCombsWithCategoryTable() as $categoryName => $cSet) {
      if ($categoryName === FantasyPointCategoryType::GENERAL) continue;
      $queryList[$categoryName] = '';
      foreach ($cSet as $idx => $col) {
        if (gettype($idx) === 'string') {
          $queryList[$categoryName] .= $idx . '+';
        } else {
          $queryList[$categoryName] .= $col . '+';
        }
      }
      $queryList[$categoryName] = preg_replace('/\+$/', '', $queryList[$categoryName]);
      $queryString .= sprintf('ROUND(CAST(AVG(%s) as float), %s) as %s,', $queryList[$categoryName], $fpCalculator->getFpPrecision(), $categoryName . '_point_avg');
    }
    $queryString = preg_replace('/\,$/', '', $queryString);
    PlayerDailyStat::query()
      ->selectRaw('ROUND(AVG(rating), 1) as rating_avg,' . sprintf('season_id, summary_position, ROUND(AVG(fantasy_point), 2) as fantasy_point_avg, %s', $queryString))
      ->where(function ($query) {
        $query->gameParticipantPlayer();
      })
      ->when($_isDaily, function ($query) {
        $query->whereHas('season', function ($query) {
          $query->currentSeasons()->withoutGlobalScopes();
        });
      }, function ($query) {
        $query->withoutGlobalScopes();
      })
      ->groupBy('season_id', 'summary_position')
      ->get()->groupBy(['season_id'])->map(function ($seasonGroup) use ($fpCalculator) {
        logger($seasonGroup[0]['season_id']);
        foreach ($seasonGroup as $idx => $cSet) {
          RefAvgFp::updateOrCreateEx(
            [
              'season_id' => $cSet['season_id'],
              'summary_position' => $cSet['summary_position'],
            ],
            $cSet->toArray(),
            true
          );
        }
      });
  }


  public function start(): bool
  {
    switch ($this->parserMode) {
      case ParserMode::SYNC:
        if (!$this->setUpSyncFantasyParsing($this->feedNick)) return false;
        switch ($this->syncGroup) {
          case FantasySyncGroupType::ALL:
            $isDaily = False;
            break;
          case FantasySyncGroupType::DAILY:
            $isDaily = True;
            break;
          default:
            # code...
            break;
        }

      case ParserMode::PARAM:
        if ($this->getParam('mode') === 'all') {
          $isDaily = False;
        } else if ($this->getParam('mode') === 'daily') {
          $isDaily = True;
        }
        break;
      default:
        # code...
        break;
    }

    $this->update($isDaily);

    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}
