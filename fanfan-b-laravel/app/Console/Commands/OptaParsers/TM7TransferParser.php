<?php

namespace App\Console\Commands\OptaParsers;

use App\Console\Commands\OptaParsers\BaseOptaParser;
use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\ParserMode;
use App\Exceptions\Custom\Parser\OTPInsertException;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\SeasonTeam;
use App\Models\data\Transfer;
use App\Models\game\Player;
use DB;
use Throwable;

class TM7TransferParser extends BaseOptaParser
{
  use FantasyMetaTrait;

  protected const REQUEST_COUNT_AT_ONCE = 1;

  protected $availablePlayers = [];

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'transfers';
    $this->feedNick = 'TM7';

    $this->availablePlayers = Player::pluck('id')->toArray();
  }

  protected function customParser($_parentKey, $_key, $_value)
  {
    if ($_key === 'person') {
      $this->extractRepeatedSpecifiedWithId($_value, 'person', 'id', 'membership', 'contestantId', 'transfer');
    }
  }

  protected function customCommonParser($_parentKey, $_key, $_value)
  {
    // do nothing
  }

  protected function getAllIds(): array
  {
    return SeasonTeam::currentSeason()->distinct()->pluck('team_id')->toArray();
  }

  protected function getDailyIds(): array
  {
    return $this->getAllIds();
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
        } else if ($this->getParam('mode') === 'daily') {
          $ids = $this->getDailyIds();
        }
        # code...
        break;
      default:
        # code...
        break;
    }


    // optaParser 설정 -->>
    $this->setGlueChildKeys(array_merge($this->getGlueChildKeys(), ['type']));
    $this->setKeyNameTransMap(
      array_merge(
        $this->getKeyNameTransMap(),
        ['personId' => 'playerId', 'personType' => 'playerType', 'personPosition' => 'playerPosition']
      )
    );
    $this->setKGsToCustom(['/person']);
    // optaParser 설정 <<--


    $idChunks = array_chunk($ids, self::REQUEST_COUNT_AT_ONCE);
    $totalChucks = count($idChunks);
    foreach ($idChunks as $idx => $idChunk) {
      __loggerEx($this->feedType, 'loop $i : ' . $idx + 1 . ' / ' . $totalChucks);


      $responses = $this->optaRequest($idChunk);

      $this->insertOptaDatasToTables(
        $responses,
        null,
        [
          [
            'specifiedInfoMap' => ['person' => Transfer::class],
            'conditions' => ['team_id', 'player_id', 'membership_start_date'],
            // 'conditions' => [],  // 중복데이터 있음
          ],
        ],
        $_act
      );
    }
    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }

  protected function insertOptaDatasToTables(
    array $_responses,
    array $_commonInfoToStore = null,
    array $_specifiedInfoToStore = null,
    $_realStore = false,
  ): void {
    foreach ($_responses as $urlKey => $response) { // 비동기 응답s 처리
      $datas = $this->preProcessResponse($urlKey, $response);

      // data 체크->
      if (!$_realStore) {
        logger($datas['commonRowOrigin']);
        logger($datas['specifiedAttrs']);
        $this->generateColumnNames();
        dd('-xTestx-');
      }
      // data 체크<-
      foreach ($datas['specifiedAttrs']['person'] as $idx => $item) {
        if (!in_array($item['player_id'], $this->availablePlayers)) {
          unset($datas['specifiedAttrs']['person'][$idx]);
        }
      };

      DB::beginTransaction();
      try {
        $this->insertDatas($_commonInfoToStore, $_specifiedInfoToStore, $datas);
        DB::commit();
      } catch (Throwable $e) {
        DB::rollBack();
        report(new OTPInsertException(null, ['feed_nick' => $this->feedNick, 'path' => $this->current_url_extra,], $e, $_specifiedInfoToStore));
      }
    }
  }
}
