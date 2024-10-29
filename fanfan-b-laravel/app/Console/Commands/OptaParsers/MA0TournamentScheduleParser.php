<?php

namespace App\Console\Commands\OptaParsers;

use App\Models\data\Schedule;
use App\Models\data\Season;
use LogEx;

// https://api.performfeeds.com/soccerdata/tournamentschedule/1vmmaetzoxkgg1qf6pkpfmku0k/css9eoc46vca8gkmv5z7603ys?_fmt=json&_rt=b
class MA0TournamentScheduleParser extends BaseOptaParser
{
  private const REQUEST_COUNT_AT_ONCE = 20;

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'tournamentschedule';
    $this->feedNick = 'MA0';
  }

  protected function customParser($_parentKey, $_key, $_value): void
  {
    if ($_key === 'matchDate') {
      foreach ($_value as $matchOuter) {
        if (isset($matchOuter['match'])) {
          foreach ($matchOuter['match'] as $inner_idx => $singleMatchData) {
            // $singleMatchData['date'] = $matchOuter['date'];
            // $singleMatchData['numberOfGames'] = $matchOuter['numberOfGames'];
            $singleMatchData['matchId'] = $singleMatchData['id'];
            unset($singleMatchData['id']);
            $this->setTargetSpecifiedAttrs('match', $singleMatchData);
          }
        }
      }
    }
  }

  protected function customCommonParser($_parentKey, $_key, $_value)
  {
    // do nothing;
  }


  protected function parse(bool $_act)
  {
    // Tournamentcalendar(tmcl) Ids 얻기
    $ids = $this->uniqueValueListFromColumn(Season::class, ['id']);

    // 비동기 동시처리 수로 쪼개기
    $idChunks = array_chunk($ids, self::REQUEST_COUNT_AT_ONCE);

    // optaParser 설정 -->>
    // $this->setKeysToIgnore(['period', 'goal', 'card', 'substitute', 'VAR', 'matchOfficial']);
    // $this->setKGsToCustom(['matchInfo/contestant', 'liveData/lineUp']);
    // $this->setKeysToIgnore(['period', 'goal', 'card', 'substitute', 'VAR', 'matchOfficial']);
    // $this->setCommonKGsToCustom(['scores/ht', 'scores/ft', 'scores/total']);
    $this->setKGsToCustom(['/matchDate']);
    // optaParser 설정 <<--

    foreach ($idChunks as $idx => $idChunk) {
      __loggerEx($this->feedType, 'loop $i : ' . $idx);

      $responses = $this->optaRequest($idChunk);

      $this->insertOptaDatasToTables($responses, null, [
        [
          'specifiedInfoMap' => [
            'match' => Schedule::class
          ],
          'conditions' => ['schedule_id' => 'id']
        ]
      ], $_act);
    }
  }
}
