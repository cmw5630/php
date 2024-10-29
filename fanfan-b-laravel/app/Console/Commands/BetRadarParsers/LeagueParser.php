<?php

namespace App\Console\Commands\BetRadarParsers;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\BrLeague;
use App\Models\data\League;

class LeagueParser extends BaseBetRadarParser
{
  use FantasyMetaTrait;
  private const REQUEST_COUNT_AT_ONCE = 20;
  private $BrOptaLeagueMap;

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'league';
    $this->feedNick = 'BR_LG';
    $this->BrOptaLeagueMap = config('betradar.BrOptaLeagueMap');
  }

  private function getOptaLeagues()
  {
    $BrOptaLeagueMap = [];
    League::withoutGlobalScopes()->get()->keyBy('id')->map(function ($item) use (&$BrOptaLeagueMap) {
      foreach ($this->BrOptaLeagueMap as $bid => $oid) {
        if ($item['id'] === $oid) {
          $BrOptaLeagueMap[$bid] = $oid;
        }
      }
    });
    return $BrOptaLeagueMap;
  }

  private function getBrLeagueIdNumber($_brId)
  {
    $pattern = '/sr:competition:([0-9]*)/';
    preg_match($pattern, $_brId, $matches);
    return $matches[1];
  }


  protected function customParser($_parent_key, $_key, $_value)
  {
    if ($_key === 'competitions') {
      $idx = 0;
      $optaLeagues = $this->getOptaLeagues();
      $brIds = array_keys($optaLeagues);
      $optaIds = array_values($optaLeagues);
      foreach ($_value as $idx => $valueSet) {
        if (!in_array($valueSet['id'], $brIds)) continue;

        $this->getBrLeagueIdNumber($valueSet['id']);

        $optaLeagueId = $optaLeagues[$valueSet['id']];
        $valueSet['br_league_id'] = $this->getBrLeagueIdNumber($valueSet['id']);
        $valueSet['leagues_name'] = $valueSet['name'];
        $valueSet['opta_league_id'] = $optaLeagueId;
        $valueSet['country_name'] = $valueSet['category']['name'];
        $valueSet['country_code'] = $valueSet['category']['country_code'] ?? null;
        unset($valueSet['category']);
        unset($valueSet['id']);
        $this->appendTargetSpecifiedAttrsByIndex(
          'competitions',
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

  protected function parse(bool $_act)
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

      case ParserMode::PARAM:
        if ($this->getParam('mode') === 'all') {
        }
        # code...
        break;
      default:
        # code...
        break;
    }

    // optaParser 설정 -->>
    $this->setKGsToCustom(['/competitions']);
    // optaParser 설정 <<--

    $responses = $this->betRadarRequest();

    $this->insertOptaDatasToTables(
      $responses,
      null,
      [
        [
          'specifiedInfoMap' => ['competitions' => BrLeague::class],
          'conditions' => ['br_league_id'],
        ],
      ],
      $_act
    );

    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}
