<?php

namespace App\Console\Commands\OptaParsers;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Season\SeasonNameType;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\ParserMode;
use App\Exceptions\Custom\Parser\OTPDataMissingException;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\OptaPlayerSeasonRanking;
use App\Models\data\OptaScheduleSeasonRanking;
use App\Models\data\OptaTeamSeasonRanking;
use App\Models\data\Season;
use Exception;
use LogEx;
use Str;

// http://api.performfeeds.com/soccerdata/rankings/1vmmaetzoxkgg1qf6pkpfmku0k?tmcl=408bfjw6uz5k19zk4am50ykmh&_rt=b&_fmt=json 
// http://api.performfeeds.com/soccerdata/rankings/1vmmaetzoxkgg1qf6pkpfmku0k/408bfjw6uz5k19zk4am50ykmh?_rt=b&_fmt=json 
class PE4SeasonRankingParser extends BaseOptaParser
{
  use FantasyMetaTrait;
  protected const REQUEST_COUNT_AT_ONCE = 5;

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'rankings';
    $this->feedNick = 'PE4';
  }


  protected function typeValueToKeyValue($_type_value_collection_array): array
  {
    $temp_array = [];
    foreach ($_type_value_collection_array as $idx => $collection) {
      if (isset($collection['type']) and isset($collection['value'])) {
        $temp_array[Str::replace(' ', '_', trim($collection['type']))] = $collection['value'];
      }
    }

    return $temp_array;
  }


  protected function customParser($_parentKey, $_key, $_value)
  {
    if ($_key === 'matchData') {
      foreach ($_value as $match_idx => $match_collection) {
        $temp_match_attrs = [];
        if (!isset($match_collection['id'])) {
          continue;
        }
        $matchid = $match_collection['id'];
        $temp_match_attrs['matchId'] = $matchid;
        foreach ($match_collection['teamData'] as $team_idx => $team_value) {
          try {
            $side = strtolower($team_value['side']);
            $temp_match_attrs[$side . 'ContestantId'] = $team_value['id'];
          } catch (Exception $e) {
            report(new OTPDataMissingException(null, ['feed_nick' => $this->feedNick, 'path' => $this->current_url_extra,], $e, $team_value));
            continue;
          }
        }
        if (isset($match_collection['stat'])) {
          $temp_match_attrs = array_merge($temp_match_attrs, $this->typeValueToKeyValue($match_collection['stat']));
        }
        $this->appendTargetSpecifiedAttrsByIndex(
          'schedule_ranking',
          $match_idx,
          $temp_match_attrs
        );
      }
    } else if ($_key === 'team') {
      // team
      $this->teamStatParserOnTeamSnippet('team_ranking', $_value);
      // player
      $this->playerParseOnTeamSnippet('player_ranking', $_value);
    }
  }

  protected function customCommonParser($_parentKey, $_key, $_value)
  {
    //do nothing
  }
  protected function getAllIds()
  {
    return Season::idsOf(
      [SeasonWhenType::BEFORE, SeasonWhenType::CURRENT, SeasonWhenType::FUTURE],
      SeasonNameType::ALL,
      3,
      ['4oogyu6o156iphvdvphwpck10,'],
    );
  }

  protected function getDailyIds()
  {
    return Season::idsOf(
      [SeasonWhenType::CURRENT],
      SeasonNameType::ALL,
      1,
      ['4oogyu6o156iphvdvphwpck10,']
    );
  }


  protected function getElasticIds()
  {
    return $this->getDailyIds();
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
          case FantasySyncGroupType::ELASTIC:
            $ids = $this->getElasticIds();
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
        $ids = $this->getDailyIds();
        break;
    }

    // optaParser 설정 -->>
    $this->setKGsToCustom(['/matchData', '/team']);
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
            'specifiedInfoMap' => ['schedule_ranking' => OptaScheduleSeasonRanking::class],
            'conditions' => ['season_id', 'schedule_id']
          ],
          [
            'specifiedInfoMap' => ['team_ranking' => OptaTeamSeasonRanking::class],
            'conditions' => ['season_id', 'team_id']
          ],
          [
            'specifiedInfoMap' => ['player_ranking' => OptaPlayerSeasonRanking::class],
            'conditions' => ['season_id', 'team_id', 'player_id']
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
