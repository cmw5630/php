<?php

namespace App\Console\Commands\OptaParsers;

use App\Console\Commands\OptaParsers\BaseOptaParser;
use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\Commentary;
use App\Models\data\Schedule;
use App\Models\game\GameSchedule;
use Carbon\Carbon;

// https://api.performfeeds.com/soccerdata/tournamentschedule/1vmmaetzoxkgg1qf6pkpfmku0k/css9eoc46vca8gkmv5z7603ys?_fmt=json&_rt=b
class MA6CommentaryParser extends BaseOptaParser
{
  use FantasyMetaTrait;
  private const REQUEST_COUNT_AT_ONCE = 20;
  protected $ids;
  protected $ingameMode;

  public function __construct(string $_scheduleId = null, $_ingameMode = false)
  {
    parent::__construct();
    $this->feedType = 'commentary';
    $this->feedNick = 'MA6';
    $this->ids = [$_scheduleId];
    $this->ingameMode = $_ingameMode;
  }


  protected function customParser($_parentKey, $_key, $_value)
  {
    if ($_key === 'messages') {
      $language = $_value[0]['language'];
      $messageList = $_value[0]['message'];
      foreach ($messageList as $idx => $messageSet) {
        $messageSet['comment_id'] = $messageSet['id'];
        unset($messageSet['id']);
        if (isset($messageSet['time'])) {
          $messageSet['time_summary'] = $messageSet['time'];
          unset($messageSet['time']);
        }
        $this->appendTargetSpecifiedAttrsByIndex(
          'message',
          $idx,
          $messageSet
        );
      }
    }
    if ($_key === 'contestant') {
      $this->contestantCommonNamingSnippet($_key, $_value);
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
    return GameSchedule::where(function ($query) {
      $query->whereIn('status', [ScheduleStatus::FIXTURE, ScheduleStatus::PLAYING])
        ->whereHas('schedule', function ($query) {
          return $query->where('started_at', '<', Carbon::now()->addMinute(config('constant.COLLECT_MA2_LIVE_START_MINUTE_BEFORE')));
        });
    })->orWhere(function ($query) {
      $query->where('status',  [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
        ->where([
          ['updated_at', '<', Carbon::now()],
          ['updated_at', '>', Carbon::now()->subminutes(3)],
        ]);
    })->pluck('schedule_id')->toArray();
  }

  private function getDailyIds(): array
  {
    return GameSchedule::whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
      ->whereHas('schedule', function ($query) {
        $query->where('started_at', '>', Carbon::now()->subDays(3));
      })
      ->whereHas('schedule.season', function ($query) {
        return $query->currentSeasons();
      })
      ->pluck('schedule_id')->toArray();
  }

  private function getAllIds(): array
  {
    // 지난 시즌 코멘터리를 수집하지 않음.
    return Schedule::whereHas('season', function ($query) {
      $query->currentSeasons();
    })
      ->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
      ->oldest('started_at')->pluck('id')->toArray();
  }

  private function ingamemModeStart()
  {
    $responses = $this->optaRequest($this->ids);

    foreach ($responses as $urlKey => $response) { // 비동기 응답s 처리
      $datas = $this->preProcessResponse($urlKey, $response);
    }

    // foreach ($_responses as $urlKey => $response) { // 비동기 응답s 처리
    //   $datas = $this->preProcessResponse($urlKey, $response);

    //   // data 체크->
    //   if (!$_realStore) {
    //     logger($datas['commonRowOrigin']);
    //     logger($datas['specifiedAttrs']);
    //     $this->generateColumnNames();
    //     dd('-xTestx-');
    //   }
    //   // data 체크<-

    //   $this->insertDatas($_commonInfoToStore, $_specifiedInfoToStore, $datas);
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
          default:
            # code...
            break;
        }

      case ParserMode::PARAM:
        if ($this->getParam('mode') === 'live') {
          $ids = $this->getLiveIds();
        }
        # code...
        break;
      default:
        # code...
        break;
    }

    if ($this->ingameMode && $this->ids) {
      $this->ingamemModeStart();
      return true; //종료
    }

    // 비동기 동시처리 수로 쪼개기
    $idChunks = array_chunk($this->ids, self::REQUEST_COUNT_AT_ONCE);

    // optaParser 설정 -->>
    // $this->setKeysToIgnore(['period', 'goal', 'card', 'substitute', 'VAR', 'matchOfficial']);
    // $this->setKGsToCustom(['matchInfo/contestant', 'liveData/lineUp']);
    // $this->setKeysToIgnore(['period', 'goal', 'card', 'substitute', 'VAR', 'matchOfficial']);
    // $this->setCommonKGsToCustom(['scores/ht', 'scores/ft', 'scores/total']);
    $this->setKGsToCustom(['matchInfo/contestant', '/messages']);
    // optaParser 설정 <<--

    $totalChucks = count($idChunks);
    foreach ($idChunks as $idx => $idChunk) {
      if (isset($this->param['chunk']) && $idx < $this->param['chunk']) {
        continue;
      }
      __loggerEx($this->feedType, 'loop $i : ' . $idx . ' / ' . $totalChucks);
      $responses = $this->optaRequest($idChunk);

      foreach ($idChunks as $idx => $idChunk) {
        __loggerEx($this->feedType, 'loop $i : ' . $idx);

        $responses = $this->optaRequest($idChunk);

        $this->insertOptaDatasToTables($responses, null, [
          [
            'specifiedInfoMap' => [
              'message' => Commentary::class
            ],
            'conditions' => ['comment_id']
          ]
        ], $_act);
      }
    }

    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}
