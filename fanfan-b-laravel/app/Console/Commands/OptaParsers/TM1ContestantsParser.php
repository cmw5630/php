<?php

namespace App\Console\Commands\OptaParsers;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Season\SeasonNameType;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Libraries\Traits\OptaDataTrait;
use App\Models\data\Season;
use App\Models\data\SeasonTeam;
use App\Models\data\Team;
use LogEx;
use Str;

// ex)
// GET http://api.performfeeds.com/soccerdata/team/1vmmaetzoxkgg1qf6pkpfmku0k?_rt=b&_fmt=json&tmcl=7r9f47y0rncn8fjw17ic0jyms
// team
class TM1ContestantsParser extends BaseOptaParser
{
  use FantasyMetaTrait;
  use OptaDataTrait;
  protected const REQUEST_COUNT_AT_ONCE = 20;

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'team';
    $this->feedNick = 'TM1';
  }

  protected function customParser($_parentKey, $_key, $_value) {}

  protected function customCommonParser($_parentKey, $_key, $_value) {}


  protected function insertOptaDatasToTables(
    array $_responses,
    ?array $_commonInfoToStore = null,
    ?array $_specifiedInfoToStore = null,
    $_realStore = false
  ): void {
    foreach ($_responses as $urlKey => $response) { // 비동기 응답s 처리
      $datas = $this->preProcessResponse($urlKey, $response);
      if (!isset($datas['specifiedAttrs']['contestant'])) continue;
      // data 체크->
      if (!$_realStore) {
        logger($datas['commonRowOrigin']);
        logger($datas['specifiedAttrs']);
        $this->generateColumnNames();
        dd('-xTestx-');
      }
      // data 체크<-

      $this->insertDatas(
        null,
        [
          [
            'specifiedInfoMap' => ['contestant' => Team::class],
            'conditions' => ['id']
          ]
        ],
        $datas
      );

      foreach ($datas['specifiedAttrs']['contestant'] as $dIdx => $dInfos) {
        $datas['specifiedAttrs']['contestant'][$dIdx]['team_id'] = $datas['specifiedAttrs']['contestant'][$dIdx]['id'];
        // bet radar에서 사용하는 팀 id 추가
        unset($datas['specifiedAttrs']['contestant'][$dIdx]['id']);
      }

      $this->insertDatas(
        null,
        [
          [
            'specifiedInfoMap' => ['contestant' => SeasonTeam::class],
            'conditions' => ['season_id', 'team_id']
          ]
        ],
        $datas
      );
    }
  }

  protected function getAllIds()
  {
    return Season::idsOf([SeasonWhenType::CURRENT, SeasonWhenType::BEFORE, SeasonWhenType::FUTURE]);
  }

  protected function getDailyIds()
  {
    return  Season::idsOf([SeasonWhenType::CURRENT, SeasonWhenType::FUTURE]);
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
          case FantasySyncGroupType::CONDITIONALLY:
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

    // TournamentCalendars::
    //   where('start_date', '>=',now()->subYears(2))
    //   ->get('id')
    //   ->pluck('id')
    //   ->toArray();

    // optaParser 설정 -->>
    // $this->setKeysToIgnore(['period', 'goal', 'card', 'substitute', 'VAR', 'matchOfficial']);
    // $this->setKGsToCustom(['matchInfo/contestant', 'liveData/lineUp']);
    // $this->setCommonKGsToCustom(['scores/ht', 'scores/ft', 'scores/total']);
    $this->setGlueChildKeys([]);
    // optaParser 설정 <<--

    // $teamSeedParsedStatus = FantasyMeta::where(
    //   'type',
    //   FantasyMetaType::TEAMS_PARSING
    // )->get('flag')->first();
    // if (!$teamSeedParsedStatus['flag']) {
    //   // 전체 시즌
    //   $ids = $this->getAllLeagueIdToSeasonIdMap();
    // } else {
    //   // 현재시즌 + 다음시즌
    //   $seasonIdMap = $this->getLeagueIdToSeasonIdMap();
    //   $ids = [];
    //   foreach ($seasonIdMap as $seasonsCurrentBefore) {
    //     $ids[] = $seasonsCurrentBefore['current'];
    //     $ids[] = $seasonsCurrentBefore['future'];
    //   }
    //   $ids = array_filter($ids);
    // }

    // 비동기 동시처리 수로 쪼개기
    $idChunks = array_chunk($ids, self::REQUEST_COUNT_AT_ONCE);

    foreach ($idChunks as $idx => $idChunk) {
      __loggerEx($this->feedType, 'loop $i : ' . $idx);

      $responses = $this->optaRequest($idChunk);

      foreach ($responses as $resKey => $resValues) {
        foreach ($idChunk as $seasonIdx => $seasonId) {
          if (Str::contains($resKey, $seasonId)) {
            foreach ($resValues['contestant'] as $teamIdx => $teamInfo) {
              $responses[$resKey]['contestant'][$teamIdx]['season_id'] = $seasonId;
            }
          }
        }
      }

      $this->insertOptaDatasToTables(
        $responses,
        null,
        null,
        $_act
      );
    }
    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}
