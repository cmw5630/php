<?php

namespace App\Console\Commands\BetRadarParsers;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\BrLeague;
use App\Models\data\BrSeason;
use App\Models\data\Season;
use Str;

class LeagueSeasonParser extends BaseBetRadarParser
{
  use FantasyMetaTrait;
  private const REQUEST_COUNT_AT_ONCE = 1;
  private const REQUESTDELAY = 2;
  private $brLeagues;

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'league_season';
    $this->feedNick = 'BR_LS';
    $this->brLeagues = BrLeague::optaAllLeagues()->get()->toArray();
  }

  private function getBrLeagueIdNumber($_brId): int
  {
    $pattern = '/sr:competition:([0-9]*)/';
    preg_match($pattern, $_brId, $matches);
    return $matches[1];
  }

  private function getBrSeasonIddNumber($_brId): int
  {
    $pattern = '/sr:season:([0-9]*)/';
    preg_match($pattern, $_brId, $matches);
    return $matches[1];
  }

  private function getBrLeagueInfo(): array
  {
    return BrLeague::get()->toArray();
  }

  private function getOptaLeagueId($_brLeagudId)
  {
    foreach ($this->brLeagues as $leagueSet) {
      if ($leagueSet['br_league_id'] == $_brLeagudId) {
        return $leagueSet['opta_league_id'];
      }
    }
    return null;
  }

  private function makeFullYear($_year)
  {
    if (Str::length($_year) == 4) {
      return $_year;
    }
    $year = 20 . $_year;
    return Str::replace('/', '/20', $year);
  }

  private function getOptaSeasonId($_optaLeagueId, $_fullYear)
  {
    return Season::where([
      ['league_id', $_optaLeagueId],
      ['name', $_fullYear],
    ])->value('id');
  }

  protected function customParser($_parent_key, $_key, $_value)
  {
    if ($_key === 'seasons') {
      $idx = 0;
      foreach ($_value as $idx => $valueSet) {
        $fullYear = $this->makeFullYear($valueSet['year']);
        $optaLeagueId = $this->getOptaLeagueId($this->getBrLeagueIdNumber($valueSet['competition_id']));
        $valueSet['br_season_id'] = $this->getBrSeasonIddNumber($valueSet['id']);
        $valueSet['br_league_id'] = $this->getBrLeagueIdNumber($valueSet['competition_id']);
        $valueSet['season_name'] = $valueSet['name'];
        $valueSet['year'] =  $fullYear;
        $valueSet['opta_league_id'] = $optaLeagueId;
        $valueSet['opta_season_id'] = $this->getOptaSeasonId($optaLeagueId, $fullYear);
        unset($valueSet['id']);
        unset($valueSet['competition_id']);
        unset($valueSet['name']);
        $this->appendTargetSpecifiedAttrsByIndex(
          'seasons',
          $idx,
          $valueSet,
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


  private function getDailyIds(): array
  {
    return BrLeague::get()->pluck('br_league_id')->toArray();
  }

  private function getAllIds(): array
  {
    return $this->getDailyIds();
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

    // optaParser 설정 -->>
    $this->setKGsToCustom(['/seasons']);
    // $this->setKGsToCustom(['matchInfo/contestant', '/previousMeetings', '/previousMeetingsAnyComp', '/form', '/formAnyComp']);
    // $this->setKGsToCustom(['/previousMeetingsAnyComp', '/form', '/formAnyComp']);
    // optaParser 설정 <<--

    $idChunks = array_chunk($ids, self::REQUEST_COUNT_AT_ONCE);
    foreach ($idChunks as $idx => $idChunk) {
      sleep(self::REQUESTDELAY);
      logger($idChunk);
      if (isset($this->param['chunk']) && $idx < $this->param['chunk']) {
        continue;
      }

      $responses = $this->betRadarRequest($idChunk);

      $this->insertOptaDatasToTables(
        $responses,
        null,
        [
          [
            'specifiedInfoMap' => ['seasons' => BrSeason::class],
            'conditions' => ['br_league_id', 'br_season_id']
          ],
        ],
        $_act
      );
    }

    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}
