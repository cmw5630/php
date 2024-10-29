<?php

namespace App\Console\Commands\OptaParsers;

// https://api.performfeeds.com/soccerdata/tournamentcalendar/1vmmaetzoxkgg1qf6pkpfmku0k/authorized?_fmt=json&_rt=b
// league, season

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\YesNo;
use App\Enums\ParserMode;
use App\Exceptions\Custom\Parser\OTPInsertException;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\Team;
use DB;
use Throwable;

class OT3VenuesParser extends BaseOptaParser
{
  use FantasyMetaTrait;

  protected const REQUEST_COUNT_AT_ONCE = 20;

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'venues';
    $this->feedNick = 'OT3';
  }

  protected function customParser($_parentKey, $_key, $_value)
  {
    if ($_key === 'venue') {
      foreach ($_value as $idx => $item) {
        $item['team_id'] = $item['contestant'][0]['id'];
        $item['primary'] = $item['contestant'][0]['primary'];
        if ($item['primary'] === YesNo::NO) continue;
        $uniqueKey = $item['id'] . '_' . $item['team_id'];
        unset($item['contestant']);
        $this->appendTargetSpecifiedAttrsByIndex(
          'venue',
          $uniqueKey,
          $item,
        );
      }
    }
    // do nothing;
  }

  protected function customCommonParser($_parentKey, $_key, $_value)
  {
    // do nothing;
  }

  protected function getAllIds()
  {
    return Team::pluck('id')->toArray();
  }

  protected function getDailyIds()
  {
    return Team::pluck('id')->toArray();
  }


  protected function parse(bool $_act): bool
  {
    switch ($this->parserMode) {
      case ParserMode::SYNC:
        if (!$this->setUpSyncFantasyParsing($this->feedNick)) return false;
        switch ($this->syncGroup) {
          case FantasySyncGroupType::ALL:
            $ids = $this->getAllIds();
            break;
          case FantasySyncGroupType::DAILY:
            $ids = $this->getDailyIds();
            break;
          default:
            # code...
            break;
        }
        //
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

    $responses = $this->optaRequest();

    // optaParser 설정 -->>
    $this->setKGsToCustom(['/venue']);
    // optaParser 설정 <<--

    $idChunks = array_chunk($ids, self::REQUEST_COUNT_AT_ONCE);
    $totalChucks = count($idChunks);
    foreach ($idChunks as $idx => $idChunk) {
      if (isset($this->param['chunk']) && $idx < $this->param['chunk']) {
        continue;
      }

      __loggerEx($this->feedType, 'loop $i : ' . $idx . ' / ' . $totalChucks);

      $responses = $this->optaRequest($idChunk);
      $this->insertOptaDatasToTables(
        $responses,
        null,
        [
          [
            'specifiedInfoMap' => ['venue' => Team::class],
            'conditions' => [],
          ]
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

      $modData = array_values($datas['specifiedAttrs']['venue'])[0];

      DB::beginTransaction();
      try {
        $teamInst = Team::where('id', $modData['team_id'])->first();
        $teamInst->capacity = $modData['capacity'];
        $teamInst->primary = $modData['primary'];
        $teamInst->venue_id = $modData['id'];
        $teamInst->venue_name = $modData['name'];
        $teamInst->save();
        DB::commit();
      } catch (Throwable $e) {
        DB::rollBack();
        report(new OTPInsertException(null, ['feed_nick' => $this->feedNick, 'path' => $this->current_url_extra,], $e, $_specifiedInfoToStore));
      }
    }
  }
}
