<?php

namespace App\Console\Commands\DataControll\Opta;

use App\Console\Commands\OptaParsers\BaseOptaParser;
use App\Enums\Opta\YesNo;
use App\Models\data\PlayerCareer;
use App\Models\data\Season;
use Illuminate\Support\Collection;
use Str;

class PE2Parser extends BaseOptaParser
{
  protected const REQUEST_COUNT_AT_ONCE = 1;
  protected $ids;
  protected $plateCardRow;
  protected $plateCardSeasonName;
  protected $plateCardSeasonId;
  protected $plateCardTeamId;
  protected $plateCardPlayerId;
  protected $beforeCurrentSeasonIdMap = [];

  public function __construct(array $_plateCardRow)
  {
    parent::__construct();
    $this->feedType = 'playercareer';
    $this->feedNick = 'PE2';
    $this->beforeCurrentSeasonIdMap = Season::getBeforeCurrentMapCollection()->keyBy('current_id');
    $this->plateCardRow = $_plateCardRow;
    $this->plateCardSeasonName = $this->beforeCurrentSeasonIdMap[$_plateCardRow['season_id']]['current_season_name'];
    $this->plateCardSeasonId = $_plateCardRow['season_id'];
    $this->plateCardTeamId = $_plateCardRow['team_id'];
    $this->plateCardPlayerId = $_plateCardRow['player_id'];
    $this->ids = [$this->plateCardPlayerId];
  }


  protected function customParser($_parentKey, $_key, $_value)
  {
    if ($_key === 'person') {
      $membershipIdx = 0;
      foreach ($_value as $person_idx => $person_collection) {
        $common_attr = [];
        $person_temp_attrs = [];
        $membership = $person_collection['membership'];
        unset($person_collection['membership']);
        $player_unique_key = $person_collection['id'];
        foreach ($person_collection as $player_key => $player_value) {
          $person_temp_attrs[$this->correctKeyName('player', $player_key)] = $player_value;
        }
        $common_attr = array_merge($common_attr, $person_temp_attrs);
        foreach ($membership as $membership_idx => $membership_collection) {
          $contestant_temp_attrs = [];
          $stats = $membership_collection['stat'];
          unset($membership_collection['stat']);

          foreach ($membership_collection as $ctstInfoKey => $ctstInfoValue) {
            $contestant_temp_attrs[$this->correctKeyName('membership', $ctstInfoKey)] = $ctstInfoValue;
          }

          $total_attrs = array_merge($common_attr, $contestant_temp_attrs);
          // logger($common_attr);
          foreach ($stats as $statIdx => $someStats) {
            $member_unique_key = $player_unique_key . '_' . $total_attrs['contestantId'] . '_' . $someStats['tournamentCalendarId'] . $statIdx;
            $someStats['membershipIdx'] = $membershipIdx++;

            $membership_temp_attrs = array_merge($total_attrs, $someStats);
            $this->appendTargetSpecifiedAttrsByIndex(
              'person',
              $member_unique_key,
              $membership_temp_attrs,
            );
          }
        }
      }
    }
    //do nothing
  }


  protected function customCommonParser($_parentKey, $_key, $_value)
  {
    // do nothing
  }


  public function startOpta(bool $_act = false)
  {
    return $this->parse($_act);
  }



  protected function parse(bool $_act)
  {
    return $this->getActiveCareers($_act);
  }

  protected function isOptaRecentlyUpdate($_results): bool
  {
    foreach ($_results as $key => $membershipItem) {
      if (
        $membershipItem['season_id'] === $this->plateCardSeasonId &&
        $membershipItem['team_id'] === $this->plateCardTeamId &&
        $membershipItem['player_id'] === $this->plateCardPlayerId
      ) {
        return true;
      }
    }
    return false;
  }

  protected function careerSorted(&$_results)
  {
    // season_name, membership_start_date 순으로 정렬
    foreach ($_results as $key => $item) {
      $_results[$key]['season_order_name'] = Str::after($_results[$key]['season_name'], '/');
    }
    $_results = __sortByKeys($_results, ['keys' => ['season_order_name', 'membership_start_date'], 'hows' => ['desc']]);
  }

  protected function takeBeforeSeasonCareer(&$_results)
  {
    $_results = array_values(array_filter($_results, function ($item) {
      return (int)$item['season_order_name'] === ((int)Str::after($this->plateCardSeasonName, '/') - 1);
    }));
  }

  protected function getActiveCareers(bool $_act): array
  {
    // false 반환: career가 업데이트 안됨.(현재 플레이어가 소속된 팀, 리그(시즌)에 대한 Career 데이터가 없음)
    // 빈 array 반환: career는 업데이트 되었지만 이전 시즌 커리어가 존재하지 않음.

    // optaParser 설정 -->>
    $this->setGlueChildKeys(array_merge($this->getGlueChildKeys(), ['type']));
    $this->setKGsToCustom(['/person']);
    // optaParser 설정 <<--
    // $responses = $this->optaRequest($this->ids);

    $responses = $this->optaRequest($this->ids);
    if (empty($responses)) return [];

    $this->insertOptaDatasToTables(
      $responses,
      null,
      [
        [
          'specifiedInfoMap' => ['person' => PlayerCareer::class],
          'conditions' => ['player_id', 'membership_idx']
        ],
      ],
      $_act
    );

    $processedData = $this->preProcessResponse('nothing', array_values($responses)[0])['specifiedAttrs']['person'] ?? [];
    $results = collect($processedData)->filter(function ($membershipItem) {
      return $membershipItem['league_format'] == 'Domestic league';
    })->toArray();

    if (!$this->isOptaRecentlyUpdate($results)) { // 데이터 체크
      logger(sprintf('player_id: %s', $this->plateCardPlayerId) . '(현재 career가 업데이트가 되지 않음(게으른 opta update - 경고 +1))');
      return [];
    }

    $this->careerSorted($results); // 정렬
    $this->takeBeforeSeasonCareer($results); // 필터
    return $results;
  }
}
