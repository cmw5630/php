<?php

namespace App\Console\Commands\OptaParsers;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Season\SeasonNameType;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\game\GamePossibleSchedule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use LogEx;
use Str;

// https://api.performfeeds.com/soccerdata/matchdetailed/1vmmaetzoxkgg1qf6pkpfmku0k?tmcl=93wcwtf25153hracvqva88aac&_rt=b&_fmt=json&lineups=yes&live=yes
// 경기 시간, 날짜, 팀, 경쟁, 라운드, 시즌 또는 주요 이벤트(라인업, 카드, 골, 교체, 어시스트) 업데이트 시 업데이트됨
// 경기당 12~24시간으로 업데이트
class MA1FixtureAndResultsParser extends BaseOptaParser
{
  use FantasyMetaTrait;

  protected const REQUEST_COUNT_AT_ONCE = 20;

  protected $liveReadyScheduleIds = [];

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'matchdetailed';
    $this->feedNick = 'MA1_detailed';

    $this->liveReadyScheduleIds = GamePossibleSchedule::whereIn(
      'status',
      [ScheduleStatus::FIXTURE, ScheduleStatus::PLAYING]
    )->whereHas('schedule', function ($query) {
      $query->whereBetween(
        'started_at',
        [
          Carbon::now()->subDays(1),
          Carbon::now()->addDays(1),
        ]
      );
    })->pluck('schedule_id')->toArray();
  }

  protected function makeFormationUsedMap($_match, &$_matchInfo): void
  {
    $teamIdOfSide = [];

    if (!isset($_matchInfo['contestant']) || count($_matchInfo['contestant']) < 2) {
      return;
    }

    foreach ($_matchInfo['contestant'] as $teamSet) {
      // meta 안돌길래 조건 추가 champs schedule_id = 6s94g4vf27iy9px92iq8fjsic
      if (!isset($teamSet['id'])) {
        return;
      }
      $teamIdOfSide[$teamSet['id']] = $teamSet['position'];
    }
    $formationUsedMap = [
      'home_formation_used' => null,
      'away_formation_used' => null,
    ];
    if (isset($_match['liveData']['lineUp'])) {
      foreach ($_match['liveData']['lineUp'] as $teamSet) {
        if (isset($teamSet['formationUsed'])) {
          $formationUsedMap[$teamIdOfSide[$teamSet['contestantId']] . '_formation_used'] = $teamSet['formationUsed'];
        }
      }
    }
    $_matchInfo = array_merge($_matchInfo, $formationUsedMap);
  }


  protected function isLiveReadyScheduleIds($_scheduleId): bool
  {
    if (in_array($_scheduleId, $this->liveReadyScheduleIds)) {
      logger('live Ready Schedule Id:' . $_scheduleId);
      return true;
    }
    return false;
  }

  protected function isFinishedScheduleStatus($_match)
  {
    $scheduleStatus = $_match['liveData']['matchDetails']['matchStatus'];
    $scheduleId = $_match['matchInfo']['id'];

    // "게임에 묶인 schedule에 대해서"만 종료여부를 살펴 필터링해야 한다.
    if (!GamePossibleSchedule::where('schedule_id', $scheduleId)->exists()) {
      return false;
    }

    if (
      $this->getSyncGroup() === FantasySyncGroupType::DAILY &&
      ($scheduleStatus == ScheduleStatus::PLAYED || $scheduleStatus == ScheduleStatus::AWARDED)
    ) return true;

    return false;
  }

  protected function makeEnddedAt($match, &$matchInfo)
  {
    // 경기 종료 시간 수집.
  }

  protected function customParser($_parentKey, $_key, $_value)
  {
    if ($_key === 'match') {
      foreach ($_value as $idx => $match) {
        $matchInfo = $match['matchInfo'];
        if ($this->isLiveReadyScheduleIds($matchInfo['id']) || $this->isFinishedScheduleStatus($match)) {
          // 라이브와 겹치면 라이브 종료 프로세스없이 상태가 변할 수 있으므로 
          // 라이브 근처(now로 부터 하루 전 ~ 하루 후 까지의) schedule은 수집하지 않는다.
          continue;
        }
        $this->makeFormationUsedMap($match, $matchInfo);
        // 참여팀이 아직 정해지지 않은 경우
        if (!isset($matchInfo['contestant']) || count($matchInfo['contestant']) < 2) {
          unset($_value[$idx]);
          continue;
        }
        $glueParentKeys = ['contestant', 'tournamentCalendar', 'competition', 'stage'];
        $this->moveToTop($matchInfo, $glueParentKeys);
        $matchDetails = $match['liveData']['matchDetails'];
        $glueParentKeys = ['scores/total/home', 'scores/total/away'];
        $glueTargetKeys = ['scoreHome', 'scoreAway'];
        $this->moveToTop($matchDetails, $glueParentKeys, $glueTargetKeys);

        // 라이브 시 조건
        // Playing 상태,
        // Fixture 상태에서 시간이 30분 이내인 것
        if ($this->getParam('mode') === 'live') {
          if (!(($matchDetails['matchStatus'] === ScheduleStatus::PLAYING) ||
            ($matchDetails['matchStatus'] === ScheduleStatus::FIXTURE && !empty($matchInfo['date']) && Carbon::parse($matchInfo['date'] . ' ' . $matchInfo['time'])->diffInMinutes(
              now(),
              false
            ) > -3000))) {
            unset($_value[$idx]);
            continue;
          }
        }
        foreach ($matchDetails as $key => $val) {
          $matchInfo[$key] = $val;
        }
        if (!empty($this->getKeyNameTransMap())) {
          foreach ($this->getKeyNameTransMap() as $old => $new) {
            if (isset($matchInfo[$old])) {
              $matchInfo[$new] = $matchInfo[$old];
              unset($matchInfo[$old]);
            }
          }
        }

        $this->appendTargetSpecifiedAttrsByIndex(
          'matchInfo',
          $matchInfo['id'],
          $matchInfo
        );
      }
    }
  }

  protected function moveToTop(&$_topData, $_glueDepthMap = [], $_glueTargetKeys = [], $_curDepthData = null, $_glueCombined = null)
  {
    $_curDepthData = $_curDepthData ?? $_topData;

    $glueKeys = array_unique(array_map(fn($value) => Str::before($value, '/'), $_glueDepthMap));

    foreach ($_curDepthData as $depthInfoKey => $depthInfoAttr) {
      if (in_array($depthInfoKey, $glueKeys)) {
        foreach ($depthInfoAttr as $key => $attribute) {
          if (is_numeric($key)) {
            // 이 데이터는 배열
            foreach ($attribute as $name => $value) {
              $_topData[Str::lcfirst($attribute['position'] . Str::ucfirst($depthInfoKey) . Str::ucfirst($name))] = $value;
            }
            continue;
          }
          if (!is_array($attribute)) {
            $_topData[Str::lcfirst($_glueCombined . Str::ucfirst($depthInfoKey) . Str::ucfirst($key))] = $attribute;
            continue;
          }
          $this->moveToTop(
            $_topData,
            array_map(fn($value) => Str::after($value, '/'), $_glueDepthMap),
            [],
            $depthInfoAttr,
            $_glueCombined . Str::ucfirst($depthInfoKey)
          );
        }
      } else {
        if (is_array($depthInfoAttr)) {
          $this->moveToTop($_topData, $_glueDepthMap, [], $depthInfoAttr, $_glueCombined);
        }
      }
    }
    if ($_glueCombined === null) {
      foreach ($glueKeys as $key) {
        unset($_topData[$key]);
      }

      if (count($_glueTargetKeys) > 0) {
        $combinedKeys = array_map(fn($value) => Str::of($value)->replace('/', '_')->camel()->toString(), $_glueDepthMap);
        array_walk(
          $_glueTargetKeys,
          function ($value, $key) use (&$_topData, $_glueTargetKeys, $combinedKeys) {
            if (empty($_glueTargetKeys[$key]) || !isset($_topData[$combinedKeys[$key]])) return;
            $_topData[$_glueTargetKeys[$key]] = $_topData[$combinedKeys[$key]];
            unset($_topData[$combinedKeys[$key]]);
          }
        );
      }
    }
  }

  protected function customCommonParser($_parentKey, $_key, $_value)
  {
    $this->attrExtractor(
      $_value,
      $this->correctKeyName($_parentKey, $_key),
      true,
    );
  }

  protected function getAllIds()
  {
    return Season::idsOf([SeasonWhenType::CURRENT, SeasonWhenType::BEFORE, SeasonWhenType::FUTURE]);
  }

  protected function getDailyIds()
  {
    return Season::idsOf([SeasonWhenType::CURRENT]);
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
          case FantasySyncGroupType::CONDITIONALLY:
            $ids = $this->getDailyIds();
            break;
          default:
            # code...
            break;
        }

      case ParserMode::PARAM:
        if ($this->getParam('mode') === 'all') {
          $ids = $this->getAllIds();
        } else if ($this->getParam('mode') === 'daily')
          $ids = $this->getDailyIds();
        # code...
        break;
      default:
        # code...
        break;
    }

    // 현재시즌 / 전체시즌
    // if ($this->getParam('season') === 'current' || $this->getParam('mode') === 'live') {
    //   $seasonQuery = Season::query()->currentSeasons();
    // } else {
    //   $seasonQuery = Season::query();
    // }

    // $ids = $seasonQuery->pluck('id')->toArray();

    // 비동기 동시처리 수로 쪼개기
    $idChunks = array_chunk($ids, self::REQUEST_COUNT_AT_ONCE);

    // optaParser 설정 -->>
    // $this->setGlueChildKeys(['tournamentCalendar', 'contestant']);
    // $this->setKGsToCustom(['match/matchInfo', 'match/liveData']);
    // $this->setCommonKGsToCustom(['scores/ht', 'scores/ft', 'scores/total']);
    $this->setKeyNameTransMap(['matchStatus' => 'status', 'matchInfoId' => 'id']);
    $this->setKGsToCustom(['/match']);
    // optaParser 설정 <<--
    foreach ($idChunks as $idx => $idChunk) {
      __loggerEx($this->feedType, 'loop $i : ' . $idx);

      $pgNm = 1;
      do {
        $responses = $this->optaRequest($idChunk, "&_pgNm={$pgNm}");
        ++$pgNm;
        $this->insertOptaDatasToTables(
          $responses,
          null,
          [
            [
              'specifiedInfoMap' => [
                'matchInfo' => Schedule::class
              ],
              'conditions' => ['id']
            ]
          ],
          $_act
        );

        $newIdChunk = [];
        foreach ($idChunk as $index => $id) {
          foreach (array_keys($responses) as $ridx => $ruri) {
            if (Str::contains($ruri, $id)) {
              array_push($newIdChunk, $id);
              unset($idChunk[$index]);
              break;
            }
          }
        }
        $idChunk = $newIdChunk;
      } while (!empty($responses));
    }

    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    $currentSeasonIds = Season::idsOf([SeasonWhenType::CURRENT], SeasonNameType::ALL, 1);
    foreach ($currentSeasonIds as $seasonId) {
      $redisKeyName =  $seasonId . '_gameList';
      Redis::del($redisKeyName);
    }
    return $parsingStatus;
  }

  protected function insertOptaDatasToTables(
    array $_responses,
    array $_commonInfoToStore = null,  //ex) ['common_table_name' => 'common_table_name', 'conditions' => []],
    array $_specifiedInfoToStore = null, //ex) [ [ 'specifiedInfoMap' => ['penaltyShot' => 'penalty_shots'], 'conditions' => ['matchId', 'timestamp'] ] ],
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

      $this->insertDatas($_commonInfoToStore, $_specifiedInfoToStore, $datas, false);
    }
  }
}
