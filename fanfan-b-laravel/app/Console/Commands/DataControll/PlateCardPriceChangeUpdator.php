<?php

namespace App\Console\Commands\DataControll;

use App\Console\Commands\DataControll\PlateCardBase;
use App\Enums\Opta\Card\OriginGrade;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\System\NotifyLevel;
use App\Libraries\Classes\Exception;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\game\PlateCard;
use App\Models\log\PlateCardPriceChangeLog;
use App\Models\meta\RefPlateGradePrice;
use App\Models\meta\RefPowerRankingQuantile;
use App\Models\meta\RefPriceGradeTransformMap;
use App\Models\meta\RefTeamTierBonus;
use Schema;

class PlateCardPriceChangeUpdator extends PlateCardBase
{
  use FantasyMetaTrait;

  protected $feedNick;

  protected $gradeChangeTickMap;

  protected $refPowerRankingQuantileMap;

  protected $refPriceGradeTransformMap;

  protected $refTeamTierBonusMap;

  protected $playerId;

  protected $playerSeasonId;

  protected $plateGradPriceTable;

  protected array $noTeamTierBounusTeamIds = [];

  public function __construct($_playerId = null)
  {
    $this->playerId = $_playerId;
    $this->playerSeasonId = PlateCard::wherePlayerId($_playerId)->value('season_id');


    parent::__construct();
    $this->feedNick = 'PCPCU';
    $this->gradeChangeTickMap = [
      OriginGrade::SS => 4,
      OriginGrade::S => 3,
      OriginGrade::A => 3,
      OriginGrade::B => 3,
      OriginGrade::C => 3,
      OriginGrade::D => 2,
    ];
    $this->plateGradPriceTable = RefPlateGradePrice::get()->toArray();
    $this->refPriceGradeTransformMap = RefPriceGradeTransformMap::get()->keyBy('id')->toArray();
    $this->refTeamTierBonusMap = RefTeamTierBonus::get()->groupBy(['season_id', 'team_id'])->toArray();
    $this->refPowerRankingQuantileMap = RefPowerRankingQuantile::get()->groupBy('league_id')->toArray();
  }

  protected function getPlateCardWithPriceChangeLogs($_playerId, $_currentSeasonId)
  {
    // $plateCardAttrs = PlateCard::currentSeason()->with('plateCardPriceChangeLog')->where('player_id', $playerId)->first()->toArray();
    // -> 임시 코드(currentSeaons() 대신 whereSeasonId($currentSeasonId))
    $plateCardAttrs = PlateCard::whereSeasonId($_currentSeasonId)
      ->where('player_id', $_playerId)
      // ->with('plateCardPriceChangeLog')
      ->first()
      ->toArray();

    return  $plateCardAttrs;
  }

  protected function recordPriceChangeLog(array $_row)
  {
    PlateCardPriceChangeLog::updateOrCreateEx(
      [
        'player_id' => $_row['player_id'],
        'season_id' => $_row['season_id'],
        'schedule_id' => $_row['schedule_id'],
      ],
      $_row,
      false,
      true,
    );
  }

  protected function makeNewPriceGrade(): string
  {
    return OriginGrade::SS;
  }

  protected function makeNormalization(float $_powerRanking, float $_mean, float $_stdev): float
  {
    return ($_powerRanking - $_mean) / $_stdev;
  }

  protected function initLocalValiable(&$_currentPriceGrade, &$_remainCount, &$_powerRankingTempSum, $_newPriceGrade = null)
  {

    $_currentPriceGrade = $_newPriceGrade;
    $_remainCount = $this->gradeChangeTickMap[$_currentPriceGrade];
    $_powerRankingTempSum = 0;
  }

  protected function makePriceChangeProcess(PlateCard $_plateCardInst)
  {
    $currentSeasonId = $_plateCardInst['season_id'];
    $playerId = $_plateCardInst['player_id'];
    $seasonInst = Season::with('leagueWithoutGS')->whereId($currentSeasonId)->first();
    $refPowerRankingLeagueQuantileMap = __sortByKeys($this->refPowerRankingQuantileMap[$seasonInst->leagueWithoutGS->id], ['keys' => ['map_identification_id'], 'hows' => ['asc']]);
    $mean = $refPowerRankingLeagueQuantileMap[0]['mean'];
    $stdev = $refPowerRankingLeagueQuantileMap[0]['stdev'];

    $logPartialRow = [
      'plate_card_id' => $_plateCardInst->id,
      'player_id' => $_plateCardInst->player_id,
      'player_name' => $_plateCardInst->match_name,
      'league_id' => $seasonInst->leagueWithoutGS->id,
      'league_name' => $seasonInst->leagueWithoutGS->name,
      'season_id' => $seasonInst->id,
      'season_name' => $seasonInst->name,
    ];
    $priceChangeLogs = $_plateCardInst->plateCardPriceChangeLog;
    $priceChangedLastScheduleId = $this->getPriceChangeLastScheduleId($priceChangeLogs->toArray());

    $powerRankingTempSum = 0;

    if ($priceChangedLastScheduleId === null) { // 최초부터 다시 기록
      $currentPriceGrade = $_plateCardInst['init_grade']; // 최초 기록 init_grade 사용!
      $this->recordPriceChangeLog(
        array_merge(
          $logPartialRow,
          [
            'opta_week' => null,
            'price_grade' => $currentPriceGrade,
            'schedule_id' => null,
            'is_change_spot' => true,
          ]
        )
      );
    } else {
      $currentPriceGrade = PlateCardPriceChangeLog::where([
        ['schedule_id', $priceChangedLastScheduleId],
        ['player_id', $playerId],
      ])->first('price_grade')->toArray()['price_grade'];
    }

    $localRemainCount = $this->gradeChangeTickMap[$currentPriceGrade];

    Schedule::where([
      ['season_id', $currentSeasonId],
      ['status', ScheduleStatus::PLAYED],
    ])->has('oneOptaPlayerDailyStat')
      ->withWhereHas('oneOptaPlayerDailyStat', function ($query) use ($playerId) {
        return $query->wherePlayerId($playerId); //->gameParticipantPlayer();
      })->when($priceChangedLastScheduleId, function ($query) use ($priceChangedLastScheduleId) {
        $startedAt = PlateCardPriceChangeLog::where('schedule_id', $priceChangedLastScheduleId)->first('started_at')->toArray()['started_at'];
        return $query->where('started_at', '>', $startedAt); // price 마지막 변동 지점 로그 기록부터 이어서
      })->oldest('started_at')
      ->get()->map(function ($playerScheduleSet) use (
        &$currentPriceGrade,
        &$localRemainCount,
        &$powerRankingTempSum,
        $logPartialRow,
        $refPowerRankingLeagueQuantileMap,
        $mean,
        $stdev,
      ) {
        // logger('round:' . $playerScheduleSet['round'] . ',' . 'schedule_id:' . $playerScheduleSet['schedule_id'] . ',' . 'started_at' . $playerScheduleSet['started_at']);
        $powerRankingTempSum += $playerScheduleSet->oneOptaPlayerDailyStat['power_ranking'];
        $localRemainCount--;

        // (DEBUG) -->>
        $teamId = $playerScheduleSet->oneOptaPlayerDailyStat['team_id'];
        $seasonId = $playerScheduleSet->oneOptaPlayerDailyStat['season_id'];

        if (!isset($this->refTeamTierBonusMap[$seasonId][$teamId][0])) {
          $this->noTeamTierBounusTeamIds[] = $teamId;
          return;
        }
        $teamTierBonusMap = $this->refTeamTierBonusMap[$seasonId][$teamId][0];
        $teamBonus = $teamTierBonusMap['normalized_bonus'];
        $tempColumns = [
          'schedule_id' => $playerScheduleSet->oneOptaPlayerDailyStat['schedule_id'],
          'power_ranking' => $playerScheduleSet->oneOptaPlayerDailyStat['power_ranking'],
          'normalized_personal' => $this->makeNormalization($playerScheduleSet->oneOptaPlayerDailyStat['power_ranking'], $mean, $stdev),
          'team_bonus' => $teamBonus,
          'mins_played' => $playerScheduleSet->oneOptaPlayerDailyStat['mins_played'],
        ];
        $logPartialRow = array_merge($logPartialRow, $tempColumns);
        // <<--

        // logger($localRemainCount);
        $newPriceGrade = $currentPriceGrade; // 초기화(change spot이 아닐 때도 업데이트하기 위해)

        if ($localRemainCount === 0) { // change spot
          $powerRankingAvg = $powerRankingTempSum / $this->gradeChangeTickMap[$currentPriceGrade];
          $seasonId = $playerScheduleSet->oneOptaPlayerDailyStat['season_id'];
          $teamId = $playerScheduleSet->oneOptaPlayerDailyStat['team_id'];
          $teamTierBonusMap = $this->refTeamTierBonusMap[$seasonId][$teamId][0];
          $teamBonus = $teamTierBonusMap['normalized_bonus'];
          $powerRankingNormalizedV = $this->makeNormalization($powerRankingAvg, $mean, $stdev) + $teamBonus;

          foreach ($refPowerRankingLeagueQuantileMap as $idx => $cutPointRow) {
            // 갱신작업
            if ($powerRankingNormalizedV > $cutPointRow['normalized_value']) {
              $newPriceGrade = $this->refPriceGradeTransformMap[$cutPointRow['map_identification_id']][$currentPriceGrade];
              if ($newPriceGrade === null) {
                $newPriceGrade = $currentPriceGrade;
              }
              // logger('insert');
              $this->recordPriceChangeLog(
                array_merge(
                  $logPartialRow,
                  [
                    'started_at' => $playerScheduleSet['started_at'],
                    'opta_week' => $playerScheduleSet['round'],
                    'current_normalized_v' => $powerRankingNormalizedV,
                    'power_ranking_avg' => $powerRankingAvg,
                    'price_grade' => $newPriceGrade,
                    'is_change_spot' => true,
                  ]
                )
              );
              $currentPriceGrade = $newPriceGrade;
              $powerRankingTempSum = 0;
              break;
            }
          }
          $localRemainCount = $this->gradeChangeTickMap[$currentPriceGrade];
        } else { // DEBUG
          $this->recordPriceChangeLog(
            array_merge(
              $logPartialRow,
              [
                'started_at' => $playerScheduleSet['started_at'],
                'opta_week' => $playerScheduleSet['round'],
                'current_normalized_v' => null,
                'price_grade' => $currentPriceGrade,
              ]
            )
          );
        }
        // // -> plate card 등급 업데이트
        // $_plateCardInst->grade = $newPriceGrade;
        // logger($newPriceGrade);
        // $_plateCardInst->save();
        // // <-
      });
    $this->updateCardPriceGrade($_plateCardInst);
  }

  public function getPrice($_grade)
  {
    foreach ($this->plateGradPriceTable as $values) {
      if ($values['grade'] === $_grade) {
        return $values['price'];
      }
    }
  }

  private function updateCardPriceGrade($_plateCardInst)
  {
    $currentSeasonId = $_plateCardInst['season_id'];
    $playerId = $_plateCardInst['player_id'];
    $latestPriceGrade = PlateCardPriceChangeLog::where(
      [
        ['player_id', $playerId],
        ['season_id', $currentSeasonId],
      ]
    )->latest('started_at')->first()->toArray()['price_grade'];
    $_plateCardInst->grade = $latestPriceGrade;
    $_plateCardInst->price = $this->getPrice($latestPriceGrade);
    $_plateCardInst->save();
  }


  protected function getPriceChangeLastScheduleId(array $_priceChangeLogs): ?string
  {
    $_priceChangedScheduleId = null;
    if (!empty($_priceChangeLogs)) { // price 변동 로그 기록 이 있다면 기록 마지막 시점으로부터 price 변동 프로세스를 완성해야 함.
      $latestPriceChangeLog = __sortByKeys($_priceChangeLogs, ['keys' => ['opta_week'], 'hows' => ['desc']])[0];
      $_priceChangedScheduleId = $latestPriceChangeLog['schedule_id'];
    }
    return $_priceChangedScheduleId;
  }


  public function update()
  {
    // 현재 서비스 리그 hide 설정 상관없이(항상 카드 판매 준비상태 유지를 위해)
    PlateCard::isPriceSet() // 현재시즌, price 설정 완료된 카드
      ->hasPowerRankingQuantile() // ref_power_ranking_quantiles에 존재하는 것만 price 변동 로직을 탈 수 있음.
      ->when($this->playerId, function ($query) {
        $query->where('player_id', $this->playerId);
      })
      ->with('plateCardPriceChangeLog', function ($query) { // 마지막 변경지점(is_change_spot) 로그들
        $query->where([
          ['is_change_spot', true],
          ['season_id', $this->playerSeasonId],
        ])->latest('started_at')->limit(1);
      })
      ->get()->map(function ($playerInst) {
        try {
          Schema::connection('log')->disableForeignKeyConstraints();
          $this->makePriceChangeProcess($playerInst);
        } catch (\Exception $e) {
          logger($e);
          logger(sprintf('price change process. (player_id: %s)', $playerInst->player_id));
          throw new Exception($e->getMessage());
        } finally {
          Schema::connection('log')->enableForeignKeyConstraints();
        }
      });
  }

  public function __destruct()
  {
    if (!empty($this->noTeamTierBounusTeamIds)) {
      __telegramNotify(
        NotifyLevel::WARN,
        'No Team Tear Bounus',
        array_values(array_unique($this->noTeamTierBounusTeamIds))
      );
    }
  }
}
