<?php

namespace App\Libraries\Traits;

use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\FantasyCalculator\FantasyDraftCategoryType;
use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Card\DraftCardStatus;
use App\Enums\Opta\Card\PlateCardStatus;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\PointRefType;
use App\Enums\PurchaseOrderStatus;
use App\Libraries\Classes\Alarm;
use App\Libraries\Classes\FantasyCalculator;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\Schedule;
use App\Models\data\Team;
use App\Models\game\Auction;
use App\Models\game\DraftComplete;
use App\Models\game\DraftSelection;
use App\Models\game\GamePossibleSchedule;
use App\Models\game\PlateCard;
use App\Models\game\PlayerDailyStat;
use App\Models\log\DraftLog;
use App\Models\order\DraftOrder;
use App\Models\user\UserPlateCard;
use App\Services\Market\MarketService;
use Illuminate\Support\Facades\Redis;

trait DraftTrait
{
  use LogTrait, SimulationTrait;

  protected function cancelDraftAllByScheduleId($_scheduleId, $_realScheduleStatus = null)
  {
    // 스케쥴 전체 취소
    $scheduleArr = Schedule::where('id', $_scheduleId)->first()->toArray();
    $scheduleId = $scheduleArr['id'];
    $seasonId = $scheduleArr['season_id'];
    $startedAt = $scheduleArr['started_at'];
    if ($_realScheduleStatus === null) {
      $_realScheduleStatus = $scheduleArr['status'];
    }

    DraftSelection::where('schedule_id', $scheduleId)
      ->get()
      ->map(function ($_dSelections) use ($seasonId, $startedAt, $_realScheduleStatus) {
        // $this->cancelDraft($_dSelections, $_datas);
        $this->cancelDraftOnePlayer($_dSelections, $seasonId, $startedAt, $_realScheduleStatus);
      });
    // game_possible_schedule에 완료 기록
    $gs = GamePossibleSchedule::withTrashed()->where('schedule_id', $scheduleId)->first();
    $gs->wrapup_draft_cancelled = true;
    $gs->save();
  }

  private function cancelDraftOnePlayer($_dSelections, $_seasonId, $_startedAt, $_scheduleStatus)
  {
    /**
     * 드래프트 취소처리
     * - draft_orders 상태 변경
     * - user_plate_cards - status=> plate
     * - draft-log 기록
     * - draft_selections 제거
     * - user_point 환불 , user_point_logs 기록
     */

    $userId = $_dSelections->user_id;
    $playerId  = $_dSelections->player_id;
    $scheduleId = $_dSelections->schedule_id;
    // $userId = $_selections->user_id;
    $userPlateCardId = $_dSelections->user_plate_card_id;

    $order = DraftOrder::where('user_plate_card_id', $userPlateCardId)->first();
    $order->order_status = PurchaseOrderStatus::REFUND;
    $order->save();

    $teamId = PlateCard::withTrashed()->where('player_id', $playerId)->value('team_id');
    $cardGrade = CardGrade::NONE;
    $draftStatus = DraftCardStatus::CANCEL;
    $draftLevel = null;
    $card = UserPlateCard::where('id', $userPlateCardId)->first();
    $card->status = PlateCardStatus::PLATE;
    $card->save();

    $this->plusUserPointWithLog(
      $order->upgrade_point,
      $order->upgrade_point_type,
      PointRefType::ETC,
      'draft refunded',
      $order->user_id,
    );

    // DraftSelection::find($selections->id)->forceDelete();
    DraftSelection::whereUserPlateCardId($userPlateCardId)->first()->forceDelete(); // user_plate_cardId is unique

    $this->recordLog(
      DraftLog::class,
      [
        'user_id' => $card->user_id,
        'user_plate_card_id' => $userPlateCardId,
        'draft_season_id' => $_seasonId,
        'draft_team_id' => $teamId,
        'schedule_id' => $scheduleId,
        'origin_started_at' => $_startedAt,
        'schedule_status' => $_scheduleStatus,
        'card_grade' => $cardGrade,
        'status' => $draftStatus,
        'draft_level' => $draftLevel,
      ]
    );
    logger('draft 취소');
  }


  protected function finishDraft($_datas)
  {
    // 드래프트 완료 작업
    $scheduleId = $_datas['commonRowOrigin']['schedule_id'];
    DraftSelection::whereHas('userPlateCard', function ($_query) {
      $_query->where('status', PlateCardStatus::UPGRADING);
    })->where('schedule_id', $scheduleId)
      ->get()
      ->map(function ($dSelection) use ($_datas) {
        $playerId  = $dSelection->player_id;
        $scheduleId = $dSelection->schedule_id;
        $optaStats = (OptaPlayerDailyStat::gameParticipantPlayer()
          ->where([
            ['schedule_id', $scheduleId],
            ['player_id', $playerId],
          ])->first());
        if ($optaStats) {
          $this->completeDraft($dSelection, $_datas, $optaStats);
        } else { // 경기 참가하지 않은 선수에 대한 드래프트 처리(취소)
          $sesonId = $_datas['commonRowOrigin']['season_id'];
          $startedAt = $_datas['commonRowOrigin']['started_at'];
          $scheduleStatus = $_datas['commonRowOrigin']['status'];
          $this->cancelDraftOnePlayer($dSelection, $sesonId, $startedAt, $scheduleStatus);
        }
      });
    // game_possible_schedule에 완료 기록
    $gs = GamePossibleSchedule::where('schedule_id', $scheduleId)->first();
    $gs->wrapup_draft_completed = true;
    $gs->save();
  }


  protected function getDailyStatsFromDatas($_datas, $_player_id): array
  {
    foreach ($_datas['specifiedAttrs']['playerFp'] as $uniqueKey => $playerSet) {
      if ($playerSet['player_id'] === $_player_id) {
        return $playerSet;
      }
    }
  }


  protected function completeDraft($_dSelections, $_datas, $_optaStats)
  {
    /**
     * @var FantasyCalculator $draftCalculator
     */
    $draftCalculator = app(FantasyCalculatorType::FANTASY_DRAFT, [0]);

    $playerId  = $_dSelections->player_id;
    $scheduleId = $_dSelections->schedule_id;
    $seasonId = $_datas['commonRowOrigin']['season_id'];
    $userId = $_dSelections->user_id;
    $userPlateCardId = $_dSelections->user_plate_card_id;
    logger($playerId);
    logger($scheduleId);
    logger($userId);
    logger($userPlateCardId);
    // $_optaStats = (OptaPlayerDailyStat::gameParticipantPlayer()
    //   ->where([
    //     ['schedule_id', $scheduleId],
    //     ['player_id', $playerId],
    //   ])->first());
    $fpStats = $this->getDailyStatsFromDatas($_datas, $playerId);
    $cardGrade = $fpStats['card_grade'];
    $position = $fpStats['summary_position'];
    $teamId = $fpStats['team_id'];
    $draftStatus = DraftCardStatus::COMPLETE;
    $draftCompleteRowWithCate = $draftCalculator->calculate(
      [
        'opta_stats' => $_optaStats->toArray(),
        'selections' => $_dSelections->toArray(),
      ],
      true
    );

    $draftCompleteRow = [];
    $draftSpecialSkill = $draftCompleteRowWithCate['meta']['user_special_skills'];

    foreach ($draftCompleteRowWithCate['success'] as $cate => $attSet) {
      $draftCompleteRow = array_merge($draftCompleteRow, $attSet);
    }

    $draftLevel = array_sum($draftCompleteRow);

    $draftTeamNames = null;
    if (($TeamAttr = Team::whereId($teamId)->get()->first()) !== null) {
      $draftTeamNames = [
        'team_name' => $TeamAttr['name'],
        'team_short_name' => $TeamAttr['short_name'],
        'team_club_name' => $TeamAttr['official_name'],
        'team_code' => $TeamAttr['code'],
      ];
    }

    /**
     * 드래프트 완료 처리
     * - user_plate_cards - card_grade, position, status=> complete, draft_season_id, draft_team_id, draft_complete_at 기록
     * - draft-log 기록
     */
    DraftComplete::create(
      array_merge([
        'user_id' => $userId,
        'user_plate_card_id' => $userPlateCardId,
        'summary_position' => $_optaStats['summary_position'],
      ], $draftCompleteRow)
    );

    $card = UserPlateCard::with('plateCard:id,grade')->where('id', $userPlateCardId)->first();

    $card->is_mom = $_optaStats['is_mom'];
    $card->card_grade = $cardGrade;
    $card->position = $position;
    $card->status = PlateCardStatus::COMPLETE;
    $card->draft_season_id = $seasonId;
    $card->draft_season_name = $_datas['commonRowOrigin']['season_name'];
    $card->draft_team_id = $teamId;
    $card->draft_team_names = $draftTeamNames;
    $card->draft_schedule_round = $_datas['commonRowOrigin']['round'];
    $card->draft_price_grade = $card->plateCard->grade;
    $card->draft_shirt_number = $card->plateCard->shirt_number;
    $card->draft_level = $draftLevel;
    if ($position === PlayerPosition::GOALKEEPER) {
      $card->goalkeeping_level = array_sum($draftCompleteRowWithCate['success'][FantasyDraftCategoryType::GOALKEEPING] ?? [0]);
    } else {
      $card->attacking_level = array_sum($draftCompleteRowWithCate['success'][FantasyDraftCategoryType::ATTACKING] ?? [0]);
    }
    $card->passing_level = array_sum($draftCompleteRowWithCate['success'][FantasyDraftCategoryType::PASSING] ?? [0]);
    $card->defensive_level = array_sum($draftCompleteRowWithCate['success'][FantasyDraftCategoryType::DEFENSIVE] ?? [0]);
    $card->duel_level = array_sum($draftCompleteRowWithCate['success'][FantasyDraftCategoryType::DUEL] ?? [0]);
    $card->special_skills = $draftSpecialSkill;
    $card->draft_completed_at = now();

    //==>> ingame point 계산
    /**
     * @var FantasyCalculator $fipCalculator
     */
    $fipCalculator = app(FantasyCalculatorType::FANTASY_INGAME_POINT, [0]);

    $fpStats = (PlayerDailyStat::where([
      ['schedule_id', $scheduleId],
      ['player_id', $playerId],
    ])->first());

    $inGameMeta = $fipCalculator->calculate([
      'user_card_attrs' => $card->toArray(),
      'fantasy_point' => $_optaStats['fantasy_point'],
      'is_mom' => $_optaStats['is_mom'],
      'schedule_id' => $scheduleId,
      'origin_stats' => $_optaStats->toArray(),
      'fp_stats' => $fpStats->toArray(),
    ], true);

    $ingamePoint = $inGameMeta['ingame_point'];
    $levelWeight = $inGameMeta['level_weight'];

    $card->ingame_fantasy_point = $ingamePoint;
    $card->level_weight = $levelWeight;
    //<<== ingame point 계산

    // 트랜스퍼 최소 거래 가격 구하기
    [$minPrice,] = (new MarketService)->getMarketDataset($card);
    $card->min_price = $minPrice;
    $card->save();


    $this->recordLog(
      DraftLog::class,
      [
        'user_id' => $userId,
        'user_plate_card_id' => $userPlateCardId,
        'draft_season_id' => $seasonId,
        'draft_team_id' => $teamId,
        'schedule_id' => $scheduleId,
        'origin_started_at' => $_datas['commonRowOrigin']['started_at'],
        'schedule_status' => $_datas['commonRowOrigin']['status'],
        'card_grade' => $cardGrade,
        'status' => $draftStatus,
        'draft_level' => $draftLevel,
      ]
    );

    // simulation_overall 테이블 저장
    // $this->setIngameCardOverall($userPlateCardId);
    /**
     * @var FantasyCalculator $foCalculator
     */
    $foCalculator = app(FantasyCalculatorType::FANTASY_OVERALL, [0]);
    $foCalculator->calculate($userPlateCardId);

    logger('draft 완료');

    /**
     * @var Alarm $alarm
     */

    $redisKey = 'alarm_draft_complete_' . $userId . '_' . $scheduleId;
    if (!Redis::exists($redisKey)) {
      $socketData = [
        'template_id' => 'draft-card-upgraded',
        'target_user_id' => $userId,
      ];
      $alarm = app('alarm', ['id' => $socketData['template_id']]);
      $alarm->send([$socketData['target_user_id']]);
      Redis::set($redisKey, 1, 'EX', 3600);
    }
  }




  private function initDraftStatToZero($_userPlateCardId)
  {
    /**
     * @var FantasyCalculator $draftCalculator
     */
    $draftCalculator = app(FantasyCalculatorType::FANTASY_DRAFT, [0]);
    $draftCompleteZeroSet = [];
    foreach ($draftCalculator->getCombRepresentationNames() as $colName) {
      $draftCompleteZeroSet[$colName] = 0;
    };
    $userCardSkillZeroSet = ['is_mom' => 0];
    foreach (array_keys($draftCalculator->getCombsWithCategoryTable()) as $cateName) {
      $userCardSkillZeroSet[$cateName . '_level'] = null;
    }

    DraftComplete::updateOrCreateEx(
      [
        'user_plate_card_id' => $_userPlateCardId
      ],
      $draftCompleteZeroSet
    );

    UserPlateCard::updateOrCreateEx(
      [
        'id' => $_userPlateCardId
      ],
      $userCardSkillZeroSet
    );
  }

  protected function updateAuctionTable(array $userPlateAttr)
  {
    $colsSet = [
      'draft_level',
      'attacking_level',
      'goalkeeping_level',
      'passing_level',
      'defensive_level',
      'duel_level',
    ];

    $updateSet = [];
    foreach ($colsSet as $colName) {
      $updateSet[$colName] = $userPlateAttr[$colName];
    }

    $targetAc = Auction::where('user_plate_card_id', $userPlateAttr['id'])->first();
    if ($targetAc) {
      $targetAc->update($updateSet);
    }
  }

  // protected function forceModifyDraftCompleteCard($_dSelections, $_datas, $_optaStats)
  protected function forceModifyDraftCompletedCard($_userPlateCardId)
  {
    /**
     * @var FantasyCalculator $draftCalculator
     */
    $draftCalculator = app(FantasyCalculatorType::FANTASY_DRAFT, [0]);

    $_dSelection = DraftSelection::where('user_plate_card_id', $_userPlateCardId)->first();

    $playerId  = $_dSelection->player_id;
    $scheduleId = $_dSelection->schedule_id;
    // $seasonId = $_datas['commonRowOrigin']['season_id'];
    $userId = $_dSelection->user_id;
    $userPlateCardId = $_dSelection->user_plate_card_id;
    logger($playerId);
    logger($scheduleId);
    logger($userId);
    logger($userPlateCardId);
    $optaStats = (OptaPlayerDailyStat::gameParticipantPlayer()
      ->where([
        ['schedule_id', $scheduleId],
        ['player_id', $playerId],
      ])->first());

    if ($optaStats === null) return;
    $this->initDraftStatToZero($_userPlateCardId);

    $fpStats = (PlayerDailyStat::where([
      ['schedule_id', $scheduleId],
      ['player_id', $playerId],
    ])->first());

    $cardGrade = $fpStats['card_grade'];
    $position = $optaStats['summary_position'];
    $teamId = $optaStats['team_id'];
    $draftStatus = DraftCardStatus::COMPLETE;
    $draftCompleteRowWithCate = $draftCalculator->calculate(
      [
        'opta_stats' => $optaStats->toArray(),
        'selections' => $_dSelection->toArray(),
      ],
      true,
    );

    $draftCompleteRow = [];
    $draftSpecialSkill = $draftCompleteRowWithCate['meta']['user_special_skills'];

    foreach ($draftCompleteRowWithCate['success'] as $cate => $attSet) {
      $draftCompleteRow = array_merge($draftCompleteRow, $attSet);
    }

    $afterDLevel = $draftLevel = array_sum($draftCompleteRow);

    $draftTeamNames = null;
    if (($TeamAttr = Team::whereId($teamId)->get()->first()) !== null) {
      $draftTeamNames = [
        'team_name' => $TeamAttr['name'],
        'team_short_name' => $TeamAttr['short_name'],
        'team_club_name' => $TeamAttr['official_name'],
        'team_code' => $TeamAttr['code'],
      ];
    }

    /**
     * 드래프트 완료 처리
     * - user_plate_cards - card_grade, position, status=> complete, draft_season_id, draft_team_id, draft_complete_at 기록
     * - draft-log 기록
     */
    DraftComplete::updateOrCreateEx(
      [
        'user_id' => $userId,
        'user_plate_card_id' => $userPlateCardId,
      ],
      array_merge([
        'user_id' => $userId,
        'user_plate_card_id' => $userPlateCardId,
        'summary_position' => $optaStats['summary_position'],
      ], $draftCompleteRow)
    );


    $card = UserPlateCard::where('id', $userPlateCardId)->first();

    $beforeDLevel = $card->draft_level;

    $card->is_mom = $optaStats['is_mom'];
    $card->card_grade = $cardGrade;
    $card->position = $position;
    $card->status = PlateCardStatus::COMPLETE;
    // $card->draft_season_id = $seasonId;
    // $card->draft_season_name = $_datas['commonRowOrigin']['season_name'];
    $card->draft_team_id = $teamId;
    $card->draft_team_names = $draftTeamNames;
    // $card->draft_schedule_round = $_datas['commonRowOrigin']['round'];
    $card->draft_price_grade = $card->plateCard->grade;
    $card->draft_shirt_number = $card->plateCard->shirt_number;
    $card->draft_level = $draftLevel;
    if ($position === PlayerPosition::GOALKEEPER) {
      $card->goalkeeping_level = array_sum($draftCompleteRowWithCate['success'][FantasyDraftCategoryType::GOALKEEPING] ?? [0]);
    } else {
      $card->attacking_level = array_sum($draftCompleteRowWithCate['success'][FantasyDraftCategoryType::ATTACKING] ?? [0]);
    }
    $card->passing_level = array_sum($draftCompleteRowWithCate['success'][FantasyDraftCategoryType::PASSING] ?? [0]);
    $card->defensive_level = array_sum($draftCompleteRowWithCate['success'][FantasyDraftCategoryType::DEFENSIVE] ?? [0]);
    $card->duel_level = array_sum($draftCompleteRowWithCate['success'][FantasyDraftCategoryType::DUEL] ?? [0]);
    $card->special_skills = $draftSpecialSkill;
    $card->draft_completed_at = now();


    // auction 데이터 업데이트
    $this->updateAuctionTable($card->toArray());

    //==>> ingame point 계산
    /**
     * @var FantasyCalculator $fipCalculator
     */
    $fipCalculator = app(FantasyCalculatorType::FANTASY_INGAME_POINT, [0]);

    $inGameMeta = $fipCalculator->calculate([
      'user_card_attrs' => $card->toArray(),
      'fantasy_point' => $optaStats['fantasy_point'],
      'is_mom' => $optaStats['is_mom'],
      'schedule_id' => $scheduleId,
      'origin_stats' => $optaStats->toArray(),
      'fp_stats' => $fpStats->toArray(),
    ], true);

    $ingamePoint = $inGameMeta['ingame_point'];
    $levelWeight = $inGameMeta['level_weight'];

    $card->ingame_fantasy_point = $ingamePoint;
    $card->level_weight = $levelWeight;
    //<<== ingame point 계산

    $card->save();

    $scheduleInst = Schedule::whereId($scheduleId)->first();


    DraftLog::updateOrCreateEx(
      [
        'user_plate_card_id' => $userPlateCardId,
        'schedule_id' => $scheduleId,
        'status' => $draftStatus,
      ],
      [
        'user_id' => $userId,
        'user_plate_card_id' => $userPlateCardId,
        'draft_season_id' => $scheduleInst->season_id,
        'draft_team_id' => $teamId,
        'schedule_id' => $scheduleId,
        'origin_started_at' => $scheduleInst->started_at,
        'schedule_status' => $scheduleInst->status,
        'card_grade' => $cardGrade,
        'status' => $draftStatus,
        'draft_level' => $draftLevel,
      ]
    );
    if ($beforeDLevel !== $afterDLevel) {
      logger('(drafte level) befor->after:' . $beforeDLevel . '->' . $afterDLevel);
    }
  }

  // 진행중이거나 가장 곧 있을 라운드
  public function upcomingRound($_season_id)
  {
    return Schedule::where('season_id', $_season_id)
      ->whereIn('status', [ScheduleStatus::FIXTURE, ScheduleStatus::PLAYING])
      // 진해
      ->selectRaw('ga_round, ABS(DATEDIFF(NOW(), started_at)) AS diffTime')
      ->orderBy('diffTime')
      ->limit(1)
      ->value('ga_round');
  }
}
