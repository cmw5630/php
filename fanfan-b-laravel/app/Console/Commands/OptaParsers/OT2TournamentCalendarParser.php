<?php

namespace App\Console\Commands\OptaParsers;

// https://api.performfeeds.com/soccerdata/tournamentcalendar/1vmmaetzoxkgg1qf6pkpfmku0k/authorized?_fmt=json&_rt=b
// league, season

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\League;
use App\Models\data\Season;
use Exception;
use LogEx;

class OT2TournamentCalendarParser extends BaseOptaParser
{
  use FantasyMetaTrait;

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'tournamentcalendar';
    $this->feedNick = 'OT2';
  }

  protected function customParser($_parentKey, $_key, $_value)
  {
    __loggerEx($this->feedType, $_parentKey . '->' . $_key);
    if ($_key === 'competition') {
      $tournamentCalendars = $_value['tournamentCalendar'];
      unset($_value['tournamentCalendar']);
      $competitionInfo = $_value; // $_value->competition정보
      foreach ($competitionInfo as $key => $value) {
        $this->setCommonAttrs(
          null,
          $key,
          $value
        );
      }
      unset($key);

      foreach ($tournamentCalendars as $value) {
        $value['competitionId'] = $competitionInfo['id'];
        $this->setTargetSpecifiedAttrs('tournamentCalendar', $value);
        // $value['competitionName'] = $competitionInfo['name'];
        // $value['competitionCode'] = $competitionInfo['competitionCode'];
      }
    }
  }

  protected function customCommonParser($_parentKey, $_key, $_value)
  {
    // do nothing;
  }

  protected function parse(bool $_act): bool
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
          case FantasySyncGroupType::CONDITIONALLY:
            break;
          default:
            # code...
            break;
        }
        //
      case ParserMode::PARAM:
        # code...
        break;
      default:
        # code...
        break;
    }

    $responses = $this->optaRequest();

    // optaParser 설정 -->>
    $this->setGlueChildKeys([]);
    // optaParser 설정 <<--


    // --> competitions 테이블에 저장 <--

    $this->insertOptaDatasToTables(
      $responses,
      null,
      [
        [
          'specifiedInfoMap' => ['competition' => League::class],
          'conditions' => ['id'],
        ]
      ],
      $_act
    );

    // --> tournament_calendars 테이블에 저장 <--

    // optaParser 설정 -->>
    $this->setKGsToCustom(['/competition']);
    // optaParser 설정 <<--

    // 특수한 경우임 - insertOptaDatasToTables 메소드에 전달할 파라미터 형식에 맞게 response 분해 및 재조합 
    foreach ($responses as $key => $value) {
      $modReponse = [];
      foreach ($value as $subjectKey => $compWithToursGroup) {
        if ($subjectKey !== 'competition') continue;
        foreach ($compWithToursGroup as $compWithTours) {
          $modReponse[$key] = [$subjectKey => $compWithTours];
          $this->insertOptaDatasToTables(
            $modReponse,
            null,
            [
              [
                'specifiedInfoMap' => ['tournamentCalendar' => Season::class],
                'conditions' => ['id'],
              ]
            ],
            $_act
          );
        }
      }
    }

    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}
