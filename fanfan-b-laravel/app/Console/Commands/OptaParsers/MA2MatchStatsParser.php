<?php

namespace App\Console\Commands\OptaParsers;

use App\Enums\Admin\GameStatus;
use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\FantasyCalculator\FantasyPointCategoryType;
use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Card\OriginGrade;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Schedule\ScheduleWinnerStatus;
use App\Enums\Opta\Season\SeasonNameType;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\ParserMode;
use App\Exceptions\Custom\Parser\OTPInsertException;
use App\Libraries\Classes\FantasyCalculator;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\Schedule;
use App\Models\data\OptaTeamDailyStat;
use App\Models\data\Season;
use App\Models\game\PlayerDailyStat;
use App\Models\game\TeamDailyStat;
use App\Models\meta\RefCardcQuantile;
use App\Models\data\Substitution;
use App\Models\data\EventCard;
use App\Models\data\EventGoal;
use Carbon\Carbon;
use DB;
use Exception;
use LogEx;

// https://api.performfeeds.com/soccerdata/matchstats/1vmmaetzoxkgg1qf6pkpfmku0k/ew92044hbj98z55l8rxc6eqz8?_fmt=json&_rt=b&detailed=yes
// goals의 scorerId 또는 assistPlayerId 가 players 테이블에 없는 경우가 있는 듯. 확인할 것. 또는 Foreign key로 처리하고 테스트해볼 것.
class MA2MatchStatsParser extends BaseOptaParser
{
  use FantasyMetaTrait;
  protected const REQUEST_COUNT_AT_ONCE = 20;
  protected const playerDailySpecifiedKey = 'playerFp';

  // public array $param = [
  //   'mode' => 'all'
  // ];

  protected $refCardCQuantileBySeasonId;

  protected $refCardCQuantileByLeagueId;

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'matchstats';
    $this->feedNick = 'MA2';
    $this->refCardCQuantileBySeasonId = RefCardcQuantile::whereHas('playingSeason', function ($query) {
      return $query->whereIn(
        'id',
        Season::idsOf([SeasonWhenType::BEFORE, SeasonWhenType::CURRENT], SeasonNameType::ALL, 3) // 3년 이전까지의 시즌에 대해서 가져온다.
      );
    })->get()->groupBy(['playing_season_id', 'summary_position'])
      ->map(function ($item) {
        foreach ($item as $position => $infos) {
          $item[$position] = $infos[0];
        }
        return $item;
      })->toArray();
    $this->refCardCQuantileByLeagueId = RefCardcQuantile::whereHas('playingSeason', function ($query) {
      return $query->whereIn(
        'id',
        Season::idsOf([SeasonWhenType::BEFORE, SeasonWhenType::CURRENT], SeasonNameType::ALL, 3) // 3년 이전까지의 시즌에 대해서 가져온다.
      );
    })->get()->groupBy(['league_id', 'summary_position'])
      ->map(function ($item) {
        foreach ($item as $position => $infos) {
          $item[$position] = $infos[0];
        }
        return $item;
      })->toArray();
  }

  protected function customParser($_parentKey, $_key, $_value)
  {
    if ($_key === 'lineUp') {
      $this->playerParseOnLineupSnippet($_value);
      $this->teamStatParserOnLineupSnippet($_value);
      // } else if ($_key === 'card') {
      //   foreach ($_value as $playerAttrs) {
      //     if (!isset($playerAttrs['teamOfficialId'])) {
      //       $this->appendTargetSpecifiedAttrsByIndex(
      //         'card',
      //         $playerAttrs['contestantId'] . '_' . $playerAttrs['playerId'],
      //         $playerAttrs
      //       );
      //     }
      //   }
    } else if (in_array($_key, ['goal', 'card', 'substitute'])) {
      foreach ($_value as $idx => $item) {
        $item['slot'] = $idx;
        $this->appendTargetSpecifiedAttrsByIndex(
          $_key,
          $idx,
          $item,
        );
      }
    }
  }

  protected function customCommonParser($_parentKey, $_key, $_value)
  {
    // $this->attrExtractor(
    //   $_value,
    //   $this->correctKeyName($_parentKey, $_key),
    //   true,
    // );
  }

  protected function getCardCGrade($_score, $_summaryPosition, $_actingSeasonId, $_actingLeagueId): string|null
  {
    // if ($_actingLeagueId === config('constant.LEAGUE_CODE.UCL')) {
    //   return CardGrade::NONE;
    // } else 
    if (isset($this->refCardCQuantileBySeasonId[$_actingSeasonId])) {
      $qunatileTable = $this->refCardCQuantileBySeasonId;
      $key = $_actingSeasonId;
    } else {
      $qunatileTable = $this->refCardCQuantileByLeagueId;
      $key = $_actingLeagueId;
    }
    foreach (array_reverse(OriginGrade::getValues()) as $gradeSuffix) {
      $quantilePoint = $qunatileTable[$key][$_summaryPosition]['quantile' . '_' . $gradeSuffix];
      if ($_score <= $quantilePoint) {
        return config('constant.GRADE_ORIGIN_TO_CARD')[$gradeSuffix];
      }
    }
  }

  protected function applyMOM($_datas)
  {
    // mom
    $commonRowOrigin = $_datas['commonRowOrigin'];
    if (
      $commonRowOrigin['status'] === ScheduleStatus::PLAYED ||
      $commonRowOrigin['status'] === ScheduleStatus::AWARDED
    ) {
      $scheduleId = $commonRowOrigin['schedule_id'];
      $winnerSide = ($commonRowOrigin['winner'] === ScheduleWinnerStatus::DRAW) ? null : $commonRowOrigin['winner'];
      $winTeamId = $winnerSide ? Schedule::withUnrealSchedule()->whereId($scheduleId)->value(sprintf('%s_team_id', $winnerSide)) : null;

      $momOptaStatsQuery = OptaPlayerDailyStat::where('schedule_id', $scheduleId)
        ->when($winnerSide, function ($query) use ($winTeamId) {
          $query->where('team_id', $winTeamId)->selectRaw(
            'player_id,
            fantasy_point as a,
            goals + goal_assist as b, 
            winning_goal as c, 
            penalty_save as d, 
            ontarget_scoring_att as e,
            saves as f, 
            total_scoring_att as g, 
            total_att_assist as h, 
            duel_won as i'
          );
        }, function ($query) {
          $query->selectRaw(
            'player_id,
            fantasy_point as a,
            penalty_save as b, 
            ontarget_scoring_att as c, 
            saves as d, 
            total_scoring_att as e, 
            total_att_assist as f, 
            duel_won g,
            0 as h,
            0 as i'
          );
        });
      $momStats = $momOptaStatsQuery->clone()->get()->toArray();
      $momPlayerId = __sortByKeys($momStats, ['keys' => ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i'], 'hows' => ['desc']])[0]['player_id'];

      $momOptaStatsQuery->where('player_id', $momPlayerId)->update(['is_mom' => true]);

      $momPlayer = PlayerDailyStat::where([
        ['schedule_id', $scheduleId],
        ['player_id', $momPlayerId],
      ])->first();
      $momPlayer->is_mom = true;
      $momPlayer->save();
    }
  }

  protected function isCancelStatusOnStatus($_status): bool
  {
    if (
      $_status === ScheduleStatus::SUSPENDED ||
      $_status === ScheduleStatus::CANCELLED ||
      $_status === ScheduleStatus::POSTPONED
    ) {
      return true;
    }
    return false;
  }

  protected function correctSomeStats(&$_datas)
  {
    $currentStatus = $_datas['commonRowOrigin']['status'];
    $specifiedAttrs = $_datas['specifiedAttrs'];
    if ($currentStatus !== GameStatus::PLAYED) {
      return;
    }

    $conf = config('constant.CORRECT_SOME_PLAYER_STATS');
    $substitutions = $specifiedAttrs['substitute'];
    $cards = $specifiedAttrs['card'];
    $refs = [];
    foreach ($substitutions as $subs) {
      $refs[$subs['player_on_id']]['time_min'] = $subs['time_min'];
      $refs[$subs['player_on_id']]['period_id'] = $subs['period_id'];
      $refs[$subs['player_off_id']]['time_min_after'] = $subs['time_min'];
      $refs[$subs['player_off_id']]['period_id'] = $subs['period_id'];
    }

    foreach ($cards as $card) {
      if ($card['type'] === 'Y2C' || $card['type'] === 'RC') {
        $refs[$card['player_id']]['time_min_card'] = $card['time_min'];
        $refs[$card['player_id']]['period_id'] = $card['period_id'];
      }
    }
    logger($refs);

    if (isset($specifiedAttrs['player'])) {
      foreach ($specifiedAttrs['player'] as $playerId => $attrs) {
        logger($playerId);
        if (isset($refs[$playerId])) {
          $topping = '';
          foreach ($conf['mp_topping_order'] as $toppoingOne) {
            $topping .= $attrs[$toppoingOne] ?? '0';
          }
          // $topping = ($attrs['game_started'] ?? 0) .
          //   ($attrs['total_sub_off'] ?? 0) .
          //   ($attrs['total_sub_on'] ?? 0) .
          //   ($attrs['red_card'] ?? 0) .
          //   ($attrs['second_yellow'] ?? 0);
          if ($topping === '00000') continue;
          $expression = $conf['mp_topping_map']['period_id' . '_' . $refs[$playerId]['period_id']][$topping];
          preg_match_all('/\{\{([^\}\}]+)\}\}/', $expression, $datasetMatches);
          $minsPlayed = eval(__pregReplacement('/\{\{([^\}\}]+)\}\}/', $refs[$playerId], $expression));
          // logger($playerId);
          // logger($topping);
          // logger('mins_played_origin:' . $_datas['specifiedAttrs']['player'][$playerId]['mins_played']);
          // logger('mins_played:' . $minsPlayed);
          // logger('clean_sheet:' . $_datas['specifiedAttrs']['player'][$playerId]['clean_sheet'] ?? 'x');
          // logger('goal_conceded:' . $_datas['specifiedAttrs']['player'][$playerId]['goals_conceded'] ?? 'x');
          $_datas['specifiedAttrs']['player'][$playerId]['mins_played'] = $minsPlayed;

          $topping = '';
          foreach ($conf['cs_topping_order'] as $toppoingOne) {
            $topping .= $attrs[$toppoingOne] ?? '0';
          }
          logger('_' . $topping);
          $_datas['specifiedAttrs']['player'][$playerId]['clean_sheet'] = $conf['sh_topping_map'][$topping] ?? 0;
        }
      }
    }
  }

  protected function middleCalPointProcess(array $_datas): array
  {
    /**
     * @var FantasyCalculator $fpCalculator 
     */
    $fpCalculator = app(FantasyCalculatorType::FANTASY_POINT, [0]);

    /**
     * @var FantasyCalculator $ratingCalculator 
     */
    $ratingCalculator = app(FantasyCalculatorType::FANTASY_RATING, [0]);

    /**
     * @var FantasyCalculator $fCardGradeCalculator 
     */
    $fCardGradeCalculator = app(FantasyCalculatorType::FANTASY_CARD_GRADE, [0]);


    /**
     * @var FantasyCalculator $powerRankingCalculator 
     */
    $powerRankingCalculator = app(FantasyCalculatorType::FANTASY_POWER_RANKING, [0]);

    $this->correctSomeStats($_datas);

    $commonRowOrigin = $_datas['commonRowOrigin'];
    $specifiedAttrs = $_datas['specifiedAttrs'];
    $currentStatus = $_datas['commonRowOrigin']['status'];

    if (isset($specifiedAttrs['player'])) {
      foreach ($specifiedAttrs['player'] as $key => $attrs) {
        $specifiedAttrs[self::playerDailySpecifiedKey][$key]['team_id'] = $specifiedAttrs['player'][$key]['team_id'];
        $specifiedAttrs[self::playerDailySpecifiedKey][$key]['player_id'] = $specifiedAttrs['player'][$key]['player_id'];
        $fantasyPoint = 0.0;
        // 판타지 포인트 set 계산
        $FpPartialPoints = [];

        // -> 옵타 disappear stats 대비 player_daily_stats 기본 0 초기화
        foreach ($fpCalculator->getCombRepresentationNames() as $colName) {
          $specifiedAttrs[self::playerDailySpecifiedKey][$key][$colName] = 0;
        }

        foreach ($fpCalculator->makePointSet($attrs) as $category => $valPoints) {
          $FpPartialPoints[$category] = 0;
          foreach ($valPoints as $name => $val) {
            $specifiedAttrs[self::playerDailySpecifiedKey][$key][$name] = $val;
            $FpPartialPoints[$category] += $val;
            // $fantasyPoint += $val; 
            // 계산을 줄이기 위해 calculate 메소드 호출을 하지 않고 Point Set의 값들을 누적하여 계산한다. 벤치플레이어 0으로 처리할 필요가 있음.
          }
        }

        $fantasyPoint = $this->isCancelStatusOnStatus($currentStatus) ? 0 : $fpCalculator->calculate($attrs);
        // logger($fantasyPoint);

        $rating = $this->isCancelStatusOnStatus($currentStatus) ? 0 : $ratingCalculator->calculate($attrs);
        $summaryPosition = $ratingCalculator->getPositionSummary($attrs);
        $specifiedAttrs['player'][$key]['fantasy_point'] = __setDecimal($fantasyPoint, $fpCalculator->getFpPrecision());
        $specifiedAttrs['player'][$key]['rating'] = $rating;
        $specifiedAttrs['player'][$key]['summary_position'] = $summaryPosition;
        $specifiedAttrs[self::playerDailySpecifiedKey][$key]['position'] =  $specifiedAttrs['player'][$key]['position'] ?? null;
        $specifiedAttrs[self::playerDailySpecifiedKey][$key]['sub_position'] = $specifiedAttrs['player'][$key]['sub_position'] ?? null;
        $specifiedAttrs[self::playerDailySpecifiedKey][$key]['formation_place'] = $specifiedAttrs['player'][$key]['formation_place'] ?? 0;
        $specifiedAttrs[self::playerDailySpecifiedKey][$key]['summary_position'] = $summaryPosition;
        $specifiedAttrs[self::playerDailySpecifiedKey][$key]['game_started'] = $specifiedAttrs['player'][$key]['game_started'] ?? false;
        $specifiedAttrs[self::playerDailySpecifiedKey][$key]['total_sub_on'] = $specifiedAttrs['player'][$key]['total_sub_on'] ?? false;
        $specifiedAttrs[self::playerDailySpecifiedKey][$key]['fantasy_point'] = $fantasyPoint;
        $specifiedAttrs[self::playerDailySpecifiedKey][$key][FantasyPointCategoryType::GENERAL . '_point'] = $FpPartialPoints[FantasyPointCategoryType::GENERAL];
        $specifiedAttrs[self::playerDailySpecifiedKey][$key][FantasyPointCategoryType::OFFENSIVE . '_point'] = $FpPartialPoints[FantasyPointCategoryType::OFFENSIVE];
        $specifiedAttrs[self::playerDailySpecifiedKey][$key][FantasyPointCategoryType::DEFENSIVE . '_point'] = $FpPartialPoints[FantasyPointCategoryType::DEFENSIVE];
        $specifiedAttrs[self::playerDailySpecifiedKey][$key][FantasyPointCategoryType::PASSING . '_point'] = $FpPartialPoints[FantasyPointCategoryType::PASSING];
        $specifiedAttrs[self::playerDailySpecifiedKey][$key][FantasyPointCategoryType::DUEL . '_point'] = $FpPartialPoints[FantasyPointCategoryType::DUEL];
        $specifiedAttrs[self::playerDailySpecifiedKey][$key][FantasyPointCategoryType::GOALKEEPING . '_point'] = $FpPartialPoints[FantasyPointCategoryType::GOALKEEPING];
        $specifiedAttrs[self::playerDailySpecifiedKey][$key]['rating'] = $rating;

        // Played된 경기만 계산(Awarded 등은 power_raking이 NULL로 유지)
        if ($commonRowOrigin['status'] === ScheduleStatus::PLAYED) {
          if (isset($attrs['game_started']) || isset($attrs['total_sub_on'])) { // 변수, key 오타 주의

            // rating 포인트 set 계산
            $ratingSet = [];
            foreach ($ratingCalculator->makePointSet($attrs) as $category => $values) {
              $ratingSet[$category] = array_sum($values);
            }

            $threeC = $fCardGradeCalculator->calculate(array_merge($attrs, [
              'season_name' => $commonRowOrigin['season_name'],
              'fantasy_point' => $fantasyPoint,
              'rating_set_sum' => $ratingSet,
            ]));
            $specifiedAttrs[self::playerDailySpecifiedKey][$key]['point_c'] = $threeC['point_c'];
            $specifiedAttrs[self::playerDailySpecifiedKey][$key]['rating_c'] = $threeC['rating_c'];
            $specifiedAttrs[self::playerDailySpecifiedKey][$key]['card_c'] = $threeC['card_c'];
            if ($this->feedNick === 'MA2') { // feedNick SDC 수집시 card c quantile에 이 리그의 정보가 없으므로 계산할 수 없다.
              $cardGrade = $this->getCardCGrade($threeC['card_c'], $summaryPosition, $commonRowOrigin['season_id'], $commonRowOrigin['league_id']);
              $specifiedAttrs[self::playerDailySpecifiedKey][$key]['card_grade'] = $cardGrade;
            }
            $attrs['schedule_id'] = $commonRowOrigin['schedule_id'];
            $attrs['winner'] = $commonRowOrigin['winner']; // db의 scehdules 테이블이 과거데이터일 경우 winner가 NULL일 수 있으므로 방금 파싱된 따끈한 winner 주입.
            $specifiedAttrs['player'][$key]['power_ranking'] = $powerRankingCalculator->calculate($attrs); // 파워랭킹
          } else {
            $specifiedAttrs['player'][$key]['power_ranking'] = 0; // Played된 경기에서 출전하지 않은 선수의 파워랭킹 0으로
          }
        }
      }
    }

    // logger($specifiedAttrs[self::playerDailySpecifiedKey]);
    return [
      'commonRowOrigin' => $commonRowOrigin,
      'specifiedAttrs' => $specifiedAttrs,
    ];
  }

  protected function insertOptaDatasToTables(
    array $_responses,
    array $_commonInfoToStore = null,
    array $_specifiedInfoToStore = null,
    $_realStore = false,
  ): void {
    foreach ($_responses as $urlKey => $response) { // 비동기 응답s 처리
      $datas = $this->middleCalPointProcess($this->preProcessResponse($urlKey, $response));

      // data 체크->
      if (!$_realStore) {
        logger($datas['commonRowOrigin']);
        logger($datas['specifiedAttrs']);
        $this->generateColumnNames();
        dd('-xTestx-');
      }
      // data 체크<-

      DB::beginTransaction();
      try {
        $this->insertDatas($_commonInfoToStore, $_specifiedInfoToStore, $datas);
        $this->applyMOM($datas);
        DB::commit();
      } catch (Exception $e) {
        DB::rollBack();
        report(new OTPInsertException(null, ['feed_nick' => $this->feedNick, 'path' => $this->current_url_extra,], $e, $_specifiedInfoToStore));
      }
    }
  }


  private function getDailyIds(): array
  {
    // 재수집 x, 현재시즌, 수집안된 1일이전(오늘 라이브 제외) 과거 경기
    return Schedule::whereHas('league', function ($query) {
      return $query->withoutGlobalScopes()->parsingAvalilable();
    })->whereHas('season', function ($query) {
      $query->currentSeasons();
    })->where('started_at', '<', Carbon::now()->subDay()) // 적어도 경기 종료 하루 이상된 경기(옵타 데이터 변경 영향 최소화)
      ->doesntHave('oneOptaPlayerDailyStat') // 재수집 금지
      // 스탯이 쌓이는 매치 상태만
      ->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
      // ->whereNotIn('status', [ScheduleStatus::PLAYING]) // LIVE 스케쥴러에서 수집하므로 제외
      ->pluck('id')
      ->toArray();
  }

  private function getAllIds(): array
  {
    // 스탯이 쌓이는 매치 상태만, 재수집 x
    return Schedule::whereHas('league', function ($query) {
      return $query->withoutGlobalScopes()->parsingAvalilable();
    })
      // ->doesntHave('oneOptaPlayerDailyStat') // 재수집 금지
      // 스탯이 쌓이는 매치 상태만
      ->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
      ->oldest('started_at')->pluck('id')->toArray();
  }

  // private function getLiveIds(): array
  // {
  //   return Schedule::where('status', ScheduleStatus::PLAYING)
  //     ->pluck('id')->toArray();
  // }

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
        }
        # code...
        break;
      default:
        # code...
        break;
    }

    // optaParser 설정 -->>
    $this->setKeysToIgnore([
      // 'matchDetails',
      // 'goal', 
      // 'card', 
      // 'substitute', 
      'VAR',
      // 'lineUp', // player
      // 'matchDetailsExtra',
      // 'contestant'
    ]);
    $this->setKGsToCustom(['matchInfo/contestant', 'liveData/lineUp', 'liveData/goal', 'liveData/card', 'liveData/substitute']);
    // $this->setGlueChildKeys([]);
    // optaParser 설정 <<--
    $this->setKeyNameTransMap(['matchStatus' => 'status', 'matchInfoId' => 'matchId', 'touches' => 'touchesOpta']);

    // $match_ids = ['a9044tbpv83gxramw9iovn7ro'];
    __loggerEx($this->feedType, 'schedule total count : ' . count($ids));

    $idChunks = array_chunk($ids, self::REQUEST_COUNT_AT_ONCE);
    $totalChucks = count($idChunks);
    foreach ($idChunks as $idx => $idChunk) {
      if (isset($this->param['chunk']) && $idx < $this->param['chunk']) {
        continue;
      }

      __loggerEx($this->feedType, 'loop $i : ' . $idx . ' / ' . $totalChucks);

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
          // [
          //   'specifiedTableMap' => ['penaltyShot' => 'penalty_shots'],
          //   'conditions' => ['matchId', 'playerId', 'timeMinSec']
          // ],
          // [
          //   'specifiedTableMap' => ['goal' => 'goals'],
          //   'conditions' => ['matchId', 'scorerId', 'timeMinSec']
          // ],
          // [
          //   'specifiedTableMap' => ['card' => 'cards'],
          //   'conditions' => ['matchId', 'playerId', 'timeMinSec']
          //   // timestamp 정보가 없을 수 있음.
          // ],
          [
            'specifiedInfoMap' => ['substitute' => Substitution::class],
            'conditions' => ['schedule_id', 'slot'],
          ],
          [
            'specifiedInfoMap' => ['goal' => EventGoal::class],
            'conditions' => ['schedule_id', 'opta_event_id'],
          ],
          [
            'specifiedInfoMap' => ['card' => EventCard::class],
            'conditions' => ['schedule_id', 'opta_event_id'],
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
