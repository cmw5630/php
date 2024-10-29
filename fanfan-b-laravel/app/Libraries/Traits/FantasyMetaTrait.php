<?php

namespace App\Libraries\Traits;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\YesNo;
use App\Enums\ParserMode;
use App\Enums\System\NotifyLevel;
use App\Models\meta\FantasyMeta;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Str;

trait FantasyMetaTrait
{
  private $parseCompleteFlag = false;
  private $isSyncModeStartFail = false;
  private $currentSyncFeedNick = '';
  private $syncGroup;
  private array $param = [];
  private $parserMode; // 기본 
  private $FantasyMetaTable;


  public function getParserMode()
  {
    return $this->parserMode;
  }

  //-PARAM-
  public function setParams(array $_param)
  {
    $this->parserMode = ParserMode::PARAM;
    $this->param = $_param;
    return $this;
  }

  public function getParams()
  {
    return $this->param;
  }

  public function getParam($_key)
  {
    return $this->getParams()[$_key] ?? null;
  }

  //-SYNC-
  public function setSyncGroup(string $_syncGroup)
  {
    $this->parserMode = ParserMode::SYNC;
    $this->syncGroup = $_syncGroup;
    return $this;
  }

  protected function getSyncGroup(): string
  {
    return $this->syncGroup;
  }

  private function isSyncMode(): bool
  {
    if (
      $this->parserMode === ParserMode::SYNC
      &&
      in_array($this->syncGroup, FantasySyncGroupType::getValues())
    ) {
      return true;
    }
    return false;
  }


  protected function isAnyParserRunningNow($_all = true): bool
  {
    /**
     * $_all = true 동기화 chain의 모든 parsing 또는 updating 중 active가 있는가 검사  
     * $_all = false 현재 parsing 또는 updating이 active인지 검사
     **/
    $result = false;
    FantasyMeta::whereSyncGroup($this->getSyncGroup())
      ->when(
        $_all,
        function (Builder $query) {},
        function (Builder $query) {
          $query->where('sync_feed_nick', $this->getCurrentSyncFeedNick());
        }
      )->where(
        'active',
        YesNo::YES,
      )->get()->map(function ($item) use (&$result) {
        $item->makeVisible('updated_at');
        $updatedAt = $item->updated_at;
        if (Carbon::parse($updatedAt)->diffInDays(now()) > 1) {
          __telegramNotify(NotifyLevel::WARN, 'fantasy meta', sprintf('auto retry sync-group collection(%s)', $this->getCurrentSyncFeedNick()));
          $item['active'] = YesNo::NO;
          $item->save();
          return;
        }
        $result = true;
      })->toArray();

    return $result;
  }

  protected function isTriggerParser(): bool
  {
    return $this->FantasyMetaTable[$this->getCurrentSyncFeedNick()]['is_trigger'] === YesNo::YES;
  }


  protected function getLastSyncOrder(): int
  {
    $lastOrder = 0;
    foreach ($this->FantasyMetaTable as $row) {
      if ($lastOrder < $row['sync_order']) $lastOrder = $row['sync_order'];
    }
    return $lastOrder;
  }

  protected function isLastParser(): bool
  {
    return $this->getLastSyncOrder() === $this->FantasyMetaTable[$this->getCurrentSyncFeedNick()]['sync_order'];
  }

  protected function setCurrentSyncFeedNick(string $_syncFeedNick): void
  {
    $this->currentSyncFeedNick = $_syncFeedNick;
  }

  protected function getCurrentSyncFeedNick(): string
  {
    return $this->currentSyncFeedNick;
  }

  protected function setParserActivate(): void
  {
    $parsingMetaInfo = FantasyMeta::whereSyncGroup($this->getSyncGroup())
      ->where(
        'sync_feed_nick',
        $this->getCurrentSyncFeedNick()
      )->first();
    $parsingMetaInfo->active = YesNo::YES;
    $parsingMetaInfo->save();
  }

  protected function setParserDeActivate(): void
  {
    $parsingMetaInfo = FantasyMeta::whereSyncGroup($this->getSyncGroup())
      ->where(
        'sync_feed_nick',
        $this->getCurrentSyncFeedNick()
      )->first();
    $parsingMetaInfo->active = YesNo::NO;
    $parsingMetaInfo->save();
  }

  protected function captureFantasyMetaTable(): void
  {
    $this->FantasyMetaTable = FantasyMeta::whereSyncGroup($this->getSyncGroup())
      ->get()
      ->keyBy('sync_feed_nick')->toArray();
  }


  protected function isParseCompleted(): bool
  {
    return $this->parseCompleteFlag;
  }

  //-외부사용 function-
  protected function setCompleteFantasyParsing(): bool
  {
    // 파서가 파싱을 무사히 완료
    return $this->parseCompleteFlag = true;
  }

  protected function setUpSyncFantasyParsing(string $_syncFeedNick): bool
  {
    // ->> 동기처리 정보 초기설정
    /**
     * sync group
     * sync_feed_nick
     * capture fantasy_meta table
     */
    /**
     * 검사 로직
     * SYNC 모드인지
     * 이미 해당 syncGroup에 Job이 돌아가고 있거나(돌아가던 중 acitve 상태로 종료된 것이 있는지)
     * parsing_step이 유효한지 
     */
    if (!$this->isSyncMode()) {
      logger('검사용 로그 - setParam 메소드를 호출했거나, setSyncGroup 메소드를 호출하지 않았습니다.');
      return false;
    }

    $this->setCurrentSyncFeedNick($_syncFeedNick); // 2
    $this->captureFantasyMetaTable(); // 3
    // <<- 동기처리 정보 초기설정

    if ($this->isAnyParserRunningNow()) {
      // if ($this->isTriggerParser()) {
      // }
      __telegramNotify(NotifyLevel::CRITICAL, 'sync-group', 'can not start syncgroup. check fantasy metas table.');
      $this->isSyncModeStartFail = true;
      return false;
    }


    if (
      $this->syncGroup === FantasySyncGroupType::DAILY || $this->syncGroup === FantasySyncGroupType::ALL ||
      $this->syncGroup === FantasySyncGroupType::ELASTIC || $this->syncGroup === FantasySyncGroupType::ETC ||
      $this->syncGroup === FantasySyncGroupType::CONDITIONALLY
    ) {
      if ($this->isTriggerParser()) {
        // trigger는 그냥 시작
        __telegramNotify(NotifyLevel::INFO, 'sync-group', sprintf('start syncgroup(%s) - %s', $this->syncGroup, $this->getCurrentSyncFeedNick()));
        $this->setParserActivate();
        return true;
      }
      __telegramNotify(NotifyLevel::INFO, 'sync-group', 'start' . $this->getCurrentSyncFeedNick());
    }

    // (trigger 파싱이 아닌 경우)
    $fantasyMetaStatus = FantasyMeta::whereSyncGroup($this->getSyncGroup())->get();
    $fantasyMetaStatusMap = $fantasyMetaStatus->keyBy('sync_feed_nick')->toArray();

    /**
     * @var Collection $parsingMeta
     */
    $parsingMeta = null;
    /**
     * @var Collection $preParsingMeta
     */
    $preParsingMeta = null; // 없으면 null

    $currentOrder = $fantasyMetaStatusMap[$this->getCurrentSyncFeedNick()]['sync_order'];

    $preStepOrder = 0;
    $fantasyMetaStatus
      ->each(function ($item) use ($currentOrder, &$parsingMeta, &$preParsingMeta, &$preStepOrder) {
        if ($item['sync_order'] === $currentOrder) {
          $parsingMeta = $item;
        } else if (($item['sync_order'] < $currentOrder) && $preStepOrder < $item['sync_order']) {
          $preStepOrder = $item['sync_order'];
          $preParsingMeta = $item;
        }
      });

    $preConditionStep = $preParsingMeta->parsing_step;
    $currentParsingStep = $parsingMeta->parsing_step;

    if ($preConditionStep <= $currentParsingStep) {
      logger('parsing_step이 맞지 않습니다.'); // 조건이 되는 파싱이 이루어지지 않음 - 로직 오류
      $this->isSyncModeStartFail = true;
      return false;
    }
    $this->setParserActivate();
    return true;
  }

  protected function wrapUpFantasyParsing(): void
  {
    if ($this->isSyncModeStartFail || !$this->isSyncMode()) {
      // ex) 이미 해당 sync_group이 running 중으로 현재 작업 시작 실패
      // ex) SINC 모드가 아닐 때
      // 마무리 작업 생략
      /**
       * 이 스코프에 들어온 경우 active값을 YES로 변경한 적이 없으므로 
       * active 값을 NO로 절대로 변경하지 않는다.
       */
      return;
    }

    // 파싱이 정상완료되었을 때 해야할 마무리 작업

    $fantasyMetaStatus = FantasyMeta::whereSyncGroup($this->getSyncGroup())->get();

    $fantasyMetaStatusMap = $fantasyMetaStatus->keyBy('sync_feed_nick')->toArray();

    $currentOrder = $fantasyMetaStatusMap[$this->getCurrentSyncFeedNick()]['sync_order'];

    /**
     * @var Collection $parsingMeta
     */
    $parsingMeta = null;
    /**
     * @var Collection $preParsingMeta
     */
    $preParsingMeta = null; // 없으면 null

    $fantasyMetaStatus
      ->each(function ($item) use ($currentOrder, &$parsingMeta, &$preParsingMeta) {
        if ($item['sync_order'] === $currentOrder) {
          $parsingMeta = $item;
        } else if ($item['sync_order'] === $currentOrder - 1) {
          $preParsingMeta = $item;
        }
      });

    if ($this->isParseCompleted()) {
      $parsingMeta->active = YesNo::NO;
      // if (!$this->isTriggerParser()) { // 선행 파싱 조건이 필요한 경우 === trigger 파서가 아닌 경우)
      if ($preParsingMeta !== null) { // 선행 파싱 조건이 필요한 경우 === trigger 파서가 아닌 경우)
        $preConditionStep = $preParsingMeta->toArray()['parsing_step'];
        $currentStep = $parsingMeta->toArray()['parsing_step'];

        // 방어적 programing 재검사 -->
        if ($preConditionStep <= $currentStep) {
          logger('parsing_step이 맞지 않습니다._'); // 조건이 되는 파싱이 이루어지지 않음 - 로직 오류
          // telegram 알림
          $parsingMeta->active = YesNo::NO;
          $parsingMeta->save();
          return;
        }
        // 방어적 programing 재검사 <<--

        $parsingMeta->parsing_step = $preConditionStep; // step = 선행파서의 step값으로 동기화

      } else { // 선행파서가 없는 trigger 파서의 경우
        $parsingMeta->parsing_step += 1;
      }
      // 모든 파서
      $parsingMeta->parsing_count += 1;
      $parsingMeta->save();
      if ($this->isLastParser() && ($this->syncGroup === FantasySyncGroupType::DAILY || $this->syncGroup === FantasySyncGroupType::ALL)) {
        __telegramNotify(NotifyLevel::INFO, 'sync-group', sprintf('parsing syncgroup(%s) has completed', $this->syncGroup));
      }
      return;
    }
    $parsingMeta->save();
    return;
  }
}
