<?php

namespace App\Console\Commands\DataControll;

use App\Console\Commands\DataControll\Opta\PE2Parser;
use App\Console\Commands\DataControll\Opta\TM7Parser;
use App\Enums\Opta\Card\OriginGrade;
use App\Enums\Opta\Season\SeasonNameType;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\System\NotifyLevel;
use App\Models\data\Season;
use App\Models\data\SeasonTeam;
use App\Models\data\Squad;
use App\Models\game\PlateCard;
use App\Models\game\RefTransferValue;
use App\Models\meta\RefPlateCPlayer;
use App\Models\meta\RefPlateCQuantile;
use App\Models\meta\RefLeagueTier;
use App\Models\meta\RefPlateGradePrice;

class PlateCardPrice
{
  protected $beforeSeasonIds = [];
  protected $beforeCurrentSeasonIdMap = [];
  protected $leagueTierTable;
  protected $leagueTierContractMatrix;
  protected $leagueTierNonContractMatrix;
  protected $playerCareerWeightMatrix;
  protected $plateCQuantile;
  protected $plateGradePriceTable;
  protected $transferVBonusMatrix = [];

  public function __construct()
  {
    $this->beforeSeasonIds = Season::idsOf([SeasonWhenType::BEFORE], SeasonNameType::ALL, 1);
    $this->beforeCurrentSeasonIdMap = Season::getBeforeCurrentMapCollection()->keyBy('current_id');
    $this->leagueTierTable = RefLeagueTier::selectRaw('league_id, tier, tier_quality')->get()->keyBy('league_id')->toArray();
    $this->leagueTierContractMatrix = config('fantasyplatecardprice.FANTASYPLATECARDPRICE_REFERENCE_TABLE_V0.LEAGUE_TIER_CONTRACT_MATRIX');
    $this->leagueTierNonContractMatrix = config('fantasyplatecardprice.FANTASYPLATECARDPRICE_REFERENCE_TABLE_V0.LEAGUE_TIER_NON_CONTRACT_MATRIX');
    $this->playerCareerWeightMatrix = config('fantasyplatecardprice.FANTASYPLATECARDPRICE_REFERENCE_TABLE_V0.PLAYER_CAREER_POSITION_WEIGHT');
    $this->plateCQuantile = RefPlateCQuantile::withoutChampionsLeague()->get()->keyBy('price_init_season_id')->toArray();
    $this->plateGradePriceTable = RefPlateGradePrice::get()->toArray();
    $transferVBonusMatrix = &$this->transferVBonusMatrix;
    RefTransferValue::orderBy('v_bonus', 'desc')
      ->get()->map(function ($matrix) use (&$transferVBonusMatrix) {
        $transferVBonusMatrix[$matrix['league_id']][] = $matrix->toArray();
      });
  }


  // about plate 가격 
  public function getPlateCPlayerRefQuery($_playerId)
  {
    return RefPlateCPlayer::withoutChampionsLeague()->where('player_id', $_playerId)
      ->whereIn('source_season_id', $this->beforeSeasonIds); // plate 카드 만들어지는 시즌은 active = 'yes'인 시즌, 이전시즌들 중 player가 속한 시즌 알아내기
  }

  public function hasBeforeSeasonPlateC($_platecPlayerRefQuery): bool
  {
    return $_platecPlayerRefQuery->clone()->exists();
  }

  public function applyLeagueTierInContractLeagues($_plateC, $_sourceSeasonId, $_plateCardSeasonId)
  {
    $fromLeagueId = null;
    $toLeagueId = null;
    foreach ($this->beforeCurrentSeasonIdMap as $idx => $map) {
      if ($map['before_id'] === $_sourceSeasonId) {
        $fromLeagueId = $map['league_id'];
      }
      if ($map['current_id'] === $_plateCardSeasonId) {
        $toLeagueId = $map['league_id'];
      }
      if ($fromLeagueId && $toLeagueId) break;
    }

    if (!isset($this->leagueTierTable[$fromLeagueId])) {
      __telegramNotify(NotifyLevel::WARN, 'LeagueTear dose not exist', $fromLeagueId);
    }
    if (!isset($this->leagueTierTable[$toLeagueId])) {
      __telegramNotify(NotifyLevel::WARN, 'LeagueTear dose not exist', $fromLeagueId);
    }
    $fromTier = $this->leagueTierTable[$fromLeagueId]['tier'];
    $toTier = $this->leagueTierTable[$toLeagueId]['tier'];
    $fromTierQuality = $this->leagueTierTable[$fromLeagueId]['tier_quality'];
    $toTierQuality = $this->leagueTierTable[$toLeagueId]['tier_quality'];

    /**
     * tier 이동 점수
     */
    $tierMove = $this->leagueTierContractMatrix[$fromTier][$toTier];
    /**
     * tier quality
     */
    $tierQuality = $fromTierQuality - $toTierQuality;

    return $_plateC + $tierMove + $tierQuality;
  }

  public function getTransferValue($_plateCard, $_playerTransferActive)
  {
    /**
     * A. 동일 리그가 아닌 타 리그에서 이적한 선수 중 이적료가 발생한 선수에게만 v_bounus 적용
     * 이적된 리그 마다 기준이 다름
     * 전시즌 여러 리그 이동이 있을 경우 가장 마지막(현재 플레이트 카드를 생성할 리그로 이동하는)만 적용
     * transfers 피드를 실시간으로 확인한다.
     * 만약 transfers 피드에 이적 정보가 없다면 log에 남긴 후 0 적용
     */
    $vBonus = 0;

    if (
      !empty($_playerTransferActive) &&
      isset($_playerTransferActive['value']) &&
      isset($_playerTransferActive['from_team_id']) &&
      !is_null(
        $aboutFromTeam = SeasonTeam::withWhereHas('season', function ($query) {
          return $query->currentSeasons()->withWhereHas('league', function ($innerQuery) {
            $innerQuery->withoutGlobalScopes();
          });
        })->where('team_id', $_playerTransferActive['from_team_id'])->first()
      )
    ) {
      if (
        $_plateCard['league_id'] !== $aboutFromTeam['season']['league']['id'] && //error
        isset($this->transferVBonusMatrix[$_plateCard['league_id']]) // ref_transfer_values 테이블에 존재하는 리그인지 체크
      ) { // A. 조건
        // v_bonus 적용
        foreach (__sortByKeys($this->transferVBonusMatrix[$_plateCard['league_id']], ['keys' => ['v_bonus'], 'hows' => ['desc']]) as $idx => $matrix) {
          if ($_playerTransferActive['value'] >= $matrix['value']) {
            $vBonus = $matrix['v_bonus'];
            logger('v_bonus 적용 player_id:' . $_plateCard['player_id']);
            break;
          }
        }
      }
    }
    return $vBonus;
  }

  public function addLeagueTierContracted($_platecPlayerRefQuery, $_plateCardSeasonId)
  {
    /**
     * 추가로 league tier 적용
     */
    $platecSum = 0; // 플레이어가 속했던 전시즌들에 plate_c 에 리그티어를 적용 -> 적용한 plate_c들에 각각의 entry_total을 곱한 후 합한 값
    $entrySum = 0; // 플레이어가 속한 모든 전시즌들을 entry_total의 총 합.
    // 여러 리그일 경우 league tier를 각각에 적용해야하므로 map 내에서 리그티어 계산을 적용
    $_platecPlayerRefQuery->get()->map(function ($platecHistory) use (&$platecSum, &$entrySum, $_plateCardSeasonId) {
      $entryTotal = $platecHistory['entry_total'];
      $plateC = $platecHistory['plate_c_auto'];
      $plateC = $this->applyLeagueTierInContractLeagues($plateC, $platecHistory['source_season_id'], $_plateCardSeasonId);
      $platecSum += $plateC * $entryTotal;
      $entrySum += $entryTotal;
    });
    if ($entrySum === 0) {
      logger('entrySum : ' . 'error');
    }
    $plateC = $platecSum / $entrySum;
    return $plateC;
  }

  public function addLeagueTierNonContracted($_plateCard, $_playerCareerHistory): int
  {
    if (
      !empty($_playerCareerHistory) &&
      in_array(($lastCareerLeagueId = $_playerCareerHistory[0]['league_id']), array_keys($this->leagueTierTable)) &&
      $this->checkAppearances($_playerCareerHistory)
    ) {
      /**
       * player career
       * league tier
       */
      $plateD = $this->getPlateD($_playerCareerHistory, $_plateCard->position);
      $gamed = $this->getGameD($_playerCareerHistory);
      $careerTier = $this->leagueTierTable[$lastCareerLeagueId]['tier'];
      $additionalLeaguPoint = $this->leagueTierNonContractMatrix[$careerTier][$_plateCard['position']];
      return $plateD + $gamed + $additionalLeaguPoint;
    }
    return -25;
  }


  public function wasBeforeSeasonPlayer($_playerId): bool
  {
    return Squad::withTrashed()->whereIn('season_id', $this->beforeSeasonIds)->where('player_id', $_playerId)->exists();
  }

  public function getGrade($_plateC, $_plateCardSeasonId)
  {
    foreach (array_reverse(OriginGrade::getValues()) as $gradeSuffix) {
      $tierPoint = $this->plateCQuantile[$_plateCardSeasonId]['quantile' . '_' . $gradeSuffix];
      if ($_plateC <= $tierPoint) {
        return $gradeSuffix;
      }
    }
  }

  public function getPrice($_grade)
  {
    foreach ($this->plateGradePriceTable as $values) {
      if ($values['grade'] === $_grade) {
        return $values['price'];
      }
    }
  }

  public function setPriceGrade(&$_plateCard)
  {
    /**
     * MA2에 기록이 있다 / 이적료가 발생했다
     * - 리그 티어 + 트랜스퍼
     * MA2에 기록이 없다 / 이적료가 발생했다
     * - player_careers 테이블 + 리그 티어+ 비서비스리그 가산점 + 트랜스퍼
     * 공통처리 
     * - 리그 티어, 트랜스퍼
     */
    $playerId = $_plateCard->player_id;
    $plateCardSeasonId = $_plateCard->season_id;

    $plateCOfSourceSeasonQuery = $this->getPlateCPlayerRefQuery($playerId); // plate 카드 만들어지는 시즌은 active = 'yes'인 시즌, 이전시즌들 중 player가 속한 시즌 알아내기
    if ($this->hasBeforeSeasonPlateC($plateCOfSourceSeasonQuery)) { // MA2에 기록이 있다.(entry에는 들었다.)
      // $wasContracted = true;
      $plateCWithLeagueTier = $this->addLeagueTierContracted($plateCOfSourceSeasonQuery, $plateCardSeasonId);
    } else { // 전시즌 MA2에 기록이 없다.
      /** 
       * 처리해야할 것들
       * 1. 전 시즌 squads에는 있지만 entry에는 한번도 들지 않은 경우. nonEntryPlayer (o)
       * 2. 비계약 리그에서 온 경우-playercareer를 보고 plate_d를 만들어야함.
       * - 공통적으로 리그티어 계산
       */

      $plateCWithLeagueTier = null;
      // // -> player_career 처리 안함
      // if ($this->wasBeforeSeasonPlayer($playerId)) { // non entry player
      //   // $wasContracted = true;
      //   $plateCWithLeagueTier = -23;
      // } else { // 비계약 리그에서 온 경우
      //   // $wasContracted = false;
      //   $plateCWithLeagueTier = $this->addLeagueTierNonContracted($_plateCard, $_playerCareerHistory);
      // }
      // //
    }
    // $transferValue = $this->getTransferValue($_plateCard, $_playerTransferActive);

    $_plateCard->plate_c_auto = $plateCWithLeagueTier;

    // 시작가 수동 등급처리로 변경하면서 주석 처리
    // $_plateCard->grade = $this->getGrade($_plateCard->plate_c_auto, $plateCardSeasonId);
    // $_plateCard->price = $this->getPrice($_plateCard->grade);
    // $_plateCard->price_init_season_id = $plateCardSeasonId;
  }

  protected function checkAppearances($_playerCareerHistory)
  {
    $appearances = 0;
    foreach ($_playerCareerHistory as $k => $item) {
      $appearances += $item['appearances'];
    }
    if ($appearances === 0) return false;
    return true;
  }

  protected function getPlateD($_playerCareerHistory, $_position): int
  {
    $total = 0;
    foreach ($_playerCareerHistory as $idx => $career) {
      foreach ($this->playerCareerWeightMatrix[$_position] as $column => $weight) {
        if (isset($career[$column])) {
          $total += $career[$column] * $weight;
        }
      }
    }
    return $total;
  }

  protected function getGameD(array $_playerCareerHistory)
  {
    $total_sub_on = 0;
    $game_started = 0;
    $only_entry = 0;
    foreach ($_playerCareerHistory as $idx => $career) {
      if (!isset($career['substitute_in'])) {
        $subtituteIn = 0;
      } else {
        $subtituteIn = $career['substitute_in'];
      }

      if (!isset($career['appearances'])) {
        $appearances = 0;
      } else {
        $appearances = $career['appearances'];
      }

      if (!isset($career['subs_on_bench'])) {
        $subsOnBench = 0;
      } else {
        $subsOnBench = $career['subs_on_bench'];
      }

      $total_sub_on += $subtituteIn;
      $game_started += $appearances - $subtituteIn;
      $only_entry += $subsOnBench - $subtituteIn;
    }
    $total_g = $game_started + $total_sub_on + $only_entry;
    $start_per = $game_started / $total_g;
    $sub_per = $total_sub_on / $total_g;
    $start_d = $game_started * $start_per;
    $sub_d = ($total_sub_on / 2) * $sub_per;
    $entry_d = -$only_entry * 0.135;
    $game_c = ($start_d + $sub_d) * 0.135;
    $game_d = $game_c + $entry_d;
    return $game_d;
  }

  public function update()
  {
    PlateCard::currentSeason()
      ->hasPowerRankingQuantile() // ref_power_ranking_quantiles에 존재하는 카드만 placte_c_auto 계산
      ->whereNull('plate_c_auto')
      ->get()
      ->map(function ($plateCard) {
        // PlateCard::currentSeason()->isPriceSet(false)->get()->map(function ($plateCard) {
        // $plateCard->season_name = $this->beforeCurrentSeasonIdMap[$plateCard->season_id]['current_season_name'];
        // ==>
        // PE2Paser 위치 - 기록실 대비 DB 저장위해 여기에 위치시킴(DB 저장을 안하면 MA2 기록 없는비서비스 리그이적 선수만 가져오면 됨.)
        // $playerTransferActive = (new TM7Parser($plateCard->toArray()))->startOpta(true);
        // $playerCareerHistory = (new PE2Parser($plateCard->toArray()))->startOpta(true);
        $this->setPriceGrade($plateCard);
        $plateCard->save();
        // <== 
      });
  }
}
