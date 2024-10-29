<?php

namespace App\Console\Commands\DataControll\Live;

use App\Console\Commands\DataControll\Live\LiveMA2MatchStatsParser;
use App\Console\Commands\DataControll\TeamHeadToHeadUpdator;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Schedule\ScheduleWinnerStatus;
use App\Enums\System\NotifyLevel;
use App\Exceptions\Custom\Parser\OTPInsertException;
use App\Jobs\JobCancelDraft;
use App\Jobs\JobCompleteDraft;
use App\Jobs\JobPlateCardChangeUpdate;
use App\Jobs\JobUpdateCurrentMeta;
use App\Libraries\Traits\DraftTrait;
use App\Libraries\Traits\LogTrait;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\OptaTeamDailyStat;
use App\Models\data\Schedule;
use App\Models\game\GameLineup;
use App\Models\game\GamePossibleSchedule;
use App\Models\game\PlayerDailyStat;
use App\Models\game\TeamDailyStat;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Support\Facades\Schema;
use Str;

// https://api.performfeeds.com/soccerdata/matchstats/1vmmaetzoxkgg1qf6pkpfmku0k/ew92044hbj98z55l8rxc6eqz8?_fmt=json&_rt=b&detailed=yes
// goals의 scorerId 또는 assistPlayerId 가 players 테이블에 없는 경우가 있는 듯. 확인할 것. 또는 Foreign key로 처리하고 테스트해볼 것.
class LiveWrapupDraft extends LiveMA2MatchStatsParser
{
  use LogTrait, DraftTrait;

  protected $ma2LiveLastUpdateKeyName;
  protected $currentDateTimeUTC;
  protected $paramList;
  protected $timeCheckerArr = [];

  // public array $param = [
  //   'mode' => 'all'
  // ];

  public function __construct()
  {
    parent::__construct();
  }



  // protected function canWarupStatus($_response): bool
  // {
  //   return $this->isNormalEndStatus($_response);
  // }

  protected function makeCommonInfos($_publishData) {}


  // protected function getDailyStatsFromDatas($_datas, $_player_id): array
  // {
  //   foreach ($_datas['specifiedAttrs']['playerFp'] as $uniqueKey => $playerSet) {
  //     if ($playerSet['player_id'] === $_player_id) {
  //       return $playerSet;
  //     }
  //   }
  // }

  protected function makeStartedAt(&$_response)
  {
    if (isset($_response['matchInfo']['date']) && isset($_response['matchInfo']['time'])) {
      // $_response['matchInfo']['started_at'] = Carbon::parse($_response['matchInfo']['date'] . $_response['matchInfo']['time'])->format('Y-m-d\TH:i:s\Z');
      $_response['matchInfo']['started_at'] = Carbon::parse($_response['matchInfo']['date'] . $_response['matchInfo']['time'])->toDateTimeString();
    }
  }

  // protected function finishDraft($_dSelections, $_datas)
  // {
  //   $playerId  = $_dSelections->player_id;
  //   $scheduleId = $_dSelections->schedule_id;
  //   $optaStats = (OptaPlayerDailyStat::gameParticipantPlayer()
  //     ->where([
  //       ['schedule_id', $scheduleId],
  //       ['player_id', $playerId],
  //     ])->first());
  //   if ($optaStats) {
  //     $this->completeDraft($_dSelections, $_datas, $optaStats);
  //   } else { // 경기 참가하지 않은 선수에 대한 드래프트 처리(취소)
  //     $sesonId = $_datas['commonRowOrigin']['season_id'];
  //     $startedAt = $_datas['commonRowOrigin']['started_at'];
  //     $scheduleStatus = $_datas['commonRowOrigin']['status'];
  //     $this->cancelDraft($_dSelections, $sesonId, $startedAt, $scheduleStatus);
  //   }
  // }

  // protected function completeDraft($_dSelections, $_datas, $_optaStats)
  // {
  //   /**
  //    * @var FantasyCalculator $draftCalculator
  //    */
  //   $draftCalculator = app(FantasyCalculatorType::FANTASY_DRAFT, [0]);

  //   $playerId  = $_dSelections->player_id;
  //   $scheduleId = $_dSelections->schedule_id;
  //   $seasonId = $_datas['commonRowOrigin']['season_id'];
  //   $userId = $_dSelections->user_id;
  //   $userPlateCardId = $_dSelections->user_plate_card_id;
  //   logger($playerId);
  //   logger($scheduleId);
  //   logger($userId);
  //   logger($userPlateCardId);
  //   // $_optaStats = (OptaPlayerDailyStat::gameParticipantPlayer()
  //   //   ->where([
  //   //     ['schedule_id', $scheduleId],
  //   //     ['player_id', $playerId],
  //   //   ])->first());
  //   $dailyStats = $this->getDailyStatsFromDatas($_datas, $playerId);
  //   $cardGrade = $dailyStats['card_grade'];
  //   $position = $dailyStats['summary_position'];
  //   $teamId = $dailyStats['team_id'];
  //   $draftStatus = DraftCardStatus::COMPLETE;
  //   $draftCompleteRowWithCate = $draftCalculator->calculate(
  //     [
  //       'opta_stats' => $_optaStats->toArray(),
  //       'selections' => $_dSelections->toArray(),
  //     ]
  //   );
  //   $draftCompleteRow = [];
  //   foreach ($draftCompleteRowWithCate['success'] as $cate => $attSet) {
  //     $draftCompleteRow = array_merge($draftCompleteRow, $attSet);
  //   }

  //   $draftLevel = array_sum($draftCompleteRow);

  //   $draftTeamNames = null;
  //   if (($plateCardAttr = PlateCard::wherePlayerId($playerId)->get()->first()) !== null) {
  //     $draftTeamNames = [
  //       'team_name' => $plateCardAttr['team_name'],
  //       'team_short_name' => $plateCardAttr['team_short_name'],
  //       'team_club_name' => $plateCardAttr['team_club_name'],
  //       'team_code' => $plateCardAttr['team_code'],
  //     ];
  //   }

  //   /**
  //    * 드래프트 완료 처리
  //    * - user_plate_cards - card_grade, position, status=> complete, draft_season_id, draft_team_id, draft_complete_at 기록
  //    * - draft-log 기록
  //    */
  //   DraftComplete::create(
  //     array_merge([
  //       'user_id' => $userId,
  //       'user_plate_card_id' => $userPlateCardId,
  //       'summary_position' => $_optaStats['summary_position'],
  //     ], $draftCompleteRow)
  //   );

  //   /**
  //    * @var FantasyCalculator $fipCalculator
  //    */
  //   $fipCalculator = app(FantasyCalculatorType::FANTASY_INGAME_POINT, [0]);

  //   $card = UserPlateCard::where('id', $userPlateCardId)->first();

  //   $inGamePoint = $fipCalculator->calculate([
  //     'user_card_attrs' => $card->toArray(),
  //     'fantasy_point' => $_optaStats['fantasy_point'],
  //     'is_mom' => $_optaStats['is_mom'],
  //   ]);

  //   $card->ingame_fantasy_point = $inGamePoint;
  //   $card->is_mom = $_optaStats['is_mom'];
  //   $card->card_grade = $cardGrade;
  //   $card->position = $position;
  //   $card->status = PlateCardStatus::COMPLETE;
  //   $card->draft_season_id = $seasonId;
  //   $card->draft_season_name = $_datas['commonRowOrigin']['season_name'];
  //   $card->draft_team_id = $teamId;
  //   $card->draft_team_names = $draftTeamNames;
  //   $card->draft_schedule_round = $_datas['commonRowOrigin']['round'];
  //   $card->draft_level = $draftLevel;
  //   if ($position === PlayerPosition::GOALKEEPER) {
  //     $card->goalkeeping_level = array_sum($draftCompleteRowWithCate['success'][FantasyDraftCategoryType::GOALKEEPING] ?? [0]);
  //   } else {
  //     $card->attacking_level = array_sum($draftCompleteRowWithCate['success'][FantasyDraftCategoryType::ATTACKING] ?? [0]);
  //   }
  //   $card->passing_level = array_sum($draftCompleteRowWithCate['success'][FantasyDraftCategoryType::PASSING] ?? [0]);
  //   $card->defensive_level = array_sum($draftCompleteRowWithCate['success'][FantasyDraftCategoryType::DEFENSIVE] ?? [0]);
  //   $card->duel_level = array_sum($draftCompleteRowWithCate['success'][FantasyDraftCategoryType::DUEL] ?? [0]);
  //   $card->draft_completed_at = now();
  //   $card->save();

  //   $this->recordLog(
  //     DraftLog::class,
  //     [
  //       'user_plate_card_id' => $userPlateCardId,
  //       'draft_season_id' => $seasonId,
  //       'draft_team_id' => $teamId,
  //       'schedule_id' => $scheduleId,
  //       'origin_started_at' => $_datas['commonRowOrigin']['started_at'],
  //       'schedule_status' => $_datas['commonRowOrigin']['status'],
  //       'card_grade' => $cardGrade,
  //       'status' => $draftStatus,
  //       'draft_level' => $draftLevel,
  //     ]
  //   );
  // }

  protected function wrapUpCommon() {}


  // TODO : 이름변경 필요 min_played 계산 필요
  protected function resetUserRank($_datas)
  {
    // $seasonId = $_datas['commonRowOrigin']['season_id'];
    // $scheduleId = $_datas['commonRowOrigin']['schedule_id'];
    // OptaPlayerDailyStat::where('schedule_id', $scheduleId)
    //   ->get()
    //   ->map(function ($optaPlayer) use ($scheduleId) {
    //     $playerId = $optaPlayer->player_id;
    //     $minsPlayed = $optaPlayer->mins_played;
    //     GameLineup::where([
    //       ['schedule_id', $scheduleId],
    //       ['player_id', $playerId],
    //     ])->update(['mins_played' => $minsPlayed]);
    //   });
  }


  protected function wrapUpNormal($_datas)
  {
    // 정상 종료된 경기 최종 마무리
    /**
     * 1. 드래프트 강화 업데이트
     * 2. plate card (price 변동)
     * 3. ref_player_metas 테이블 업데이트
     * 4. user_rank를 위한 game_lineup mins_played 업데이트
     * 5. wrapUpdCommon 호출
     */

    // 1. 
    JobCompleteDraft::dispatch($_datas);
    // DraftSelection::whereHas('userPlateCard', function ($_query) {
    //   $_query->where('status', PlateCardStatus::UPGRADING);
    // })->where('schedule_id', $_datas['commonRowOrigin']['schedule_id'])
    //   ->get()
    //   ->map(function ($dSelection) use ($_datas) {
    //     $this->finishDraft($dSelection, $_datas);
    //   });

    // 2. job으로 넘기기
    $targetPlayers = [];
    foreach ($_datas['specifiedAttrs']['playerFp'] as $idx => $playerSet) {
      $targetPlayers[] = $playerSet['player_id'];
    }
    JobPlateCardChangeUpdate::dispatch($targetPlayers);

    // 3.
    JobUpdateCurrentMeta::dispatch($_datas);

    // 4.
    $this->resetUserRank($_datas);

    // 5.
    $this->wrapUpCommon();

    $scheduleId = $_datas['commonRowOrigin']['schedule_id'];
  }

  protected function wrapUpCancel($_response)
  {
    /**
     * 1. 드래프트 취소
     * 2. wrapUpCommon 호출
     */
    JobCancelDraft::dispatch($this->getScheduleId($_response), $this->getStatus($_response));
    // $this->cancelDraftAllByScheduleId($this->getScheduleId($_response), $this->getStatus($_response));
    $this->wrapUpCommon();
  }

  protected function getCleanSheetTeamId($_datas): null|string
  {
    $winner = $_datas['commonRowOrigin']['winner'];
    if ($winner !== ScheduleWinnerStatus::DRAW) {
      $oppositeTeamSideMap = ['away' => 'home', 'home' => 'away'];
      $winTeamLostGoal = $_datas['commonRowOrigin']['total_' . $oppositeTeamSideMap[$winner]];
      if ($winTeamLostGoal === 0) {
        return Schedule::where('id', $_datas['commonRowOrigin']['schedule_id'])
          ->first()[$winner . '_team_id'];
      }
    }
    return null;
  }

  protected function validatePlayerStats($_datas)
  {
    // live 경기 종료(Played 상태) 시 mins_played의 업데이트가 제대로 되었는지 검증한다.
    $validationState = true;
    $winnerGoalState = false;
    $statName = '';
    if ($_datas['commonRowOrigin']['status'] !== ScheduleStatus::PLAYED) return true;
    $cleanSheetTeamId = $this->getCleanSheetTeamId($_datas);
    foreach ($_datas['specifiedAttrs']['player'] as $idx => $statSet) {
      // mins_played
      if (($statSet['game_started'] === 1 || $statSet['total_sub_on'] === 1) && !isset($statSet['mins_played'])) {
        $validationState = false;
        $statName = 'mins_played';
        break;
      }
      // clean_sheet
      if (($cleanSheetTeamId === $statSet['team_id']) && isset($statSet['clean_sheet']) && (int)$statSet['clean_sheet'] !== 1) {
        $validationState = false;;
        $statName = 'clean_sheet';
        break;
      }
      // winning_goal
      if ($_datas['commonRowOrigin']['winner'] !== ScheduleWinnerStatus::DRAW) {
        if ((isset($statSet['winning_goal'])) && ((int)$statSet['winning_goal'] === 1)) {
          $winnerGoalState = true;
        }
      } else { // draw
        $winnerGoalState = true;
      }
    }
    $messags = [
      0 => sprintf('sechedule id(%s) wating opta stats(%s) fitting', $_datas['commonRowOrigin']['schedule_id'], Str::camel($statName)),
      1 => sprintf('sechedule id(%s) opta stats validation(ok!)', $_datas['commonRowOrigin']['schedule_id']),
    ];
    __telegramNotify(NotifyLevel::DEBUG, 'live wrapup', $messags[(int)$validationState * (int)$winnerGoalState]);
    return $validationState;
  }

  protected function makeTeamSide($_datas, $_response): array
  {
    foreach ($_response['matchInfo']['contestant'] as $teamSet) {
      $_datas['commonRowOrigin'][$teamSet['position'] . '_' . 'team_id'] = $teamSet['id'];
    }
    return $_datas;
  }

  protected function insertOptaDatasToTables(
    array $_responses,
    array $_commonInfoToStore = null,
    array $_specifiedInfoToStore = null,
    $_realStore = false,
  ): void {
    foreach ($_responses as $urlKey => $response) { // 비동기 응답s 처리
      if ($this->isCancelStatus($response)) { // 드래프트 취소
        DB::beginTransaction();
        try {
          $this->wrapUpCancel($response);
          DB::commit();
        } catch (Exception $e) {
          DB::rollBack();
          __telegramNotify(NotifyLevel::CRITICAL, 'live wrapup draft cancel:', '에러 발생 로그 체크!');
          report(new OTPInsertException(null, ['feed_nick' => $this->feedNick, 'path' => $this->current_url_extra,], $e));
        }
        continue;
      }

      $this->makeStartedAt($response);
      $datas = $this->makeTeamSide($this->middleCalPointProcess($this->preProcessResponse($urlKey, $response)), $response);

      // user point 데이터s 갱신
      $this->addScheduleQueueMap($response, 'fake');
      $this->makePubMuliSetUserLineup($datas);
      $this->makePubMultiSetUserRank($datas);
      //

      // data 체크->
      if (!$_realStore) {
        logger($datas['commonRowOrigin']);
        logger($datas['specifiedAttrs']);
        $this->generateColumnNames();
        dd('-xTestx-');
      }
      // data 체크<-

      // if (!$this->validatePlayerStats($datas)) continue;

      DB::beginTransaction();
      try {
        Schema::connection('api')->disableForeignKeyConstraints();
        $this->insertDatas($_commonInfoToStore, $_specifiedInfoToStore, $datas);
        $this->applyMOM($datas);
        /**
         * status 처리
         * core
         */
        if ($this->isNormalEndStatus($response)) { // 마무리 작업
          $this->wrapUpNormal($datas); // 정상종료 마무리
        }

        $this->updateScheduleAllAttrs($response); // 경기 schedule에 관련된 모든 상태 업데이트(중요: 트랜잭션 내에서)

        // <<--

        // team 집계
        (new TeamHeadToHeadUpdator($datas['commonRowOrigin']['season_id']))->update();
        logger("before commit");
        DB::commit();
        $this->updateGamePossibleScheduleStatus($response);
        logger("after commit");
      } catch (Exception $e) {
        DB::rollBack();
        __telegramNotify(NotifyLevel::CRITICAL, 'live wrapup draft:', '에러 발생 로그 체크!');
        report(new OTPInsertException(null, ['feed_nick' => $this->feedNick, 'path' => $this->current_url_extra,], $e));
      } finally {
        Schema::connection('api')->enableForeignKeyConstraints();
      }
    }
  }

  private function getWraupTargeScheduleIds()
  {
    return GamePossibleSchedule::withTrashed()->where(function ($query) {
      return $query
        ->where(function ($innerQuery) {
          return $innerQuery
            ->whereIn(
              'status',
              [ScheduleStatus::SUSPENDED, ScheduleStatus::CANCELLED, ScheduleStatus::POSTPONED]
            )->where('deleted_at', '<', Carbon::now()->subMinutes(10)); // 취소된 후 10분 후 경기
        })->orWhere(function ($innerQuery) {
          return $innerQuery->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
            ->whereHas('schedule', function ($query) {
              return $query->where('ended_at', '<', Carbon::now()->subMinutes(90)); // 정상 종료된 후 1시간 30분 후 경기
            });
        });
    })->where('wrapup_draft_completed', false)
      ->where('wrapup_draft_cancelled', false)
      ->pluck('schedule_id')->toArray();
  }

  protected function parse(bool $_act): bool
  {
    $ids = $this->getWraupTargeScheduleIds();
    // logger(count($ids));
    logger('live wrapup:' . json_encode($ids));
    // throw (new OTPInsertException(null, []));
    //logger('x');
    // optaParser 설정 -->>
    $this->setKeysToIgnore([
      'VAR',
    ]);
    $this->setKGsToCustom(['matchInfo/contestant', 'liveData/lineUp']);
    // $this->setGlueChildKeys([]);
    // optaParser 설정 <<--
    $this->setKeyNameTransMap(['matchStatus' => 'status', 'matchInfoId' => 'matchId', 'touches' => 'touchesOpta']);


    $idChunks = array_chunk($ids, self::REQUEST_COUNT_AT_ONCE);
    foreach ($idChunks as $idx => $idChunk) {
      $responses = $this->optaRequest($idChunk);
      $this->insertOptaDatasToTables(
        $responses,
        null,
        [
          [
            'specifiedInfoMap' => ['player' => OptaPlayerDailyStat::class],
            'conditions' => ['schedule_id', 'player_id']
          ],
          [
            'specifiedInfoMap' => ['teamStats' => OptaTeamDailyStat::class],
            'conditions' => ['schedule_id', 'team_id']
          ],
          [
            'specifiedInfoMap' => [self::playerDailySpecifiedKey => PlayerDailyStat::class],
            'conditions' => ['schedule_id', 'player_id'] // update condidions
          ],
          [
            'specifiedInfoMap' => ['teamStats' => TeamDailyStat::class],
            'conditions' => ['schedule_id', 'team_id']
          ],
        ],
        $_act,
      );
    }
    return true;
  }
}
