<?php
// http://api.performfeeds.com/soccerdata/standings/1vmmaetzoxkgg1qf6pkpfmku0k?tmcl=408bfjw6uz5k19zk4am50ykmh&_fmt=json&_rt=b

namespace App\Console\Commands\OptaParsers;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\OptaTeamSeasonStat;
use App\Models\data\Season;
use LogEx;

class TM2TeamStandings extends BaseOptaParser
{
  use FantasyMetaTrait;

  protected const REQUEST_COUNT_AT_ONCE = 20;

  protected $capacityMap = [];

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'standings';
    $this->feedNick = 'TM2';
  }

  // protected function makeCapacityMap($_data)
  // {
  //   foreach ($_data as $dataSet) {
  //     foreach ($dataSet['division'] as $abc) {
  //       if ($abc['type'] === 'attendance') {
  //         foreach ($abc['ranking'] as $item) {
  //           $this->capacityMap[$item['contestantId']] = $item['capacity'];
  //         }
  //       }
  //     }
  //   }
  // }

  protected function customParser($_parentKey, $_key, $_value)
  {
    if ($_key === 'stage') {

      // $this->makeCapacityMap($_value);

      $stageCommonAttrs = [];
      foreach ($_value as  $stageCollections) {
        if ($stageCollections['name'] !== 'Regular Season') {
          continue;
        }
        $stageCommonAttrs[$this->correctKeyName('stage', 'id')] = $stageCollections['id'] ?? null;
        $stageCommonAttrs[$this->correctKeyName('stage', 'formatId')] = $stageCollections['formatId'] ?? null;
        $stageCommonAttrs[$this->correctKeyName('stage', 'name')] = $stageCollections['name'] ?? null;
        $stageCommonAttrs[$this->correctKeyName('stage', 'vertical')] = $stageCollections['vertical'] ?? null;
        $stageCommonAttrs[$this->correctKeyName('stage', 'startDate')] = $stageCollections['startDate'] ?? null;
        $stageCommonAttrs[$this->correctKeyName('stage', 'endDate')] = $stageCollections['endDate'] ?? null;
        foreach ($stageCollections['division'] as $divisionCollections) {
          // logger($divisionCollections['type']);
          foreach ($divisionCollections['ranking'] as $rankingCollections) {
            if ($divisionCollections['type'] !== 'total') {
              continue;
            }

            foreach (['rankStatus', 'rankId'] as $keyName) {
              if (!isset($rankingCollections[$keyName])) {
                $rankingCollections[$keyName] = null;
              }
            }

            $rankingCollections['type'] = $divisionCollections['type'];
            // $ranking_values = [];
            // $ranking_values['type'] = $divisionCollections['type'];
            $typeContestantId = $divisionCollections['type'] . '_' . $rankingCollections['contestantId'];
            // if(!isset($ranking_values[$typeContestantId])) $ranking_values[$typeContestantId] = $stageCommonAttrs;
            // logger($typeContestantId);

            // $rankingCollections['capacity'] = $this->capacityMap[$rankingCollections['contestantId']] ?? null;

            $this->appendTargetSpecifiedAttrsByIndex(
              'stage',
              $typeContestantId,
              array_merge(
                $stageCommonAttrs,
                $rankingCollections
              )
            );
          }
        }
      }
    }
    // dd($this->getTargetSpecifiedAttrs());
  }

  protected function customCommonParser($_parentKey, $_key, $_value)
  {
    // do nothing
  }

  protected function getAllIds()
  {
    return Season::query()->pluck('id')->toArray();
  }

  protected function getDailyIds()
  {
    return Season::query()->currentSeasons()->pluck('id')->toArray();
  }

  protected function getElasticIds()
  {
    return $this->getDailyIds();
  }


  protected function parse(bool $_act): bool
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
          case FantasySyncGroupType::ELASTIC:
            $ids = $this->getElasticIds();
            break;
          default:
            # code...
            break;
        }

      case ParserMode::PARAM:
        if ($this->getParam('mode') === 'all') {
          $ids = $this->getAllIds();
        } else if ($this->getParam('mode') === 'daily') {
          $ids = $this->getDailyIds();
        }
        # code...
        break;
      default:
        # code...
        break;
    }

    // 비동기 동시처리 수로 쪼개기
    $idChunks = array_chunk($ids, self::REQUEST_COUNT_AT_ONCE);

    // optaParser 설정 -->>
    // $this->setKeysToIgnore(['period', 'goal', 'card', 'substitute', 'VAR', 'matchOfficial']);
    $this->setKGsToCustom(['/stage']);
    // $this->setKeysToIgnore(['period', 'goal', 'card', 'substitute', 'VAR', 'matchOfficial']);
    // $this->setCommonKGsToCustom(['scores/ht', 'scores/ft', 'scores/total']);
    // $this->setGlueChildKeys([]);
    // $this->setKeyNameTransMap(['id'=>'playerId']);
    // $this->setKGsToCustom(['/matchDate']);
    unset($this->ubiquitousKeyMap['match']);
    // optaParser 설정 <<--

    foreach ($idChunks as $idx => $idChunk) {
      __loggerEx($this->feedType, 'loop $i : ' . $idx);

      $responses = $this->optaRequest($idChunk);

      $this->insertOptaDatasToTables(
        $responses,
        null,
        [
          [
            'specifiedInfoMap' => [
              'stage' => OptaTeamSeasonStat::class
            ],
            'conditions' => ['season_id', 'team_id']
          ]
        ],
        $_act
      );
    }
    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}
