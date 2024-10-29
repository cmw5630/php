<?php

namespace App\Console\Commands\DataControll;

use App\Console\Commands\DataControll\PlateCardBase;
use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Season\SeasonNameType;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\Season;
use App\Models\game\PlayerDailyStat;
use App\Models\meta\RefPointcQuantile;
use DB;
use Str;

class PointCQuantileUpdator extends PlateCardBase
{
  use FantasyMetaTrait;

  protected $feedNick;

  public function __construct()
  {
    parent::__construct();
    $this->feedNick = 'PCQU';
  }

  public function isQuantileSoruceSeasonReady(string $_nameType): string
  {
    /**
     * 전제조건: season테이블에 이전시즌 정보와 현재시즌 정보는 1:1 매칭된다. (프리미어 리그 2022/2023 시즌 데이터가 존재하면 2021/2022도 반드시 존재한다.)
     * 로직 핵심: season 테이블을 토대로 현재시즌(가장 최신) 정보가 모두 active='yes'가 되는 시점에서 quantile soruce를 수집하기 시작한다.
     */
    if ($_nameType === SeasonNameType::DOUBLE) {
      $operand = 'LIKE';
    } else if ($_nameType === SeasonNameType::SINGLE) {
      $operand = 'NOT LIKE';
    }
    $currentSeasonName = Season::where('name', $operand, '%/%')
      ->orderByDesc('name')
      ->first('name')
      ->toArray()['name'];

    return $currentSeasonName;

    // if ($_nameType === SeasonNameType::DOUBLE) {
    //   $beforeSeasonName = (Str::before($currentSeasonName, '/') - 1) . '/' . (Str::after($currentSeasonName, '/') - 1);
    // } else if ($_nameType === SeasonNameType::SINGLE) {
    //   $beforeSeasonName = (string)($currentSeasonName - 1);
    // }

    // if (
    //   Season::where('name', (string)$currentSeasonName)
    //   ->currentSeasons()->count() === Season::where('name', (string)$beforeSeasonName)->count()
    // ) {
    //   return $currentSeasonName;
    // }
    // return null;
  }

  public function start(): bool
  {
    logger('PointCQuantileUpdator Start');
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


    foreach ([SeasonNameType::SINGLE, SeasonNameType::DOUBLE] as $nameType) {
      if ($currentSeasonName = $this->isQuantileSoruceSeasonReady($nameType)) {
        logger($nameType);
        // source season ready
        $ids = Season::idsOf([SeasonWhenType::BEFORE], $nameType, $baseOffsetYear = 3, $_leagueRemove = [config('constant.LEAGUE_CODE.UCL')]);
        PlayerDailyStat::wherehas('season', function ($query) use ($ids) {
          $query->whereIn('id', $ids);
        })->select(DB::raw('fantasy_point, summary_position, ROW_NUMBER() over(partition by summary_position order by fantasy_point desc) as nrank'))
          ->gameParticipantPlayer()->get()->groupBy('summary_position')
          ->map(function ($groupByPosition) use ($currentSeasonName, $nameType, $baseOffsetYear) {
            $position = $groupByPosition[0]['summary_position'];
            $count = $groupByPosition->count();

            $q3 = ($groupByPosition[__setDecimal($count * 1 / 4, 1, 'round')]['fantasy_point'] + 1);
            $q1 = ($groupByPosition[__setDecimal($count * 3 / 4, 1, 'round')]['fantasy_point'] + 1);

            $iqr = $q3 - $q1;

            $max_q = __setDecimal($q3 + (1.5 * ($iqr)), 1, 'round');
            $min_q = __setDecimal($q1 - (1.5 * ($iqr)), 1, 'round');

            $outLinerRemoved = array_values($groupByPosition->filter(function ($value, $key) use ($max_q, $min_q) {
              return ($min_q <= $value['fantasy_point']) && ($value['fantasy_point'] <= $max_q);
            })->toArray());

            $total = [
              'playing_season_name' => $currentSeasonName,
              'season_name_type' => $nameType,
              'position' => $position,
              'base_offset' => $baseOffsetYear,
            ];
            foreach (['_top' => 1, '_middle' => 2, '_bottom' => 3] as $suffix => $qnum) {
              $total['quantile' . $suffix] = $outLinerRemoved[__setDecimal(count($outLinerRemoved) * $qnum / 4, 0, 'round') + 1]['fantasy_point'];
            }

            RefPointcQuantile::updateOrCreateEx(
              [
                'playing_season_name' => $total['playing_season_name'],
                'position' => $total['position']
              ],
              $total,
              false,
              true,
            );
          });
      }
    }

    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}
