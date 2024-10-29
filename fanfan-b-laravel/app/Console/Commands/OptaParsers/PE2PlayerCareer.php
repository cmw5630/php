<?php

namespace App\Console\Commands\OptaParsers;

use App\Console\Commands\OptaParsers\BaseOptaParser;
use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\ParserMode;
use App\Enums\System\NotifyLevel;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\PlayerCareer;
use App\Models\game\PlateCard;
use Carbon\Carbon;
use Throwable;

class PE2PlayerCareer extends BaseOptaParser
{
  use FantasyMetaTrait;
  protected const REQUEST_COUNT_AT_ONCE = 5;

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'playercareer';
    $this->feedNick = 'PE2';
  }


  protected function customParser($_parentKey, $_key, $_value)
  {
    if ($_key === 'person') {
      // $membershipIdx = 0;
      foreach ($_value as $personCollection) {
        $commonAttr = [];
        $personTempAttrs = [];
        $membership = $personCollection['membership'];
        unset($personCollection['membership']);
        $playerUniqueKey = $personCollection['id'];
        foreach ($personCollection as $playerKey => $playerValue) {
          $personTempAttrs[$this->correctKeyName('player', $playerKey)] = $playerValue;
        }
        $commonAttr = array_merge($commonAttr, $personTempAttrs);
        foreach ($membership as $groupIdx => $membershipCollection) {
          $contestantTempAttrs = [];
          $stats = $membershipCollection['stat'];
          unset($membershipCollection['stat']);

          foreach ($membershipCollection as $ctstInfoKey => $ctstInfoValue) {
            $contestantTempAttrs[$this->correctKeyName('membership', $ctstInfoKey)] = $ctstInfoValue;
          }

          if (!isset($contestantTempAttrs['membershipEndDate'])) {
            $contestantTempAttrs['membershipEndDate'] = null;
          }
          $totalAttrs = array_merge($commonAttr, $contestantTempAttrs);
          // logger($common_attr);
          foreach ($stats as $statIdx => $someStats) {
            $memberUniqueKey = $playerUniqueKey . '_' . $totalAttrs['contestantId'] . '_' . $someStats['tournamentCalendarId'] . $statIdx;
            $someStats['membershipIdx'] = $statIdx;

            $membershipTempAttrs = array_merge($totalAttrs, $someStats);
            $membershipTempAttrs['group_no'] = $groupIdx;
            $this->appendTargetSpecifiedAttrsByIndex(
              'person',
              $memberUniqueKey,
              $membershipTempAttrs,
            );
          }
          if (empty($stats)) {
            $memberUniqueKey = $playerUniqueKey . '_' . $totalAttrs['contestantId'] . '_' . $totalAttrs['membershipStartDate'] . random_int(1000, 1000000) . '0';
            $totalAttrs['group_no'] = $groupIdx;
            $totalAttrs['membershipIdx'] = 0;
            $totalAttrs['is_friendly'] = 'no';
            $this->appendTargetSpecifiedAttrsByIndex(
              'person',
              $memberUniqueKey,
              $totalAttrs,
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

  protected function getAllIds(): array
  {
    $quarter = Carbon::now()->quarter;
    $pcInst = PlateCard::currentSeason();
    return $pcInst->orderBy('id')
      ->limit($quarter === 4 ? 10000 : (int)($pcInst->count() / 4))
      ->offset((int)($pcInst->count() / 4)  * ($quarter - 1))
      ->pluck('player_id')
      ->toArray();
  }

  protected function getDailyIds(): array
  {
    return $this->getAllIds();
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
          case FantasySyncGroupType::ETC:
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

    // optaParser 설정 -->>
    $this->setGlueChildKeys(array_merge($this->getGlueChildKeys(), ['type']));
    $this->setKGsToCustom(['/person']);
    // optaParser 설정 <<--

    // 비동기 동시처리 수로 쪼개기
    $idChunks = array_chunk($ids, self::REQUEST_COUNT_AT_ONCE);
    $totalChucks = count($idChunks);
    foreach ($idChunks as $idx => $idChunk) {
      if (isset($this->param['chunk']) && $idx < $this->param['chunk']) {
        continue;
      }

      if (($idx % 10) === 0) {
        try {
          __loggerEx($this->feedType, 'loop $i : ' . $idx + 1 . ' / ' . $totalChucks);
        } catch (Throwable $e) {
          __telegramNotify(NotifyLevel::WARN, 'playercareer', 'log write error!');
        }
      }

      $responses = $this->optaRequest($idChunk);

      $this->insertOptaDatasToTables(
        $responses,
        null,
        [
          [
            'specifiedInfoMap' => ['person' => PlayerCareer::class],
            'conditions' => ['player_id', 'group_no', 'membership_idx']
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
