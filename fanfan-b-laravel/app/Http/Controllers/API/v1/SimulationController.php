<?php

namespace App\Http\Controllers\API\v1;

use App\Enums\ErrorDefine;
use App\Enums\GradeCardLockStatus;
use App\Enums\Opta\Card\DraftCardStatus;
use App\Enums\Opta\Card\PlateCardStatus;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\YesNo;
use App\Enums\Simulation\SimulationScheduleStatus;
use App\Enums\Simulation\SimulationTeamSide;
use App\Enums\SimulationCalculator\SimulationCalculatorType;
use App\Enums\SimulationCalculator\SimulationCategoryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Simulation\RankConfirmCheckRequest;
use App\Http\Requests\Api\Simulation\ScheduleGameListRequest;
use App\Http\Requests\Api\Simulation\ScheduleSummaryRequest;
use App\Http\Requests\Api\Simulation\ScheduleSummarySeasonRequest;
use App\Http\Requests\Api\Simulation\SubmitLineupRequest;
use App\Http\Requests\Api\Simulation\ValidApplicantRequest;
use App\Http\Requests\Api\Simulation\MyCardsRequest;
use App\Http\Requests\Api\Simulation\RegisterApplicantRequest;
use App\Http\Requests\Api\Simulation\SimulationScheduleRequest;
use App\Http\Requests\Api\Simulation\SimulationRankRequest;
use App\Http\Requests\Api\Simulation\SimulationReportRequest;
use App\Models\simulation\SimulationApplicant;
use App\Models\simulation\SimulationApplicantStat;
use App\Models\simulation\SimulationDivision;
use App\Models\simulation\SimulationLeagueStat;
use App\Models\simulation\SimulationLineup;
use App\Models\simulation\SimulationSeason;
use App\Models\simulation\SimulationStep;
use App\Models\simulation\SimulationUserLeague;
use App\Models\simulation\SimulationUserLineup;
use App\Models\simulation\SimulationUserLineupMeta;
use App\Models\simulation\SimulationUserRank;
use App\Services\Simulation\SimulationService;
use Exception;
use App\Libraries\Traits\CommonTrait;
use App\Libraries\Traits\SimulationTrait;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\game\PlateCard;
use App\Models\meta\RefPlayerOverallHistory;
use App\Models\order\OrderPlateCard;
use App\Models\simulation\SimulationDivisionStat;
use App\Models\simulation\SimulationLineupMeta;
use App\Models\simulation\SimulationRefCardValidation;
use App\Models\simulation\SimulationSchedule;
use App\Models\user\UserPlateCard;
use App\Services\Data\DataService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Schema;
use Symfony\Component\HttpFoundation\Response;
use ReturnData;
use Throwable;

class SimulationController extends Controller
{
  use CommonTrait, SimulationTrait;

  protected SimulationService $simulationService;
  protected DataService $dataService;

  public function __construct(SimulationService $_simulationService, DataService $_dataService)
  {
    $this->simulationService = $_simulationService;
    $this->dataService = $_dataService;
  }

  public function checkApplicant()
  {
    try {
      $result = $this->simulationService->checkApplicant();
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }

    return ReturnData::setData($result)->send(Response::HTTP_OK);
  }

  public function registerApplicant(RegisterApplicantRequest $request)
  {
    $input = $request->only([
      'club_code_name',
      'user_id',
      'server'
    ]);

    DB::beginTransaction();
    try {
      $applicant = new SimulationApplicant();
      $applicant->user_id = $input['user_id'];
      $applicant->club_code_name = $input['club_code_name'];
      $applicant->server = $input['server'];
      $applicant->save();

      $registerDefaultUserLineup = $this->simulationService->registerDefaultUserLineup($applicant);

      if (!$registerDefaultUserLineup) {
        throw new Exception('Default lineup registration failed', Response::HTTP_BAD_REQUEST);
      }

      $divisionId = SimulationDivision::whereHas('tier', function ($query) {
        $query->where('level', 6);
      })
        ->value('id');

      $group = new SimulationUserLeague();
      $group->applicant_id = $applicant->id;
      $group->season_id = SimulationSeason::currentSeasons()->value('id');
      $group->division_id = $divisionId;
      $group->save();

      DB::commit();
    } catch (Exception $th) {
      DB::rollBack();
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }

    return ReturnData::send(Response::HTTP_OK);
  }

  public function myLineup(ValidApplicantRequest $request)
  {
    try {
      $applicant = SimulationApplicant::where('user_id', $request->user()->id)
        ->first();
      // 다음 시즌을 미리 만들어 놓아야 할까..?
      $tz = config('simulationpolicies.server')[$applicant->server]['timezone'];
      $nextScheduleStartedAt = $this->simulationService->getMySchedule($request->user()->id, 'next')
        ->value('started_at')?->toDateTimeString();

      $userLineup = null;
      if (is_null($nextScheduleStartedAt)) {
        $nextScheduleStartedAt = now($tz)->next('monday')->setHour(10)->setTimezone('UTC')->toDateTimeString();
      } else {
        $userLineup = $this->simulationService->getUserLineup($request);
      }
      $result['next_started_at'] = $nextScheduleStartedAt;
      // 일단 다음 시즌 시작 계산을 해서 적용

      $result['user_lineup'] = $userLineup;

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());

      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function myCards(MyCardsRequest $request)
  {
    $filter = $request->only([
      'player_name',
      'grade',
      'club',
      'position_type',
      'position_value',
      'page',
      'per_page'
    ]);

    try {
      $result = $this->simulationService->getUserCards([...$filter, 'user_id' => $request->user()->id]);
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());

      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function lobby(ValidApplicantRequest $request)
  {
    try {
      // ApplicantId 구하기, 하위 코드에서 whereHas를 줄이기 위해 뽑음
      $applicant = SimulationApplicant::with([
        'userLeague' => function ($userLeagueQuery) {
          $userLeagueQuery->whereHas('season', function ($seasonQuery) {
            $seasonQuery->currentSeasons();
          })
            ->with('league.division.tier');
        },
      ])
        ->where('user_id', $request->user()->id)
        ->first();

      if (is_null($applicant)) {
        throw new Exception('Applicant not found', Response::HTTP_NOT_FOUND);
      }

      $applicantId = $applicant->id;

      // 시뮬 신청 후 최초 진입 대응
      if (is_null($applicant->userLeague->league_id)) {
        return ReturnData::send(Response::HTTP_OK);
      }

      // $weekday = now(config('simulationpolicies.server')[$applicant->server]['timezone'])->englishDayOfWeek;

      // $resultUnchecked = SimulationSchedule::whereHas('season', function ($seasonQuery) {
      //   $seasonQuery->currentSeasons();
      // })
      //   ->where('status', ScheduleStatus::PLAYED)
      //   ->whereRaw("DATE_FORMAT(started_at, '%W') = '{$weekday}'")
      //   ->whereHas('lineupMeta', function ($lineupMetaQuery) use ($applicantId) {
      //     $lineupMetaQuery->where([
      //       'is_result_checked' => false,
      //       'applicant_id' => $applicantId,
      //     ]);
      //   })
      //   ->oldest('started_at')
      //   ->get();

      // $result['result_unchecked'] = null;
      // if ($resultUnchecked->count() > 0) {
      //   $result['result_unchecked'] = [
      //     'count' => $resultUnchecked->count(),
      //     'id' => $resultUnchecked->first()?->id,
      //   ];
      // }
      $unCheckedGame = $this->getUncheckedGames($request->user()->id);
      if ($unCheckedGame) {
        $result['result_unchecked'] = $unCheckedGame;
      }

      // 알림
      $notificationMessage = [];
      $userLineupMeta = $this->simulationService->getUserLineupMeta($applicantId);
      if ($userLineupMeta->is_first) {
        $notificationMessage['code'] = 'HAS_NOT_CHANGED';
      } else if (!$this->simulationService->resultCheckedAll($applicantId)) {
        $notificationMessage['code'] = 'NEED_CHECK_RESULT';
      } else if ($userLineupMeta->is_in_trouble) {
        $notificationMessage['code'] = 'NEED_CHECK_LINEUP';
      } else if ($soon = $this->simulationService->getSoonFixture($applicantId)) {
        // $tz = config('simulationpolicies.server')[$applicant->server]['timezone'];
        $notificationMessage = [
          'code' => 'SOON_FIXTURE',
          'started_at' => $soon->started_at->toDateTimeString(),
        ];
      } else if ($rank = $this->simulationService->showNextTier($applicant)) {
        // 기본 : 잔류
        $leagueResult = 'REMAIN';
        if ($rank->ranking <= 4) {
          // 승격
          $leagueResult = 'PROMOTED';
        } else if ($rank->ranking >= 17) {
          // 강등
          $leagueResult = 'RELEGATED';
        }
        $notificationMessage['code'] = sprintf('TIER_OPEN_%s', $leagueResult);
        $notificationMessage['rank'] = $rank->ranking;
      } else {
        $notificationMessage['code'] = 'NORMAL';
      }


      // 리그 티어 & 내정보
      $myLeague = $applicant->userLeague;
      $userInfo['league'] = $myLeague->league->toArray();
      $userInfo['applicant'] = $myLeague->applicant->toArray();

      $sub = SimulationApplicantStat::where([
        'league_id' => $myLeague->league_id,
      ])
        ->selectRaw('RANK() over (order by points desc) as ranking, count_won, count_draw, count_lost, points as pts, applicant_id
        ');

      $rankInfo = DB::query()->fromSub($sub, 's')
        ->where('applicant_id', $myLeague->applicant_id)
        ->first();

      $userInfo['rank_info'] = $rankInfo;

      $userLineupStarted = $userLineupMeta->userLineup->where('game_started', true)
        ->map(function ($item) {
          if ($item->simulationOverall) {
            $finalOverall = $item->simulationOverall->final_overall;
            $subPosition = $item->simulationOverall->sub_position;
            $item->final_overall = $finalOverall ? (int) $finalOverall[$subPosition] : null;
          }

          return $item;
        });

      $userInfo['overall_avg'] = round($userLineupStarted->avg('final_overall'), 1);
      $overallByPosition = [];
      $userLineupStarted->groupBy('position')->map(function ($group, $key) use (&$overallByPosition) {
        $overallByPosition['my'][$key] = (int) round($group->avg('final_overall'), 1);
      });
      $result['user_info'] = $userInfo;

      // 동일 리그에 있는 타 유저들의 라인업
      $leagueUsers = SimulationUserLeague::where('league_id', $myLeague->league_id)
        ->pluck('applicant_id');

      SimulationUserLineup::with('simulationOverall')
        ->whereHas('userLineupMeta', function ($query) use ($leagueUsers) {
          $query->whereIn('applicant_id', $leagueUsers);
        })
        ->where('game_started', true)
        ->get()
        ->map(function ($item) {
          if ($item->simulationOverall) {
            $finalOverall = $item->simulationOverall->final_overall;
            $subPosition = $item->simulationOverall->sub_position;
            $item->final_overall = $finalOverall ? (int) $finalOverall[$subPosition] : null;
          }

          return $item;
        })
        ->groupBy('position')
        ->map(function ($group, $key) use (&$overallByPosition) {
          $overallByPosition['others'][$key] = (int) round($group->avg('final_overall'), 1);
        });

      $result['position_overall_avg'] = $overallByPosition;

      // 베스트 플레이어
      $seasonStats = SimulationApplicantStat::where([
        'season_id' => $myLeague->league->season_id,
        'applicant_id' => $myLeague->applicant_id,
      ])
        ->first();

      $bestPlayer = [
        'goal' => null,
        'assist' => null,
        'save' => null,
        'rating' => null,
      ];
      if (!is_null($seasonStats)) {
        $bestPlayer['goal'] = $seasonStats->best_goal_players[0];
        $bestPlayer['assist'] = $seasonStats->best_assist_players[0];
        $bestPlayer['save'] = $seasonStats->best_save_players[0];
        $bestPlayer['rating'] = $seasonStats->best_rating_players[0];
      }
      $result['best_player'] = $bestPlayer;

      $result['notice'] = $notificationMessage;
    } catch (Throwable $th) {
      logger($th);
      return ReturnData::setError([ErrorDefine::INTERNAL_SERVER_ERROR, 'SERVER ERROR'])->send(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return ReturnData::setData($result, $request)->send(Response::HTTP_OK);
  }

  public function submitLineup(SubmitLineupRequest $request)
  {
    $input = $request->only([
      'user_id',
      'lineups',
      'formation_used',
      'playing_style',
      'defensive_line',
      'substitution_count'
    ]);

    try {
      //신청인의 user_lineup_meta id 조회
      $simulationUserLineupMeta = SimulationUserLineupMeta::withWhereHas('applicant', function ($query) use ($input) {
        $query->select('id', 'server')
          ->where('user_id', $input['user_id']);
      })->with('userLineup:user_lineup_meta_id,user_plate_card_id,formation_place')
        ->first();

      $requestLineups = json_decode($input['lineups'], true);
      $cardIds = array_column($requestLineups, 'id');

      //신청자 server에 해당되는 시즌 스케쥴 조회 쿼리
      $serverScheduleQuery = SimulationSchedule::whereHas('season', function ($query) use ($simulationUserLineupMeta) {
        $query->where('server', $simulationUserLineupMeta->applicant->server);
      });

      //시작시간이 현재 10분 전 후에 해당하는 스케쥴 조회
      $impossibleSchedule = $serverScheduleQuery
        ->clone()
        ->whereBetween(
          'started_at',
          [now()->subMinutes(10), now()->addMinutes(10)]
        )
        ->selectRaw('*,
        CASE
            WHEN started_at BETWEEN NOW() - INTERVAL 10 MINUTE AND NOW() THEN "after"
            WHEN started_at BETWEEN NOW() AND NOW() + INTERVAL 10 MINUTE THEN "before"
        END AS limitStatus')
        ->first();

      if (!is_null($impossibleSchedule)) {
        switch ($impossibleSchedule->limitStatus) {
          case 'before':
            throw new Exception('10_MINUTES_BEFORE_LIMIT', 420);
          case 'after':
            throw new Exception('10_MINUTES_AFTER_LIMIT', 420);
          default:
            break;
        }
      }

      //라인업 수정의 타겟이 되는 스케쥴 조회
      $nextScheduleId = $serverScheduleQuery->clone()
        ->where('is_user_lineup_locked', false)
        ->where('started_at', '>', Carbon::now())
        ->oldest('started_at')
        ->value('id');

      //수정 제출 라인업에 다음 스케쥴 출전 불가 선수가 있는지
      $bannedUserPlateCards = SimulationRefCardValidation::whereIn('user_plate_card_id', $cardIds)
        ->isBanned($nextScheduleId);

      if ($bannedUserPlateCards->exists()) {
        throw new Exception('INCLUDE_BANNED_CARD', 420);
      }

      $formationUsed = $input['formation_used'];
      //포메이션에 맞는 서브포지션
      $formationSubPositions = config('formation-by-sub-position.formation_used')[$formationUsed];
      //출전 선수 명수 사용을 위한 변수 선언
      $formationSubPositionCnt = count($formationSubPositions);
      //포메이션에 맞는 포지션별 후보선수 명수
      $substitutionPositionsCnt = config('simulationpolicies.substitution_count_formations')[$formationUsed];
      //포지션별 필요 명수 배열 반환 (선수+후보선수 합산)
      $totalCount = $formationSubPositionCnt + array_sum(array_values($substitutionPositionsCnt));

      //제출 라인업 조건 검사
      $userPlateCardQuery = UserPlateCard::where([
        ['user_id', $input['user_id']],
        ['status', PlateCardStatus::COMPLETE],
        ['is_open', true]
      ])->whereIn('id', $cardIds);

      //총 16명인지
      if ($userPlateCardQuery->count() !== $totalCount) {
        throw new Exception('NOT_ENOUGH_TOTAL_AVAILABLE_CARDS', 420);
      }

      //16명 중 중복되는 선수가 있음
      if ($userPlateCardQuery->distinct('plate_card_id')->count() !== $totalCount) {
        throw new Exception('DUPLICATE_PLAYER', 420);
      }

      //제출 라인업에 해당하는 user_plate_card 정보
      $userPlateCardArr = $userPlateCardQuery->clone()
        ->with(['simulationOverall:user_plate_card_id,player_id,sub_position'])
        ->select('id', 'position')
        ->get()
        ->keyBy('id')
        ->toArray();

      //현재 user_lineups의 레코드
      $userLineupArr = $simulationUserLineupMeta
        ->userlineup()
        ->select('formation_place', 'user_plate_card_id')
        ->get()
        ->keyBy('user_plate_card_id')
        ->toArray();

      //후보선수의 포지션 담을 배열
      $substitutionPositions = [];
      //제출 선수 중 출전 선수 subposition 담을 배열
      $playerSubPosition = [];
      //최종 라인업 제출 될 배열
      $submitLineupArr = [];

      // 제출 라인업 수만큼 반복 (16)
      foreach ($requestLineups as $lineup) {
        //n번째 제출 라인업의 place_index
        $placeIndex = $lineup['place_index'];
        //n번째 제출 라인업의 id (cardId)
        $lineupCardId = $lineup['id'];
        //n번째 제출 라인업의 sub_position
        $subPosition = $lineup['sub_position'];

        //place_index 0~11: 출전 선수
        if ($placeIndex <= $formationSubPositionCnt) {
          //formationSubPositions 배열의 place_index별 필요 subPosition이 제출 라인업의 sub_position과 다를때
          if ($formationSubPositions[$placeIndex] !== $subPosition) {
            throw new Exception('PLACE_INDEX_UNMATCH_SUB_POSITION', 420);
          }
          //이상없는 선수별 subPosition 추출, 현재 라인업테이블에 동일한 id, formation_place여도 총 합이 동일해야하기때문에 필요
          $playerSubPosition[] = $subPosition;
        } else {
          //place_index가 12~: 후보선수
          //제출 id와 일치하고 가능한 user_plate_cards 중 n번째 제출 라인업의 id가 같은 게 있으면
          //substitutionPosition 별 갯수 체크 위해 후보선수의 주 position 추출
          if (isset($userPlateCardArr[$lineupCardId])) {
            $substitutionPositions[] = $userPlateCardArr[$lineupCardId]['position'];
          }
        }

        //수정전 user_lineups 테이블에 제출 라인업에 해당하는 선수가 있으면 해당 선수는 lock_status 해체 안함
        //lock_status 해체 불 필요 카드는 $userLineupArr에서 unset하여 남은 카드 id만 update 후 lock_status 해제
        if (isset($userLineupArr[$lineupCardId])) {
          //fomation_place가 제출 라인업 place_index와 같으면 수정 불필요
          if ($placeIndex === $userLineupArr[$lineupCardId]['formation_place']) {
            //수정전 user_lineup 테이블 중 수정 필요한 id lock_status 해제해줄거기때문에 수정 불필요 id는 해제 배열에서 제외하고 formation_index 수정 불필요하면 for문 중지
            unset($userLineupArr[$lineupCardId]);
            continue;
          }
          //formation_index만 바뀔, 여전히 user_linups에 있을 id는 해제 배열에서 제외
          unset($userLineupArr[$lineupCardId]);
        }

        //user_plate_card_id를 키로 하는 최종 배열
        $submitLineupArr[$lineupCardId] = [
          'place_index' => $placeIndex,
          'sub_position' => $subPosition
        ];
      }

      //수정될 id 없이 모두 continue 되었을 경우 바로 200 응답으로 내보냄
      if (count($submitLineupArr) === 0) {
        return ReturnData::send(Response::HTTP_OK);
      }

      //출전 선수 subPosition 별 갯수가 필요 갯수와 맞을때
      if (array_count_values($playerSubPosition) != array_count_values($formationSubPositions)) {
        throw new Exception('DIFFERENT_SUB_POSITION_TOTAL_COUNT', 420);
      }

      //후보 선수 position 별 갯수가 필요 갯수와 맞을때
      if (array_count_values($substitutionPositions) != $substitutionPositionsCnt) {
        throw new Exception('SUBSTITUTION_UNMATCH_REQUIRED_POSITION_COUNT', 420);
      }

      Schema::connection('simulation')->disableForeignKeyConstraints();
      DB::beginTransaction();

      // user_lineup_metas 수정
      $simulationUserLineupMeta->update([
        'playing_style' => $input['playing_style'],
        'defensive_line' => $input['defensive_line'],
        'substitution_count' => $input['substitution_count'],
        'formation_used' => $formationUsed,
        'is_first' => false,
      ]);

      $submitLineupPlaceIndex = array_flip(array_column($submitLineupArr, 'place_index'));

      $userLineups = $simulationUserLineupMeta->userLineup()
        ->whereIn('formation_place', array_keys($submitLineupPlaceIndex))
        ->get()
        ->keyBy('formation_place');

      //최종 수정 필요 배열만큼 반복
      foreach ($submitLineupArr as $cardId => $submitLineup) {
        //userPlateCard 정보 담은 배열(player_id,position,sub_position) 중 제출 카드에 해당하는 정보 변수화
        $userPlateCard = $userPlateCardArr[$cardId];
        //출전 or 후보여부 담는 변수 (place_index가 config에 있는지)
        $gameStarted = isset($formationSubPositions[$submitLineup['place_index']]);

        //user_lineups 테이블에 업데이트 될 카드 lock_status => simulation으로 변경
        if (!__startUserPlateCardLock($cardId, GradeCardLockStatus::SIMULATION)) {
          throw new Exception('LOCKED_CARD');
        }

        $userLineups[$submitLineup['place_index']]
          ->update([
            'user_plate_card_id' => $cardId,
            'player_id' => $userPlateCard['simulation_overall']['player_id'],
            'game_started' => $gameStarted,
            'position' => $userPlateCard['position'],
            'sub_position' => $userPlateCard['simulation_overall']['sub_position']
          ]);
      }

      //user_lineups에서 빠질 카드들만 lock_status 해제
      if (!is_null($userLineupArr)) {
        foreach ($userLineupArr as $unlockCard) {
          __endUserPlateCardLock(
            $unlockCard['user_plate_card_id'],
            GradeCardLockStatus::SIMULATION
          );
        }
      }

      // 라인업 attack/defence_power 계산
      /** 
       *  @var SimulationCalculator $simulatioCalculator
       */
      $simulationCalculator = app(SimulationCalculatorType::SIMULATION);
      $simulationCalculator->getAttDefPower($simulationUserLineupMeta->applicant_id);

      DB::commit();
      return ReturnData::send(Response::HTTP_OK);
    } catch (Exception $th) {
      DB::rollBack();
      logger($th);

      return ReturnData::setError($th->getMessage())->send($th->getCode());
    } finally {
      Schema::connection('simulation')->enableForeignKeyConstraints();
    }
  }

  public function scheduleGameList(ScheduleGameListRequest $request)
  {
    $filter = $request->only([
      'year',
      'season',
      'weekday'
    ]);

    try {
      $applicant = SimulationApplicant::where('user_id', $request->user()->id)
        ->with('userLineupMeta')
        ->first();

      // 시즌 필터들
      $seasons = SimulationSeason::whereHas(
        'applicantStat',
        function ($stats) use ($applicant) {
          $stats->where('applicant_id', $applicant->id);
        }
      )
        ->whereYear('first_started_at', $filter['year'])
        ->latest('first_started_at')
        ->get()
        ->map(function ($item) {
          return [
            'id' => $item->id,
            'year' => $item->first_started_at->year,
            'week' => $item->week,
            'first_started_at' => $item->first_started_at->toDateTimeString(),
            'last_started_at' => $item->last_started_at->toDateTimeString(),
          ];
        });

      $years = $seasons->pluck('year')->unique()->toArray();
      $timezone = config('simulationpolicies.server')[$applicant->server]['timezone'];
      $now = now($timezone);

      // Todo: 탈퇴 회원 처리 해야함. 임시로 탈퇴회원정보도 가져오도록 조치
      $resultUncheckedCount = 0;
      // 참여 게임 리스트
      $gameList = SimulationSchedule::with([
        'home.user' => function ($query) {
          $query->withoutGlobalScope('excludeWithdraw')
            ->with('userMeta');
        },
        'away.user' => function ($query) {
          $query->withoutGlobalScope('excludeWithdraw')
            ->with('userMeta');
        },
        'lineupMeta',
      ])
        ->where(function ($whereQuery) use ($applicant) {
          $whereQuery->where('home_applicant_id', $applicant->id)
            ->orWhere('away_applicant_id', $applicant->id);
        })
        ->where('season_id', $filter['season'])
        ->whereRaw("DATE_FORMAT(started_at, '%W') = '{$filter['weekday']}'")
        ->orderBy('round')
        ->get()
        ->map(function ($item) use ($applicant, &$resultUncheckedCount, $now) {
          // home
          // $item->lineupMeta->where('applicant_id', $item->homeUserLineupMeta->applicant_id)->first()->id;
          // away
          // $item->lineupMeta->where('applicant_id', $item->awayApplicant->userLineupMeta->applicant_id)->first()->id;
          $result = [
            'id' => $item->id,
            'season_id' => $item->season_id,
            'league_id' => $item->league_id,
            'started_at' => $item->started_at->toDateTimeString(),
            'round' => $item->round,
            'status' => $item->status,
            'weekday' => $item->started_at->englishDayOfWeek,
            'player_team' => null,
            'match_result' => null,
            'is_result_checked' => false,
            'home' => [
              'formation_used' => $item->home->userLineupMeta->formation_used,
              'substitution_count' => $item->home->userLineupMeta->substitution_count,
              'club_code_name' => $item->home->user->club_code_name,
              'user_name' => $item->home->user->name,
              'photo_path' => $item->home->user->userMeta->photo_path,
              'score' => null,
            ],
            'away' => [
              'formation_used' => $item->away->userLineupMeta->formation_used,
              'substitution_count' => $item->away->userLineupMeta->substitution_count,
              'club_code_name' => $item->away->userLineupMeta->applicant->club_code_name,
              'user_name' => $item->away->user->name,
              'photo_path' => $item->away->user->userMeta->photo_path,
              'score' => null,
            ],
          ];

          foreach (['home', 'away'] as $teamSide) {
            $result[$teamSide]['is_player_team'] = $item->{$teamSide . '_applicant_id'} === $applicant->id;
            if ($item->{$teamSide . '_applicant_id'} === $applicant->id) {
              $result['player_team'] = $teamSide;
            }
          }

          $lineupMetas = $item->lineupMeta->keyBy('team_side');

          // 라인업이 없는 스케쥴을 발견... 'scf6e91f1d91e04e33b5faf594ad8a0120'
          if (count($lineupMetas) > 0) {
            $result['is_result_checked'] = $lineupMetas[$result['player_team']]->is_result_checked;
          }
          // 라인업이 없는 스케쥴을 발견... 'scf6e91f1d91e04e33b5faf594ad8a0120'

          if ($now->clone()->startOfDay()->setTimezone('UTC')->isAfter($item->started_at)) {
            $result['is_result_checked'] = true;
          }

          if ($result['is_result_checked']) {
            foreach ($item->lineupMeta as $lineupMeta) {
              $side = $lineupMeta->team_side;
              $result[$side]['score'] = $lineupMeta->score;
            }

            if ($item->status === SimulationScheduleStatus::PLAYED) {
              if ($item->winner === 'home') {
                $result['match_result'] = $result['player_team'] === 'home' ? 'win' : 'lose';
              } else if ($item->winner === 'away') {
                $result['match_result'] = $result['player_team'] === 'away' ? 'win' : 'lose';
              } else {
                $result['match_result'] = 'draw';
              }
            }
          } else {
            if ($item->status === SimulationScheduleStatus::PLAYED) {
              $resultUncheckedCount++;
            }
          }

          return $result;
        });

      $result = [
        'result_unchecked_count' => $resultUncheckedCount,
        'list' => $gameList,
        'options' => [
          'year' => $years,
          'seasons' => $seasons,
        ],
      ];

      return ReturnData::setData($result, $request)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function scheduleSummarySeason(ScheduleSummarySeasonRequest $request)
  {
    $input = $request->only(['year']);

    try {
      $list = SimulationUserRank::with(['league.division.tier', 'league.season'])
        ->whereHas('applicant.user', function ($userQuery) use ($request) {
          $userQuery->where('user_id', $request->user()->id);
        })
        ->whereHas('league.season', function ($seasonQuery) use ($input) {
          $seasonQuery->where('active', YesNo::NO)
            ->whereYear('first_started_at', $input['year']);
        })
        ->get()
        ->map(function ($item) {
          return [
            'season_id' => $item->league->season->id,
            'season_no' => $item->league->season->week,
            'division_no' => $item->league->division->division_no,
            'tier_name' => $item->league->division->tier->name,
            'tier_level' => $item->league->division->tier->level,
            'league_no' => $item->league->league_no,
            'ranking' => $item->ranking,
            'status' => $item->status,
          ];
        });

      return ReturnData::setData(compact('list'), $request)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }
  public function scheduleSummary(ScheduleSummaryRequest $request)
  {
    $input = $request->only([
      'year',
      'season'
    ]);

    try {
      $applicant = SimulationApplicant::with('userLineupMeta')
        ->where('user_id', $request->user()->id)
        ->first();

      // $applicantId = $userLineupMeta->applicant_id;
      $applicantId = $applicant->id;

      $userRank = SimulationUserRank::with([
        'applicantStat',
        'league.season'
      ])
        ->where('applicant_id', $applicantId)
        ->whereHas('league', function ($league) use ($input) {
          $league->where('season_id', $input['season']);
        })
        ->first();

      $userLeagueRecord = [
        'ranking' => 0,
        'club_ovr' => 0,
        'win' => 0,
        'draw' => 0,
        'lose' => 0,
        'pts' => 0,
      ];

      if (!is_null($userRank)) {
        $userLeagueRecord = [
          'ranking' => $userRank->ranking,
          'club_ovr' => $userRank->applicantStat->overall_avg,
          'win' => $userRank->count_won,
          'draw' => $userRank->count_draw,
          'lose' => $userRank->count_lost,
          'pts' => $userRank->points,
        ];
      }

      $userLineupMetaId = $applicant->userLineupMeta->id;
      $recordByTeamSide = SimulationSchedule::where('season_id', $input['season'])
        ->where(function ($query) use ($applicantId) {
          $query->where('home_applicant_id', $applicantId)
            ->orWhere('away_applicant_id', $applicantId);
        })
        ->selectRaw("
              CAST(SUM(CASE WHEN home_applicant_id = $applicantId AND winner = 'home' THEN 1 ELSE 0 END) AS unsigned) AS home_win,
              CAST(SUM(CASE WHEN home_applicant_id = $applicantId AND winner = 'draw' THEN 1 ELSE 0 END) AS unsigned) AS home_draw,
              CAST(SUM(CASE WHEN home_applicant_id = $applicantId AND winner = 'away' THEN 1 ELSE 0 END) AS unsigned) AS home_lose,           
              CAST(SUM(CASE WHEN away_applicant_id = $applicantId AND winner = 'away' THEN 1 ELSE 0 END) AS unsigned) AS away_win,
              CAST(SUM(CASE WHEN away_applicant_id = $applicantId AND winner = 'draw' THEN 1 ELSE 0 END) AS unsigned) AS away_draw,
              CAST(SUM(CASE WHEN away_applicant_id = $applicantId AND winner = 'home' THEN 1 ELSE 0 END) AS unsigned) AS away_lose
          ")
        ->first();

      // 베스트 플레이어
      $topPlayers = SimulationApplicantStat::where([
        ['season_id', $input['season']],
        ['applicant_id', $applicantId],
      ])
        ->value('best_rating_players');

      // 업적
      $playerStatKeys = [
        'goal',
        'assist',
        'save',
      ];

      $achievement = [];
      $bestLeagueStat = SimulationLeagueStat::where('season_id', $input['season'])
        ->first();

      foreach ($playerStatKeys as $key) {
        $bestLineupStat = SimulationLineup::whereHas(
          'lineupMeta',
          function ($lineupMetaQuery) use ($applicantId, $input) {
            $lineupMetaQuery->where('applicant_id', $applicantId)
              ->whereHas('schedule', function ($schedule) use ($input) {
                $schedule->where('season_id', $input['season']);
              });
          }
        )
          ->orderByDesc($key)
          ->first();

        $bestLeagueStatField = 'best_' . $key . '_card';

        $isSeasonBest = $bestLineupStat && ($bestLeagueStat?->{$bestLeagueStatField} === $bestLineupStat->user_plate_card_id);

        $achievement[] = [
          'user_plate_card_id' => $bestLineupStat?->user_plate_card_id,
          'is_season_best' => $isSeasonBest,
          'stat_type' => $key,
          'stat_value' => $bestLineupStat?->{$key},
        ];
      }

      $result = [
        ...$userLeagueRecord,
        ...$recordByTeamSide->toArray(),
        'top_player' => $topPlayers,
        'achievement' => $achievement,
      ];

      return ReturnData::setData($result, $request)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function getRank(SimulationRankRequest $request)
  {
    $seasonId = $request->only('season')['season'];

    try {
      $userId = $request->user()->id;

      $myLeague = SimulationUserLeague::with([
        'league.division.tier',
        'league.season',
        'applicant'
      ])
        ->whereHas('league.season', function ($query) use ($seasonId) {
          $query->when($seasonId, function ($seasonQuery, $val) {
            $seasonQuery->where('id', $val);
          }, function ($seasonQuery) {
            $seasonQuery->currentSeasons();
          });
        })
        ->whereHas('applicant', function ($query) use ($userId) {
          $query->where('user_id', $userId);
        })
        ->first();

      $userInfo['league'] = $myLeague->league->toArray();
      $userInfo['applicant'] = $myLeague->applicant->toArray();

      $seasonId = $myLeague->league->season->id;
      $leagueId = $myLeague->league->id;
      $leagueRankInfo = $this->simulationService->getRankInfo($seasonId, $leagueId, $myLeague->league->season->active === YesNo::NO);

      $limit = 5;
      $myHistory = $this->simulationService->getUserHistory($userId, $limit);

      $result['user_info'] = $userInfo;
      $result['rank_info'] = $leagueRankInfo;
      $result['history'] = $myHistory;
    } catch (Exception $th) {
      dd($th);
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }

    return ReturnData::setData($result)->send(Response::HTTP_OK);
  }

  public function getReport(SimulationReportRequest $request)
  {
    $input = $request->only(['mode']);

    try {
      $userInfo = SimulationUserLeague::with('division.tier')
        ->whereHas('applicant', function ($query) use ($request) {
          $query->where('user_id', $request->user()->id);
        })->withWhereHas('league.season', function ($query) {
          $query->currentSeasons();
        })->first();

      $divisionId = $userInfo->division_id;
      $leagueId = $userInfo->league_id;
      $seasonId = $userInfo->league->season_id;
      $seasonInfo = $userInfo->league->season;
      $applicantId = $userInfo->applicant_id;

      $divisionLeagueStats = SimulationDivisionStat::withWhereHas('leagueStat', function ($query) use ($leagueId) {
        $query->where('league_id', $leagueId)->limit(1);
      })->where([
        ['season_id', $seasonId],
        ['division_id', $divisionId]
      ])->first();

      $divisionOverallAvg = $divisionLeagueStats?->overall_avg;
      $leagueStats = $divisionLeagueStats?->leagueStat[0];

      $userStats = SimulationApplicantStat::where([
        ['applicant_id', $applicantId],
        ['season_id', $seasonId]
      ])->first();

      $result = [];
      switch ($input['mode']) {
        case 'preview':
          // 1-1. 시즌, division 정보
          $season['id'] = $seasonId;
          $season['week'] = $seasonInfo['week'];
          $season['year'] = Carbon::parse($seasonInfo['first_started_at'])->year;
          $season['first_started_at'] = $seasonInfo['first_started_at']->toDateTimeString();
          $season['last_started_at'] = $seasonInfo['last_started_at']->toDateTimeString();
          $result['season'] =  $season;

          $division['id'] = $divisionId;
          $division['no'] = $userInfo->division->division_no;
          $division['tier_level'] = $userInfo->division->tier->level;
          $division['tier_name'] = $userInfo->division->tier->name;
          $result['division'] = $division;

          $overalls['division'] = $divisionOverallAvg;
          $overalls['league'] = $leagueStats?->overall_avg;
          $overalls['my'] = $userStats?->overall_avg;

          $result['overalls'] = $overalls;

          // 1-2. 예정경기
          $scheduleInfo = SimulationSchedule::where('status', SimulationScheduleStatus::FIXTURE)
            ->where(function ($query) use ($applicantId) {
              $query->where('home_applicant_id', $applicantId)
                ->orWhere('away_applicant_id', $applicantId);
            })
            ->oldest('started_at')
            ->first();

          $result['match'] = null;
          if (!is_null($scheduleInfo)) {
            $schedule['id'] = $scheduleInfo->id;
            $schedule['started_at'] = $scheduleInfo->started_at->toDateTimeString();
            $home['applicant_id'] = $scheduleInfo->home_applicant_id;
            $away['applicant_id'] = $scheduleInfo->away_applicant_id;

            SimulationApplicantStat::with('applicant.user')
              ->whereIn('applicant_id', [$home['applicant_id'], $away['applicant_id']])
              ->where('league_id', $leagueId)
              ->get()
              ->keyBy('applicant_id')
              ->map(function ($info) use (&$home, &$away, $seasonId) {
                $user['name'] = $info->applicant->user->name;
                $user['rank'] = $info->ranking;
                $user['count_won'] = $info->count_won;
                $user['count_draw'] = $info->count_draw;
                $user['count_lost'] = $info->count_lost;

                // if ($info->applicant_id !== $userStats?->applicant_id) {
                //   $userStats = SimulationApplicantStat::where([
                //     ['applicant_id', $info->applicant_id],
                //     ['season_id', $seasonId]
                //   ])->first();
                // }

                $user['goal_avg'] = $info->goal_avg ?? 0;
                $user['goal_against__avg'] = $info->goal_against_avg ?? 0;
                $user['recent_5_match'] = $info->recent_5_match;

                if ($info->applicant_id === $home['applicant_id']) {
                  $home = array_merge($home, $user);
                } else if ($info->applicant_id === $away['applicant_id']) {
                  $away = array_merge($away, $user);
                }
              });

            $lineupMeta = SimulationLineupMeta::where('schedule_id', $scheduleInfo->id);
            if ($lineupMeta->clone()->exists()) {
              $lineupMeta->get()->map(function ($info) use (&$home, &$away) {
                ${$info->team_side}['formation'] = $info->formation_used;
                $overallAvg = SimulationLineup::where('lineup_meta_id', $info->id)
                  ->with('simulationOverall')
                  ->get()
                  ->map(function ($info) {
                    if ($info->simulationOverall) {
                      $finalOverall = $info->simulationOverall->final_overall;
                      $subPosition = $info->simulationOverall->sub_position;
                      $info->final_overall = $finalOverall ? (int) $finalOverall[$subPosition] : null;
                    }

                    return $info;
                  })
                  ->avg('final_overall');
                ${$info->team_side}['overall_avg'] = round($overallAvg, 1);
              });
            } else {
              SimulationUserLineupMeta::whereIn('applicant_id', [$home['applicant_id'], $away['applicant_id']])
                ->get()
                ->map(function ($info) use (&$home, &$away) {
                  $user['formation'] = $info->formation_used;
                  $overallAvg = SimulationUserLineup::where('user_lineup_meta_id', $info->id)
                    ->with('simulationOverall')
                    ->get()
                    ->map(function ($info) {
                      if ($info->simulationOverall) {
                        $finalOverall = $info->simulationOverall->final_overall;
                        $subPosition = $info->simulationOverall->sub_position;
                        $info->final_overall = $finalOverall ? (int) $finalOverall[$subPosition] : null;
                      }

                      return $info;
                    })
                    ->avg('final_overall');
                  $user['overall_avg'] = round($overallAvg, 1);
                  if ($info->applicant_id === $home['applicant_id']) {
                    $home = array_merge($home, $user);
                  } else if ($info->applicant_id === $away['applicant_id']) {
                    $away = array_merge($away, $user);
                  }
                });
            }

            $schedule['home'] = $home;
            $schedule['away'] = $away;
            $result['match'] = $schedule;
          }
          break;
        case 'team':
          // 2. team
          // 2-1. 사용자/리그 평균 득/실점
          $goals['my'] = $userStats?->goal_avg ?? 0;
          $goals['league'] = $leagueStats?->goal_avg ?? 0;
          $goalAgainsts['my'] = $userStats?->goal_against_avg ?? 0;
          $goalAgainsts['league'] = $leagueStats?->goal_against_avg ?? 0;

          $result['goals'] = $goals;
          $result['goal_againsts'] = $goalAgainsts;

          // 2-2. 선제득점 및 역전 골 승무패 비율
          $winRates['scoring_first_won'] = $userStats?->scoring_first_won_avg ?? 0;
          $winRates['scoring_first_draw'] = $userStats?->scoring_first_draw_avg ?? 0;
          $winRates['scoring_first_lost'] = $userStats?->scoring_first_lost_avg ?? 0;
          $winRates['comeback_won'] = $userStats?->comeback_won_avg ?? 0;
          $winRates['comeback_lost'] = $userStats?->comeback_lost_avg ?? 0;

          $result['win_rates'] = $winRates;

          // 2-3. 경고 및 퇴장 사용 불가 선수 발생 체크
          $yellowCard = $redCard = [];
          SimulationRefCardValidation::with('userPlateCard.plateCardWithTrashed')
            ->whereHas('userPlateCard', function ($query) use ($request) {
              $query->where('user_id', $request->user()->id);
              // $query->where('user_id', 60);
            })->whereNotNull('banned_schedules')
            ->get()
            ->map(function ($info) use (&$yellowCard, &$redCard) {
              $player['id'] = $info->userPlateCard->plateCardWithTrashed->id;
              $player['player_id'] = $info->userPlateCard->plateCardWithTrashed->player_id;
              foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
                $player[$field] = $info->userPlateCard->plateCardWithTrashed->{$field};
              }
              if ($info->yellow_card_count >= 5) {
                $yellowCard[] = $player;
              } else if ($info->red_card_count >= 1) {
                $redCard[] = $player;
              }
            });

          $result['player_issue'] = [
            'red_card' => $redCard,
            'yellow_card' => $yellowCard
          ];
          break;
        case 'stats':
          // 3. stats
          // 3-1. 내 팀에서 골/어시스트/평점 1~3위
          $result['goals'] = $userStats?->best_goal_players ?? 0;
          $result['assists'] = $userStats?->best_assist_players ?? 0;
          $result['rating'] = $userStats?->best_rating_players ?? 0;

          break;
        case 'player':
          // 4. player : 전주월요일00시~일요일00시까지
          // 4-1. 인기 선수 : 플레이트 카드 판매량과 업그레이드 합산수 1~30위 중 랜덤

          // redis 확인
          $redisKeyName = 'simulation_report_players';
          if (Redis::exists($this->getRedisCachingKey($redisKeyName))) {
            $data = json_decode(Redis::get($this->getRedisCachingKey($redisKeyName)), true);
            $popularId = $data['popular'];
            $recommendId = $data['recommend'];
          } else {
            $startTime = now()->subWeek()->startOfWeek();
            $endTime = now()->subWeek()->endOfWeek();

            $sub = UserPlateCard::whereHas('draftComplete', function ($query) use ($startTime, $endTime) {
              $query->whereBetween('created_at', [$startTime, $endTime]);
            })->selectRaw('count(id) as draft_cnt, plate_card_id as pcId')
              ->where('status', DraftCardStatus::COMPLETE)
              ->groupBy('plate_card_id');

            $popularPlayers = OrderPlateCard::leftJoinSub($sub, 'user_plate_card', function ($join) {
              $orderTbl = OrderPlateCard::getModel()->getTable();
              $join->on($orderTbl . '.plate_card_id', 'user_plate_card.pcId');
            })->with(['plateCard:id,player_id'])
              ->selectRaw('sum(quantity) as order_cnt, plate_card_id, (sum(quantity) + user_plate_card.draft_cnt) AS total')
              ->whereBetween('created_at', [$startTime, $endTime])
              ->groupBy('plate_card_id')
              ->orderByDesc('total')
              ->limit(30)
              ->get();
            if (count($popularPlayers) > 0) {
              $popularId = $popularPlayers->random(1)
                ->value('plateCard.player_id');
            }

            // 4-2. 추천 선수 : 판타지 포인트 순위 1~30위 중 랜덤
            $recomPlayers = OptaPlayerDailyStat::whereHas('schedule', function ($query) use ($startTime, $endTime) {
              $query->whereBetween('started_at', [$startTime, $endTime]);
            })->where('season_id', '1jt5mxgn4q5r6mknmlqv5qjh0')
              ->select('player_id')
              ->orderByDesc('fantasy_point')
              ->limit(30)
              ->get();
            if (count($recomPlayers) > 0) {
              $recommendId = $recomPlayers->random(1)
                ->value('player_id');
            }

            if (!isset($popularId) || !isset($recommendId)) {
              $overallPlayers = [];
              $positionCnt = config('simulationpolicies.report_player');
              $overall = RefPlayerOverallHistory::where('season_id', '1jt5mxgn4q5r6mknmlqv5qjh0')->orderByDesc('final_overall');
              foreach ($positionCnt as $position => $cnt) {
                $players = $overall->clone()->where('position', $position)->limit($cnt)->pluck('player_id')->toArray();
                $overallPlayers = array_merge($overallPlayers, $players);
              }

              if (!isset($popularId)) {
                $popularId = $overallPlayers[array_rand($overallPlayers)];
              }
              if (!isset($recommendId)) {
                $recommendId = $this->getPlayer($overallPlayers, $popularId);
              }
            }

            Redis::set($redisKeyName, json_encode(['popular' => $popularId, 'recommend' => $recommendId]), 'EX', 60 * 60);
          }

          PlateCard::whereIn('player_id', [$popularId, $recommendId])
            ->with('currentRefPlayerOverall')
            ->get()
            ->map(function ($info) use (&$result, $popularId, $recommendId) {
              $player['id'] = $info->id;
              $player['player_id'] = $info->player_id;
              $player['position'] = $info->position;
              $player['sub_position'] = $info->currentRefPlayerOverall?->sub_position;
              $player['headshot_path'] = $info->headshot_path;
              $player['final_overall'] = $info->currentRefPlayerOverall?->final_overall;

              foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
                $player[$field] = $info->{$field};
              }

              foreach (config('commonFields.team') as $field) {
                $team[$field] = $info->{'team_' . $field};
              }
              $player['team'] = $team;

              $columns = config('fantasyoverall.column');
              $categoryCntArr = array_count_values($columns);

              foreach ($columns as $column => $category) {
                if (!isset($total[$category]['total'])) $total[$category]['total'] = 0;
                $total[$category]['total'] += $info->currentRefPlayerOverall?->{$column};
                $player[$category . '_avg'] = BigDecimal::of($total[$category]['total'])->dividedBy(BigDecimal::of($categoryCntArr[$category]), 0, RoundingMode::HALF_UP)->toInt();
              }

              if ($info->player_id === $popularId) {
                $result['popular'] = $player;
              } else if ($info->player_id === $recommendId) {
                $result['recommend'] = $player;
              }
            });
          break;
        default:
          return false;
      }

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  private function getPlayer($dataArr, $playerId)
  {
    $key = $dataArr[array_rand($dataArr)];
    if ($playerId !== $key) {
      return $key;
    }
    return $this->getPlayer($dataArr, $playerId);
  }

  public function gameResultCheck(SimulationScheduleRequest $request)
  {
    $filter = $request->only([
      'schedule_id',
      'user_id'
    ]);

    try {
      $lineupMeta = SimulationLineupMeta::whereHas(
        'schedule',
        function ($scheduleQuery) use ($filter) {
          $scheduleQuery->where([
            'id' => $filter['schedule_id'],
            'status' => SimulationScheduleStatus::PLAYED
          ]);
        }
      )
        ->whereHas('applicant', function ($query) use ($filter) {
          $query->where('user_id', $filter['user_id']);
        })
        ->first();

      if (is_null($lineupMeta)) {
        throw new Exception('Invalid Schedule ID');
      }

      if (!$lineupMeta->is_result_checked) {
        $lineupMeta->is_result_checked = true;
        $lineupMeta->save();
      }
      $this->deleteUncheckedGame($filter['schedule_id']);
    } catch (Exception $th) {
      logger($th->getMessage());
      return ReturnData::setError(null, $request)->send(Response::HTTP_BAD_REQUEST);
    }

    return ReturnData::send(Response::HTTP_OK);
  }

  public function gameLineups(SimulationScheduleRequest $request)
  {
    $filter = $request->only([
      'schedule_id',
      'user_id'
    ]);

    $overall = [
      'total' => 0,
      'cnt' => 0,
      'avg' => 0,
    ];
    foreach (PlayerPosition::getAllPositions() as $position) {
      $overall[$position] = [
        'total' => 0,
        'cnt' => 0,
        'avg' => 0,
      ];
    }

    try {
      $scheduleInfo = SimulationSchedule::where('id', $filter['schedule_id'])
        ->with([
          'lastSequenceMeta.refSimulationSequence',
          'home.userLineupMeta',
          'away.userLineupMeta',
        ])
        ->first();

      $result['started_at'] = $scheduleInfo->started_at->toDateTimeString();
      $result['status'] = $scheduleInfo->status;

      $model = SimulationLineup::class;
      $metaRelation = 'lineupMeta';

      // 결과확인 체크
      $lineupMeta = SimulationLineupMeta::whereHas('applicant', function ($query) use ($filter) {
        $query->where('user_id', $filter['user_id']);
      })->where('schedule_id', $filter['schedule_id'])->first();

      $result['is_check_first'] = false;
      if (is_null($lineupMeta)) {
        $model = SimulationUserLineup::class;
        $metaRelation = 'userLineupMeta';
      } else {
        if ($scheduleInfo->status === SimulationScheduleStatus::PLAYED && $lineupMeta->is_result_checked === false) {
          $lineupMeta->is_result_checked = true;
          $lineupMeta->save();

          $result['is_check_first'] = true;
        }
      }

      $teamConfig = config('simulationwdl.additional_team');
      $teamCnt = [];
      $formationConfig = config('formation-by-sub-position');

      // 가장 마지막 경기의 라인업 home/away
      $model::when($metaRelation === 'lineupMeta', function ($query) use ($scheduleInfo) {
        $query->whereHas('lineupMeta', function ($query) use ($scheduleInfo) {
          $query->where('schedule_id', $scheduleInfo->id);
        });
      }, function ($query) use ($scheduleInfo) {
        $query->whereIn('user_lineup_meta_id', [$scheduleInfo->home->userLineupMeta->id, $scheduleInfo->away->userLineupMeta->id]);
      })->with([
        $metaRelation . '.applicant.userRank',
        $metaRelation . '.applicant.user:id,name',
        $metaRelation . '.applicant.user.userMeta:user_id,photo_path',
        'simulationOverall:user_plate_card_id,sub_position,second_position,third_position,final_overall',
        'userPlateCard.draftTeam:' . implode(',', config('commonFields.team')),
        'userPlateCard.draftSeason:id,name,league_id',
        'userPlateCard.draftSeason.league:id,league_code',
        'userPlateCard.draftSelection.schedule' => function ($scheduleQuery) {
          $scheduleQuery->with([
            'home:' . implode(',', config('commonFields.team')),
            'away:' . implode(',', config('commonFields.team')),
          ])->selectRaw('id,home_team_id,away_team_id,score_home,score_away');
        }
      ])
        ->get()
        ->map(function ($info) use (&$teamCnt, &$result, $teamConfig, $scheduleInfo, &$overall, $metaRelation, $formationConfig) {
          // 스태미나, record
          $staminas = null;
          if (count($scheduleInfo->lastSequenceMeta) > 0) {
            if ($scheduleInfo->home_applicant_id === $info->$metaRelation->applicant_id) {
              $staminas = $scheduleInfo->lastSequenceMeta[0]->sequence_events['staminas']['home'];
            } else if ($scheduleInfo->away_applicant_id === $info->$metaRelation->applicant_id) {
              $staminas = $scheduleInfo->lastSequenceMeta[0]->sequence_events['staminas']['away'];
            }

            // dd($scheduleInfo->lastSequenceMeta[0]->refSimulationSequence);
            // record
            foreach (SimulationTeamSide::getValues() as $teamSide) {
              foreach (['possession', 'shots', 'shots_on_target', 'foul', 'cornerkick'] as $stat) {
                ${$teamSide . '_' . $stat} = $scheduleInfo->lastSequenceMeta[0]->refSimulationSequence->{$teamSide . '_' . $stat};
                if ($stat === 'possession') {
                  ${$teamSide . '_' . $stat} = ${$teamSide . '_' . $stat} * 100;
                }
              }
            }
          }

          [, $player] = $this->dataService->getPlayerBaseInfo($info->userPlateCard);
          $player = array_merge(['user_plate_card_id' => $info->user_plate_card_id], $player);
          unset($player['id']);

          $draftSchedule = $info->userPlateCard->draftSelection?->schedule;
          if (!is_null($draftSchedule)) {
            foreach (SimulationTeamSide::getValues() as $teamSide) {
              $draftSchedule[$teamSide]['is_player_team'] = $draftSchedule[$teamSide]['id'] === $player['draft_team']['id'];
            }
          }

          $player['draft_schedule'] = $draftSchedule;
          $player['is_free'] = !is_null($draftSchedule);
          $player['formation_place'] = $info->formation_place;
          $player['rating'] = $info->rating;
          $player['is_game_mom'] = $info->is_mom;
          $player['slot_no'] = $formationConfig['slot_by_place'][$info->$metaRelation->formation_used][$info->formation_place];
          if ($info->formation_place < 12) {
            $player['slot_position'] = $formationConfig['formation_used'][$info->$metaRelation->formation_used][$info->formation_place];
          } else {
            $player['slot_position'] = null;
          }

          $overalls['position'] = $info->simulationOverall->sub_position;
          $overalls['overall'] = (int) $info->simulationOverall->final_overall[$overalls['position']];
          $overallPosition[] = $overalls;
          $overalls = null;

          $secondPosition = $info->simulationOverall->second_position;
          $thirdPosition = $info->simulationOverall->third_position;

          if (!is_null($secondPosition)) {
            $secondOverall = (int) $info->simulationOverall->final_overall[$secondPosition];
            $overalls['position'] = $secondPosition;
            $overalls['overall'] = $secondOverall;
            $overallPosition[] = $overalls;
            $overalls = null;
          }

          if (!is_null($thirdPosition)) {
            $thirdOverall = (int) $info->simulationOverall->final_overall[$thirdPosition];
            $overalls['position'] = $thirdPosition;
            $overalls['overall'] = $thirdOverall;
            $overallPosition[] = $overalls;
          }

          if (isset($secondOverall) && isset($thirdOverall)) {
            if ($secondOverall < $thirdOverall) {
              $temp = $overallPosition[1];
              $overallPosition[1] = $overallPosition[2];
              $overallPosition[2] = $temp;
            }
          }
          $player['overall_position'] = $overallPosition;

          foreach (SimulationCategoryType::getValues() as $category) {
            $player['overall_category'][$category] = $info->userPlatecard->simulationOverall->{$category . '_overall'};
          }

          $player['game_started'] = $info->game_started;
          $player['is_changed'] = false;
          if ($scheduleInfo->status === SimulationScheduleStatus::PLAYED) {
            $player['is_changed'] = $info->is_changed;
          }

          $player['stamina'] = 100;
          if (!is_null($staminas)) {
            $player['stamina'] = $staminas[$info->user_plate_card_id];
          }

          $overall['cnt']++;
          $overall['total'] += $info->userPlateCard->simulationOverall->final_overall[$info->userPlateCard->simulationOverall->sub_position];
          $overall['avg'] = BigDecimal::of($overall['total'])->dividedBy(BigDecimal::of($overall['cnt']), 1, RoundingMode::HALF_UP)->toFloat();

          $overall[$info->position]['cnt']++;
          $overall[$info->position]['total'] += $info->userPlateCard->simulationOverall->final_overall[$info->userPlateCard->simulationOverall->sub_position];
          $overall[$info->position]['avg'] = BigDecimal::of($overall[$info->position]['total'])->dividedBy(BigDecimal::of($overall[$info->position]['cnt']), 1, RoundingMode::HALF_UP)->toFloat();

          // 팀스택
          $teamStack = null;
          if (!isset($teamCnt[$info->userPlateCard->draft_team_id]['all'])) {
            $teamCnt[$info->userPlateCard->draft_team_id]['all'] = 0;
          }
          if (!isset($teamCnt[$info->userPlateCard->draft_team_id]['game_started'])) {
            $teamCnt[$info->userPlateCard->draft_team_id]['game_started'] = 0;
          }
          $teamCnt[$info->userPlateCard->draft_team_id]['all']++;
          $configArr = $teamConfig['all'];
          $myCnt = $teamCnt[$info->userPlateCard->draft_team_id]['all'];
          if ($info->game_started) {
            $configArr = $teamConfig['game_started'];
            $teamCnt[$info->userPlateCard->draft_team_id]['game_started']++;
            $myCnt = $teamCnt[$info->userPlateCard->draft_team_id]['game_started'];
          }

          foreach ($configArr as $standard => $addArr) {
            if ($myCnt >= $standard) {
              $teamStack['add'] = $addArr['add'];
              $teamStack['level'] = $addArr['level'];
            }
          }

          if (!is_null($teamStack)) {
            $result['team_stack'] = array_merge($info->userPlateCard->draft_team_names, $teamStack);
          }
          $photo_path = $info->$metaRelation->applicant->user->userMeta->photo_path;
          $clubName = $info->$metaRelation->applicant->user->name;
          $clubCodeName = $info->$metaRelation->applicant->club_code_name;
          $sub = SimulationApplicantStat::where([
            'league_id' => $scheduleInfo->league_id,
          ])
            ->selectRaw('RANK() over (order by points desc) as ranking, count_won, count_draw, count_lost, applicant_id
            ');

          $userRank = DB::query()->fromSub($sub, 's')
            ->where('applicant_id', $info->$metaRelation->applicant_id)
            ->first();
          $ranking = $userRank->ranking;
          $countWon = $userRank->count_won;
          $countDraw = $userRank->count_draw;
          $countLost = $userRank->count_lost;
          if ($info->$metaRelation->applicant_id === $scheduleInfo->home_applicant_id) {
            $result['home_score'] = $info->$metaRelation->score;
            $result['home']['user_id'] = $info->$metaRelation->applicant->user_id;
            $result['home']['lineup_meta_id'] = $info->lineup_meta_id;
            $result['home']['photo_path'] = $photo_path;
            $result['home']['club_name'] = $clubName;
            $result['home']['club_code_name'] = $clubCodeName;
            $result['home']['rank'] = $ranking;
            $result['home']['count_won'] = $countWon;
            $result['home']['count_draw'] = $countDraw;
            $result['home']['count_lost'] = $countLost;
            $result['home']['formation_used'] = $info->$metaRelation->formation_used;
            $result['home']['substitution_count'] = $info->$metaRelation->substitution_count;
            $result['home']['playing_style'] = $info->$metaRelation->playing_style;
            $result['home']['defensive_line'] = $info->$metaRelation->defensive_line;
            $result['home']['overall']['club'] = $overall['avg'];
            $result['home']['overall']['position'][$info->position] = $overall[$info->position]['avg'];
            $result['home']['team_stack'] = $teamStack;
            $result['home']['record']['possession'] = $home_possession ?? 50;
            $result['home']['record']['shots'] = $home_shots ?? 0;
            $result['home']['record']['shots_on_target'] = $home_shots_on_target ?? 0;
            $result['home']['record']['foul'] = $home_foul ?? 0;
            $result['home']['record']['cornerkick'] = $home_cornerkick ?? 0;
            $result['home']['lineup'][] = $player;
          } else if ($info->$metaRelation->applicant_id === $scheduleInfo->away_applicant_id) {
            $result['away_score'] = $info->$metaRelation->score;
            $result['away']['user_id'] = $info->$metaRelation->applicant->user_id;
            $result['away']['lineup_meta_id'] = $info->lineup_meta_id;
            $result['away']['photo_path'] = $photo_path;
            $result['away']['club_name'] = $clubName;
            $result['away']['club_code_name'] = $clubCodeName;
            $result['away']['rank'] = $ranking;
            $result['away']['count_won'] = $countWon;
            $result['away']['count_draw'] = $countDraw;
            $result['away']['count_lost'] = $countLost;
            $result['away']['formation_used'] = $info->$metaRelation->formation_used;
            $result['away']['substitution_count'] = $info->$metaRelation->substitution_count;
            $result['away']['playing_style'] = $info->$metaRelation->playing_style;
            $result['away']['defensive_line'] = $info->$metaRelation->defensive_line;
            $result['away']['overall']['club'] = $overall['avg'];
            $result['away']['overall']['position'][$info->position] = $overall[$info->position]['avg'];
            $result['away']['team_stack'] = $teamStack;
            $result['away']['record']['possession'] = $away_possession ?? 50;
            $result['away']['record']['shots'] = $away_shots ?? 0;
            $result['away']['record']['shots_on_target'] = $away_shots_on_target ?? 0;
            $result['away']['record']['foul'] = $away_foul ?? 0;
            $result['away']['record']['cornerkick'] = $away_cornerkick ?? 0;
            $result['away']['lineup'][] = $player;
          }
          return $result;
        });

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function getCommentary(SimulationScheduleRequest $request)
  {
    $filter = $request->only(['schedule_id']);

    try {
      $result = [];

      SimulationStep::with([
        'sequenceMeta.refSimulationSequence',
        'commentaryTemplate:id,name,timeline,comment',
      ])
        ->whereHas('sequenceMeta', function ($query) use ($filter) {
          $query->where('schedule_id', $filter['schedule_id']);
        })
        ->orderBy('id')
        ->get()
        ->map(function ($item) use (&$result) {
          if (!is_null($item->commentaryTemplate) && !is_null($item->ref_params)) {
            $cTemplate = $item->commentaryTemplate->comment;
            $cParams = $item->ref_params['comment'] ?? [];

            preg_match_all('/\{\{([^\}\}]+)\}\}/', $cTemplate);
            $cTemplate = __pregReplacement('/\{\{([^\}\}]+)\}\}/', $cParams, $cTemplate);

            $result[] = [
              'step_id' => $item->id,
              'playing_seconds' => $item->playing_seconds,
              'nth_half' => $item->sequenceMeta->refSimulationSequence->nth_half,
              'name' => $item->commentaryTemplate->name,
              'timeline' => $item->commentaryTemplate->timeline,
              'comment' => $cTemplate,
              'ref_infos' => $item->ref_params['ref_infos'] ?? [],
            ];
          }
        });

      return ReturnData::setData($result, $request)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());

      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function getUncheckedGame(Request $request)
  {
    try {
      $uncheckedGame = $this->getUncheckedGames($request->user()->id);
      return ReturnData::setData($uncheckedGame)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());

      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function getUserRankConclusion(ValidApplicantRequest $request)
  {
    try {
      $applicant = SimulationApplicant::where('user_id', $request->user()->id)->first();

      $latestEndedSeasonId = SimulationSeason::currentSeasons(false)
        ->where('server', $applicant->server)
        ->latest('first_started_at')
        ->value('id');

      $userRank = SimulationUserRank::whereHas('league', function ($seasonQuery) use ($latestEndedSeasonId) {
        $seasonQuery->where('season_id', $latestEndedSeasonId);
      })
        ->where('applicant_id', $applicant->id)
        ->first();

      //컨펌 해야할 데이터가 없는 경우
      if (is_null($userRank) || $userRank->is_confirm === true) {
        return ReturnData::send(Response::HTTP_OK);
      }

      $result = [
        'id' => $userRank->id,
        'applicant_id' => $userRank->applicant_id,
        'ranking' => $userRank->ranking,
        'status' => $userRank->status,
        'season_id' => $userRank->league->season_id,
        'league_id' => $userRank->league_id,
        'league_no' => $userRank->league->league_no,
        'division_id' => $userRank->league->division_id,
        'division_no' => $userRank->league->division->division_no,
        'tier_id' => $userRank->league->division->tier_id,
        'tier_level' => $userRank->league->division->tier->level,
        'tier_name' => $userRank->league->division->tier->name,
      ];

      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());

      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function userRankConfirmCheck(RankConfirmCheckRequest $request)
  {
    $filter = $request->only([
      'user_id',
      'id'
    ]);

    DB::beginTransaction();
    try {
      $userRank = SimulationUserRank::whereHas('applicant', function ($query) use ($filter) {
        $query->where('user_id', $filter['user_id']);
      })
        ->where([
          ['id', $filter['id']],
          ['is_confirm', false]
        ])
        ->first();

      if (is_null($userRank)) {
        throw new Exception('No data to confirm');
      }

      $userRank->is_confirm = true;
      $userRank->save();

      DB::commit();
    } catch (Exception $th) {
      DB::rollBack();

      return ReturnData::setError($th->getMessage(), $request)->send(Response::HTTP_BAD_REQUEST);
    }

    return ReturnData::send(Response::HTTP_OK);
  }
}
