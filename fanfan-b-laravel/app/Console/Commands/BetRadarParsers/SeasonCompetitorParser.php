<?php

namespace App\Console\Commands\BetRadarParsers;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\BrTeam;
use App\Models\data\BrSeason;
use App\Models\data\SeasonTeam;

class SeasonCompetitorParser extends BaseBetRadarParser
{
  use FantasyMetaTrait;
  private const REQUEST_COUNT_AT_ONCE = 1;

  private int $currentBrSeasonId;

  private array $brSeasonRef;

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'season_competitor';
    $this->feedNick = 'BR_SC';
    $this->brSeasonRef = BrSeason::whereNot('opta_league_id', config('constant.LEAGUE_CODE.UCL'))
      ->get(['br_season_id', 'opta_season_id', 'opta_league_id'])
      ->keyBy('br_season_id')
      ->toArray();
  }

  private function getBrCompetitorIdNumber($_brId)
  {
    $pattern = '/sr:competitor:([0-9]*)/';
    preg_match($pattern, $_brId, $matches);
    return $matches[1];
  }


  private function extraJob()
  {
    $teamMap = config('betradar.brOptaTeamMap');
    BrTeam::whereNull('opta_team_id')->whereIn('br_team_id', array_keys($teamMap))
      ->get()
      ->map(function ($item) use ($teamMap) {
        $item->opta_team_id = $teamMap[$item['br_team_id']];
        $item->save();
      });
  }


  private function getOptaTeamId($_competitorSet)
  {
    // $optaLeagueId = $_competitorSet['opta_league_id'];
    $optaSeasonId = $_competitorSet['opta_season_id'];
    $shortName = $_competitorSet['short_name'];
    $officialName = $_competitorSet['name'];
    $result = (SeasonTeam::currentSeason()->where([
      ['season_id', $optaSeasonId],
      ['short_name', $shortName],
    ])->orWhere([
      ['season_id', $optaSeasonId],
      ['name', $officialName],
    ])->first());
    if ($result !== null) $result = $result->toArray()['team_id'];
    return $result;
  }

  private function isAvailableLeague() {}

  protected function customParser($_parent_key, $_key, $_value)
  {
    if ($_key === 'season_competitors') {
      foreach ($_value as $idx => $competitorSet) {
        if (!isset($this->brSeasonRef[$this->currentBrSeasonId])) {
          continue;
        }
        $competitorSet['opta_league_id'] = $this->brSeasonRef[$this->currentBrSeasonId]['opta_league_id'];
        $competitorSet['opta_season_id'] = $this->brSeasonRef[$this->currentBrSeasonId]['opta_season_id'];
        $competitorSet['name'] = $competitorSet['name'];
        $competitorSet['short_name'] = $competitorSet['short_name'];
        $competitorSet['abbreviation'] = $competitorSet['abbreviation'];
        $competitorSet['br_team_id'] = $this->getBrCompetitorIdNumber($competitorSet['id']);
        $optaTeamId = $this->getOptaTeamId($competitorSet);
        if ($optaTeamId) {
          $competitorSet['opta_team_id'] = $optaTeamId;
        }
        unset($competitorSet['id']);
        $this->appendTargetSpecifiedAttrsByIndex(
          'season_competitors',
          $idx,
          $competitorSet
        );
      }
    }
  }


  protected function customCommonParser($_parentKey, $_key, $_value)
  {
    // do nothing;
  }

  // public function start(bool $_act = false)
  // {
  //   return $this->parse($_act);
  // }

  // protected function parse(bool $_act)
  // {
  //   return $this->getCommentary();
  // }

  private function getLiveIds(): array
  {
    return BrSeason::optaCurrentSeason()->get()->pluck('br_season_id')->toArray();
  }

  private function getDailyIds(): array
  {
    return $this->getLiveIds();
  }

  private function getAllIds(): array
  {
    return $this->getLiveIds();
  }


  protected function parse(bool $_act)
  {
    switch ($this->parserMode) {
      case ParserMode::SYNC:
        if (!$this->setUpSyncFantasyParsing($this->feedNick)) return false;
        switch ($this->syncGroup) {
          case FantasySyncGroupType::ALL:
            $ids = $this->getAllIds();
            # code...
            break;
          case FantasySyncGroupType::DAILY:
            $ids = $this->getDailyIds();
            break;
          default:
            # code...
            break;
        }

      case ParserMode::PARAM:
        if ($this->getParam('mode') === 'all') {
          $ids = $this->getAllIds();
        }
        # code...
        break;
      default:
        # code...
        break;
    }
    // $ids = [93741];

    // optaParser 설정 -->>
    // $this->setKGsToCustom(['matchInfo/contestant', '/previousMeetings', '/previousMeetingsAnyComp', '/form', '/formAnyComp']);
    $this->setKGsToCustom(['/season_competitors']);
    // optaParser 설정 <<--

    // 비동기 동시처리 수로 쪼개기
    $idChunks = array_chunk($ids, self::REQUEST_COUNT_AT_ONCE);
    $totalChucks = count($idChunks);
    foreach ($idChunks as $idx => $idChunk) {
      if (isset($this->param['chunk']) && $idx < $this->param['chunk']) {
        continue;
      }
      $this->currentBrSeasonId = $idChunk[0];

      __loggerEx($this->feedType, 'loop $i : ' . $idx + 1 . ' / ' . $totalChucks);

      sleep(2);
      $responses = $this->betRadarRequest($idChunk);

      $this->insertOptaDatasToTables(
        $responses,
        null,
        [
          [
            'specifiedInfoMap' => ['season_competitors' => BrTeam::class],
            'conditions' => ['br_team_id'],
          ],
        ],
        $_act
      );
    }
    $this->extraJob();

    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}
