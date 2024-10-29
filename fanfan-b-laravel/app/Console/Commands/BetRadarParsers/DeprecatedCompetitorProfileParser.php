<?php

namespace App\Console\Commands\BetRadarParsers;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\Schedule;
use App\Models\game\GameSchedule;
use Carbon\Carbon;

class DeprecatedCompetitorProfileParser extends BaseBetRadarParser
{
  use FantasyMetaTrait;
  private const REQUEST_COUNT_AT_ONCE = 20;

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'competitor_profile';
    $this->feedNick = 'BR_CP';
  }


  protected function customParser($_parent_key, $_key, $_value)
  {
    if ($_key === 'message') {
      foreach ($_value as $idx => $messageSet) {
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
    $ids = [93741];

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

      $responses = $this->betRadarRequest($idChunk);

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
