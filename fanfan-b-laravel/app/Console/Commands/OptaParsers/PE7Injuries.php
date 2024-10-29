<?php

namespace App\Console\Commands\OptaParsers;

use App\Console\Commands\OptaParsers\BaseOptaParser;
use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\Injuries;
use App\Models\data\PlayerCareer;
use App\Models\data\Season;
use App\Models\game\PlateCard;

class PE7Injuries extends BaseOptaParser
{
  use FantasyMetaTrait;
  protected const REQUEST_COUNT_AT_ONCE = 20;

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'injuries';
    $this->feedNick = 'PE7';
  }


  protected function customParser($_parentKey, $_key, $_value)
  {
    //do nothing
  }

  protected function customCommonParser($_parentKey, $_key, $_value)
  {
    // do nothing
  }

  protected function getAllIds()
  {
    return Season::pluck('id')->toArray();
  }

  protected function getDailyIds()
  {
    return Season::currentSeasons()->pluck('id')->toArray();
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
    $ids = $this->getAllIds();

    // optaParser 설정 -->>
    $this->setGlueChildKeys(array_merge($this->getGlueChildKeys(), ['type']));
    $this->setKeyNameTransMap(array_merge($this->getKeyNameTransMap(), ['personId' => 'playerId']));
    // optaParser 설정 <<--

    // 비동기 동시처리 수로 쪼개기
    $idChunks = array_chunk($ids, self::REQUEST_COUNT_AT_ONCE);
    $totalChucks = count($idChunks);
    foreach ($idChunks as $idx => $idChunk) {
      if (isset($this->param['chunk']) && $idx < $this->param['chunk']) {
        continue;
      }

      __loggerEx($this->feedType, 'loop $i : ' . $idx + 1 . ' / ' . $totalChucks);

      $responses = $this->optaRequest($idChunk);

      $this->insertOptaDatasToTables(
        $responses,
        null,
        [
          [
            'specifiedInfoMap' => ['person' => Injuries::class],
            'conditions' => ['league_id', 'season_id', 'player_id', 'injury_type', 'injury_start_date'],
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
