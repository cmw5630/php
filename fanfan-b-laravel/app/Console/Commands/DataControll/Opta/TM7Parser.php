<?php

namespace App\Console\Commands\DataControll\Opta;

use App\Console\Commands\OptaParsers\BaseOptaParser;
use App\Enums\Opta\YesNo;
use App\Models\data\Transfer;

class TM7Parser extends BaseOptaParser
{
  protected const REQUEST_COUNT_AT_ONCE = 1;
  protected $plateCardSeasonId;
  protected $plateCardTeamId;
  protected $plateCardRow;
  protected $ids;

  public function __construct(array $_plateCardRow)
  {
    parent::__construct();
    $this->feedType = 'transfers';
    $this->feedNick = 'TM7';
    $this->plateCardRow = $_plateCardRow;
    $this->plateCardSeasonId = [$_plateCardRow['season_id']];
    $this->plateCardTeamId = [$_plateCardRow['team_id']];
    $this->ids = [$_plateCardRow['player_id']];
  }

  protected function customParser($_parentKey, $_key, $_value)
  {
    if ($_key === 'person') {
      $this->extractRepeatedSpecifiedWithId($_value, 'person', 'id', 'membership', 'contestantId', 'transfer');
    }
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
    // optaParser 설정 -->>
    $this->setGlueChildKeys(array_merge($this->getGlueChildKeys(), ['type']));
    $this->setKeyNameTransMap(
      array_merge(
        $this->getKeyNameTransMap(),
        ['personId' => 'playerId', 'personType' => 'playerType', 'personPosition' => 'playerPosition']
      )
    );
    $this->setKGsToCustom(['/person']);
    // optaParser 설정 <<--
    $responses = $this->optaRequest($this->ids);
    if (empty($responses)) return [];


    $this->insertOptaDatasToTables(
      $responses,
      null,
      [
        [
          'specifiedInfoMap' => ['person' => Transfer::class],
          'conditions' => ['player_id', 'membership_idx'],
          // 'conditions' => [],  // 중복데이터 있음
        ],
      ],
      $_act
    );

    $processedData = $this->preProcessResponse('nothing', array_values($responses)[0])['specifiedAttrs']['person'] ?? [];
    foreach ($processedData as $idx => $data) {
      if ($this->plateCardRow['team_id'] === $data['team_id'] && $data['active'] === YesNo::YES) {
        return $data;
      }
    }

    logger(sprintf('player_id: %s', $this->plateCardRow['player_id']) . '(현재 Transfer가 업데이트가 되지 않음(게으른 opta update - 경고 +1))');
    return [];
  }
}
