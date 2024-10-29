<?php

namespace App\Console\Commands\OptaParsers;

use App\Console\Commands\OptaParsers\BaseOptaParser;
use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\Schedule;
use App\Models\data\Suspension;
use Carbon\Carbon;

class PE8SuspensionParser extends BaseOptaParser
{
  use FantasyMetaTrait;
  protected const REQUEST_COUNT_AT_ONCE = 20;

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'suspensions';
    $this->feedNick = 'PE8';
  }

  protected function extractRepeatedSpecifiedWithId(
    $_value,
    $_outerKey,
    $_outerCollectionId,
    $_innerKey,
    $_innerCollectionId = null,
    $_lastStatKey = 'xxyyzzabcdpp',
  )
  // 
  {
    foreach ($_value as $outerIdx => $outerCollection) {
      $outerTempAttrs = [];
      $innerCollection = $outerCollection[$_innerKey];
      unset($outerCollection[$_innerKey]);
      $player_unique_key = $outerCollection[$_outerCollectionId];
      foreach ($outerCollection as $outerCollectionKey => $outerCollectionValue) {
        $outerTempAttrs[$this->correctKeyName($_outerKey, $outerCollectionKey)] = $outerCollectionValue;
      }
      // info($person_temp_attrs);
      foreach ($innerCollection as $inner_collection_idx => $innerCollectionValue) {

        $innerTempAttrs = [];
        foreach ($innerCollectionValue as $k => $v) {
          $innerTempAttrs[$this->correctKeyName($_innerKey, $k)] = $v;
        }

        $innerTempAttrs = array_merge($innerTempAttrs, $outerTempAttrs);

        // info($outer_idx);
        $this->appendTargetSpecifiedAttrsByIndex(
          $_outerKey,
          $outerIdx,
          $innerTempAttrs
        );
        // info($membership_temp_attrs);
      }
    }
  }

  protected function customParser($_parentKey, $_key, $_value)
  {
    if ($_key === 'person') {
      $this->extractRepeatedSpecifiedWithId($_value, 'person', 'id', 'suspension');
    }
  }
  protected function customCommonParser($_parentKey, $_key, $_value)
  {
    //do nothing
  }


  protected function getAllIds(): array
  {

    return Schedule::currentSeasonSchedules()
      ->with('league')
      ->where('status', ScheduleStatus::PLAYED)
      ->orWhere('status', ScheduleStatus::AWARDED)
      ->pluck('id')
      ->toArray();

    // return GameSchedule::Where('status', ScheduleStatus::PLAYED)
    //   ->orWhere('status', ScheduleStatus::AWARDED)
    //   ->pluck('schedule_id')
    //   ->toArray();
  }

  protected function getDailyIds(): array
  {
    return Schedule::currentSeasonSchedules()
      ->with('league')
      ->where('started_at', '>', Carbon::parse(now())->subDays(10))
      ->where(function ($query) {
        $query->where('status', ScheduleStatus::PLAYED)
          ->orWhere('status', ScheduleStatus::AWARDED);
      })
      ->pluck('id')
      ->toArray();
  }

  // protected function makeTeamSide($_datas, $_response): array
  // {
  //   foreach ($_response['matchInfo']['contestant'] as $teamSet) {
  //     $_datas['commonRowOrigin'][$teamSet['position'] . '_' . 'team_id'] = $teamSet['id'];
  //   }
  //   return $_datas;
  // }


  // protected function insertOptaDatasToTables(
  //   array $_responses,
  //   array $_commonInfoToStore = null,
  //   array $_specifiedInfoToStore = null,
  //   $_realStore = false,
  // ): void {
  //   foreach ($_responses as $urlKey => $response) { // 비동기 응답s 처리

  //     $datas = $this->makeTeamSide($this->preProcessResponse($urlKey, $response), $response);

  //     // data 체크->
  //     if (!$_realStore) {
  //       logger($datas['commonRowOrigin']);
  //       logger($datas['specifiedAttrs']);
  //       $this->generateColumnNames();
  //       dd('-xTestx-');
  //     }

  //     try {
  //       $this->insertDatas($_commonInfoToStore, $_specifiedInfoToStore, $datas);
  //       logger("after commit");
  //     } catch (Exception $e) {
  //       __telegramNotify(NotifyLevel::CRITICAL, 'MA8:', '에러 발생 로그 체크!');
  //       report(new OTPInsertException(null, ['feed_nick' => $this->feedNick, 'path' => $this->current_url_extra,], $e));
  //     } finally {
  //     }
  //   }
  // }


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
        $ids = $this->getAllIds();
        # code...
        break;
    }

    // optaParser 설정 -->>
    $this->setGlueChildKeys(array_merge($this->getGlueChildKeys(), ['value', 'type']));
    $this->setKeyNameTransMap(
      array_merge(
        $this->getKeyNameTransMap(),
        ['personId' => 'playerId', 'personType' => 'playerType', 'personPosition' => 'playerPosition']
      )
    );
    $this->setKGsToCustom(['/person']);
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
            'specifiedInfoMap' => ['person' => Suspension::class],
            'conditions' => ['player_id', 'suspension_start_date'],
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
