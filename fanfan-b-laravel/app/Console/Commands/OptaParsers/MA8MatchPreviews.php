<?php

namespace App\Console\Commands\OptaParsers;

use App\Console\Commands\OptaParsers\BaseOptaParser;
use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\ParserMode;
use App\Enums\System\NotifyLevel;
use App\Exceptions\Custom\Parser\OTPInsertException;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\MatchPreview;
use App\Models\data\Schedule;
use App\Models\game\GameSchedule;
use Carbon\Carbon;
use Exception;

class MA8MatchPreviews extends BaseOptaParser
{
  use FantasyMetaTrait;
  protected const REQUEST_COUNT_AT_ONCE = 20;

  protected $ids;

  public function __construct(array|string $_ids = [])
  {
    parent::__construct();
    $this->feedType = 'matchpreview';
    $this->feedNick = 'MA8';
    if (gettype($_ids) == 'string') {
      $this->ids = [$_ids];
    } else if (gettype($_ids) == 'array') {
      $this->ids = $_ids;
    }
  }

  private function ma8Snippet($_key, $_value)
  {
    $seedArray = [];
    if ($_key === 'previousMeetings' or $_key === 'previousMeetingsAnyComp') {
      $seedArray[] = $_value;
    } else if ($_key === 'form' or $_key === 'formAnyComp') {
      $seedArray = $_value;
    }
    foreach ($seedArray as $idx => $ctstItem) {
      $match = $ctstItem['match'];
      unset($ctstItem['match']);
      foreach ($match as $matchIdx => $preMatchValue) {
        $preTempAttrs = $ctstItem;
        $preTempAttrs['lastSix'] = $ctstItem['lastSix'] ?? null;
        $preTempAttrs['preview_category'] = $_key;
        $preMatchId = $preMatchValue['id'];
        $preContestantTemp = [];
        foreach ($preMatchValue['contestants'] as $k => $v) {
          $preContestantTemp['pre_' . $k] = $v;
        }

        $preTempAttrs['pre_matchId'] = $preMatchValue['id'] ?? null;
        $preTempAttrs['pre_date'] = $preMatchValue['date'] ?? null;
        $preTempAttrs['pre_competitionCode'] = $preMatchValue['competitionCode'] ?? null;
        $preTempAttrs['pre_country'] = $preMatchValue['country'] ?? null;
        $preTempAttrs['pre_countryId'] = $preMatchValue['countryId'] ?? null;
        $preTempAttrs = array_merge($preTempAttrs, $preContestantTemp);

        foreach ($preMatchValue['goal'] as $goal_idx => $goalValue) {
          $goalTempValue = [];
          foreach ($goalValue as $k => $v) {
            $goalTempValue['pre_' . $k] = $v;
          }
          $preTempAttrs = array_merge($preTempAttrs, $goalTempValue);
        }
        $this->appendTargetSpecifiedAttrsByIndex(
          $_key,
          $preMatchId . '_' . $matchIdx,
          $preTempAttrs
        );
      }
    }
  }


  protected function customParser($_parentKey, $_key, $_value)
  {
    if ($_key === 'contestant') {
      $this->contestantCommonNamingSnippet($_key, $_value);
    } else if (
      $_key === 'previousMeetings' or
      $_key === 'previousMeetingsAnyComp' or
      $_key === 'form' or
      $_key === 'formAnyComp'
    ) {
      $this->ma8Snippet($_key, $_value);
    }
  }


  protected function customCommonParser($_parentKey, $_key, $_value)
  {
    // do nothing
  }

  protected function getAllIds(): array
  {
    return Schedule::whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
      ->oldest('started_at')
      ->get()
      ->pluck('id')
      ->toArray();
  }

  protected function getDailyIds(): array
  {
    return Schedule::whereIn('status', [ScheduleStatus::FIXTURE, ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
      ->whereBetween('started_at', [
        Carbon::now()->subDays(2),
        Carbon::now()->addDays(7),
      ])
      ->get()
      ->pluck('id')
      ->toArray();
  }

  protected function makeTeamSide($_datas, $_response): array
  {
    foreach ($_response['matchInfo']['contestant'] as $teamSet) {
      $_datas['commonRowOrigin'][$teamSet['position'] . '_' . 'team_id'] = $teamSet['id'];
    }
    return $_datas;
  }


  protected function insertOptaDatasToTables(
    array $_responses,
    array $_commonInfoToStore = null,
    array $_specifiedInfoToStore = null,
    $_realStore = false,
  ): void {
    foreach ($_responses as $urlKey => $response) { // 비동기 응답s 처리

      $datas = $this->makeTeamSide($this->preProcessResponse($urlKey, $response), $response);

      // data 체크->
      if (!$_realStore) {
        logger($datas['commonRowOrigin']);
        logger($datas['specifiedAttrs']);
        $this->generateColumnNames();
        dd('-xTestx-');
      }

      try {
        $this->insertDatas($_commonInfoToStore, $_specifiedInfoToStore, $datas);
      } catch (Exception $e) {
        __telegramNotify(NotifyLevel::CRITICAL, 'MA8:', '에러 발생 로그 체크!');
        report(new OTPInsertException(null, ['feed_nick' => $this->feedNick, 'path' => $this->current_url_extra,], $e));
      } finally {
      }
    }
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
        $ids = $this->ids;
        # code...
        break;
    }

    // optaParser 설정 -->>
    // $this->setKGsToCustom(['matchInfo/contestant', '/previousMeetings', '/previousMeetingsAnyComp', '/form', '/formAnyComp']);
    $this->setKGsToCustom(['/previousMeetingsAnyComp', '/form', '/formAnyComp']);
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
        [
          'common_table_name' => MatchPreview::class,
          'conditions' => ['home_team_id', 'away_team_id'],
        ],
        null,
        // [
        //   [
        //     'specifiedInfoMap' => ['preview' => MatchPreview::class],
        //     'conditions' => ['home_team_id', 'away_team_id']
        //   ],
        // ],
        $_act
      );
    }

    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}
