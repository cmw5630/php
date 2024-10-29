<?php

namespace App\Console\Commands\DataControll;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Season\SeasonNameType;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\data\SeasonTeam;
use App\Models\meta\RefTeamCurrentMeta;
use App\Models\meta\RefTeamFormationMap;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DB;
use Exception;

class TeamMainFormationUpdator
{
  use FantasyMetaTrait;

  protected $feedNick;

  protected $teamId = null;
  protected $resetBool = true;
  protected $seasonTeam = [];
  protected $defaultCnt = [
    'cnt_1' => 0,
    'cnt_2' => 0,
    'cnt_3' => 0,
    'cnt_4' => 0,
    'cnt_5' => 0,
    'cnt_6' => 0,
    'cnt_7' => 0,
    'cnt_8' => 0,
    'cnt_9' => 0,
    'cnt_10' => 0,
    'cnt_11' => 0,
    'deleted_at' => null
  ];

  public function __construct(null|string $_teamId = null)
  {
    $this->teamId = $_teamId;
    $this->feedNick = 'TMFU';
  }

  private function baseUpdate($_seasonIds)
  {
    if (is_null($this->teamId)) {
      $_seasonIds = ['9n12waklv005j8r32sfjj2eqc'];
      $seasonQuery = Season::select(['id', 'start_date'])
        ->whereIn('id', $_seasonIds)
        ->has('league');

      $seasonTeamTbl = SeasonTeam::getModel()->getTable();
      $latestSeasons = SeasonTeam::joinSub($seasonQuery, 'season', function ($join) use ($seasonTeamTbl) {
        $join->on($seasonTeamTbl . '.season_id', 'season.id');
      })
        ->selectRaw('team_id, season_id, ROW_NUMBER() OVER(PARTITION BY team_id ORDER BY start_date DESC) AS ranking')
        ->where('start_date', '<', now());

      $this->seasonTeam = DB::query()
        ->fromSub($latestSeasons, 's')
        ->where('ranking', 1)
        ->get()
        ->pluck('season_id', 'team_id')
        ->toArray();
      // $this->seasonTeam = ['a3nyxabgsqlnqfkeg41m6tnpp' => '9n12waklv005j8r32sfjj2eqc'];
    } else {
      $seasonId = Season::whereHas('seasonTeam', function ($query) {
        $query->where('team_id', $this->teamId);
      })->where('start_date', '<', now())
        ->has('league')
        ->orderByDesc('start_date')
        ->value('id');
      $this->seasonTeam = [$this->teamId => $seasonId];
    }
  }

  // ref_team_formation_maps 업데이트
  private function mapTableUpdate($datas)
  {
    foreach ($datas as $player => $data) {
      if ($this->resetBool) {
        RefTeamFormationMap::withTrashed()->where('team_id', $data['team_id'])->delete();
        $this->resetBool = false;
      }
      RefTeamFormationMap::withTrashed()->updateOrCreateEx(
        ['player_id' => $player],
        $data,
        false,
        true,
      );
    }
  }

  // 메인 포메이션 계산
  private function formationUpdate()
  {
    try {
      foreach ($this->seasonTeam as $teamId => $seasonId) {
        $formation = [];
        Schedule::where('season_id', $seasonId)
          ->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
          ->where(function ($query) use ($teamId) {
            $query->where('home_team_id', $teamId)
              ->orWhere('away_team_id', $teamId);
          })->orderBy('started_at')
          ->get()
          ->map(function ($info) use (&$formation, $teamId) {
            if ($info->home_team_id === $teamId) {
              $status = 'home';
            } else {
              $status = 'away';
            }
            $formation['last'] = $info->{$status . '_formation_used'};
            if (array_key_exists($info->{$status . '_formation_used'}, $formation)) {
              $formation[$info->{$status . '_formation_used'}]++;
            } else {
              $formation[$info->{$status . '_formation_used'}] = 1;
            }
          });

        if (count($formation) > 0) {
          $tempValue = 0;
          $tempKey = null;
          foreach ($formation as $key => $value) {
            if ($key === 'last') continue;

            if ($value > $tempValue) {
              $tempValue = $value;
              $tempKey = $key;
            } else if ($value == $tempValue) {
              $tempValue = $value;
              if ($tempKey === $formation['last']) {
                $tempKey = $key;
              }
            }
          }

          RefTeamCurrentMeta::withTrashed()->updateOrCreateEx(
            [
              'team_id' => $teamId,
              'season_id' => $seasonId
            ],
            [
              'main_formation_used' => $tempKey
            ],
            false,
            true,
          );
        }
      }
    } catch (Exception $th) {
      dd($th);
    }
  }

  // position 별 count 계산
  private function mapUpdate()
  {
    try {
      $players = [];
      logger('mapUpdate start');

      $mainTeamFormations = RefTeamCurrentMeta::whereIn('team_id', array_keys($this->seasonTeam))
        ->get()
        ->pluck('main_formation_used', 'team_id');

      foreach ($this->seasonTeam as $teamId => $seasonId) {
        logger(sprintf('team_id : %s, season_id : %s', $teamId, $seasonId));

        if (!isset($mainTeamFormations[$teamId])) continue;
        $mainTeamFormation = $mainTeamFormations[$teamId];

        // scheduleId 뽑기
        $scheduleIds = [];
        Schedule::where('season_id', $seasonId)
          ->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
          ->where(function ($query) use ($teamId) {
            $query->where('home_team_id', $teamId)
              ->orWhere('away_team_id', $teamId);
          })->where(function ($query) use ($mainTeamFormation) {
            $query->where('home_formation_used', $mainTeamFormation)
              ->orWhere('away_formation_used', $mainTeamFormation);
          })->selectRaw('id, 
        CASE 
          WHEN home_team_id="' . $teamId . '" AND home_formation_used = ' . $mainTeamFormation . ' THEN home_formation_used 
          WHEN away_team_id="' . $teamId . '" AND away_formation_used = ' . $mainTeamFormation . ' THEN away_formation_used 
          ELSE 0 
        END AS formation_used')
          ->get()
          ->map(function ($info) use (&$scheduleIds) {
            if ($info->formation_used > 0) {
              $scheduleIds[] = $info->id;
            }
          });

        if (count($scheduleIds) < 1) {
          continue;
        }

        OptaPlayerDailyStat::gameParticipantPlayer()
          ->with('plateCard')
          ->has('plateCard')
          ->where([
            ['season_id', $seasonId],
            ['team_id', $teamId],
            ['formation_place', '>', 0]
          ])->whereIn('schedule_id', $scheduleIds)
          ->selectRaw('player_id, formation_place, COUNT(*) AS cnt')
          ->groupBy(['player_id', 'formation_place'])
          ->get()
          ->map(function ($info) use ($teamId, &$players) {
            if ($info->plateCard->team_id === $teamId) {
              $column = 'cnt_' . $info->formation_place;
              if (!isset($players[$info->player_id])) {
                $players[$info->player_id] = $this->defaultCnt;
                $players[$info->player_id]['team_id'] = $teamId;
              }
              $players[$info->player_id][$column] = $info->cnt;
            }
          });

        $this->mapTableUpdate($players);
      }
      logger('mapUpdate end');
    } catch (Exception $th) {
      logger('mapUpdate 에러 발생! ' . $th->getMessage());
    }
  }

  // 동률 발생시 필요한 값 추가 계산
  private function mapExtraUpdate()
  {
    try {
      $this->resetBool = true;
      logger('mapExtraUpdate start');
      $players = [];
      foreach ($this->seasonTeam as $teamId => $seasonId) {
        OptaPlayerDailyStat::gameParticipantPlayer()
          ->has('refTeamFormationMap')
          ->with('plateCardWithTrashed')
          ->has('plateCardWithTrashed')
          ->where([
            ['season_id', $seasonId],
            ['team_id', $teamId],
            ['formation_place', '>', 0]
          ])->selectRaw('team_id, player_id, count(*) AS cnt, SUM(mins_played) AS mins_played, SUM(fantasy_point) AS fantasy_points')
          ->groupBy(['team_id', 'player_id'])
          ->get()
          ->map(function ($info) use ($teamId, &$players) {
            if ($info->plateCardWithTrashed->team_id === $teamId) {
              $player['team_id'] = $info->team_id;
              $player['match_name'] = $info->plateCardWithTrashed->match_name;
              $player['position'] = $info->plateCardWithTrashed->position;
              $player['all_count'] =  $info->cnt;
              $player['mins_played'] =  $info->mins_played;
              $player['fantasy_point_per'] = 0;
              if ($info->mins_played > 0) {
                $player['fantasy_point_per'] = BigDecimal::of($info->fantasy_points)->dividedBy(BigDecimal::of($info->mins_played)->dividedBy(90, 10, RoundingMode::HALF_UP), 1, RoundingMode::HALF_UP);
              }
              $player['deleted_at'] = null;
              // if ($info->player_id === '77yaqzy45dxtzicao00jxrlgq')
              //   dd($info->fantasy_points, $info->mins_played, $player['fantasy_point_per']);
              $players[$info->player_id] = $player;
            }
          })
          ->toArray();

        $this->mapTableUpdate($players);
      }
    } catch (Exception $th) {
      logger('mapExtraUpdate 에러 발생! ' . $th->getMessage());
    }
  }

  // index 별 대표선수 계산
  private function playerUpdate()
  {
    try {
      $maxByTeam = RefTeamFormationMap::selectRaw('team_id, MAX(cnt_1) AS cnt_1, MAX(cnt_2) AS cnt_2, MAX(cnt_3) AS cnt_3, MAX(cnt_4) AS cnt_4, MAX(cnt_5) AS cnt_5, MAX(cnt_6) AS cnt_6, MAX(cnt_7) AS cnt_7, MAX(cnt_8) AS cnt_8, MAX(cnt_9) AS cnt_9, MAX(cnt_10) AS cnt_10, MAX(cnt_11) AS cnt_11')
        ->whereIn('team_id', array_keys($this->seasonTeam))
        ->groupBy('team_id')
        ->get()
        ->keyBy('team_id')
        ->toArray();

      $players = [];
      foreach ($maxByTeam as $teamId => $columnCnt) {
        logger($teamId);
        $positionTops = [];
        foreach ($columnCnt as $column => $cnt) {
          if ($column === 'team_id') continue;

          $multiPlayerChk = RefTeamFormationMap::where([
            ['team_id', $teamId],
            [$column, $cnt]
          ])->get()->keyBy('player_id')->toArray();

          // 한 포지션에 여러 선수 동률
          // 1. 해당 포지션 출전 횟수 확인
          // 2. 전체 경기 출전 횟수 확인
          // 3. FP per 90 값
          // 4. mins_played
          $tempCount = 0;
          $tempPer = 0;
          $tempMins = 0;
          $tempKey = null;

          // if ($column === 'cnt_4')
          //   dd($multiPlayerChk);
          foreach ($multiPlayerChk as $playerId => $data) {
            if (count($multiPlayerChk) > 1) {
              if ($data['all_count'] > $tempCount) {
                $tempKey = $playerId;
                $tempCount = $data['all_count'];
                $tempPer = $data['fantasy_point_per'];
                $tempMins = $data['mins_played'];
              } else if ($data['all_count'] === $tempCount) {
                if ($data['fantasy_point_per'] > $tempPer) {
                  $tempKey = $playerId;
                  $tempPer = $data['fantasy_point_per'];
                  $tempMins = $data['mins_played'];
                } else if ($data['fantasy_point_per'] === $tempPer) {
                  if ($data['mins_played'] > $tempMins) {
                    $tempKey = $playerId;
                    $tempMins = $data['mins_played'];
                  }
                }
              }
            } else {
              $tempKey = $playerId;
            }
          }

          $positionTops[$tempKey][] = preg_replace('/\D/', '', $column);
        }

        // dd($positionTops);
        // 한 선수가 두 포지션에서 최다 출전
        // 1. 해당 포지션 출전 횟수 확인
        // 2. 해당 팀의 해당 포메이션으로 경기한 최근 경기에서 해당 선수 포지션으로
        // 3. 차순위 선수 선정

        foreach ($positionTops as $playerId => $positions) {
          if (count($positions) > 1) {  // 한 선수가 두 포지션 이상에서 최다 빈도일 경우
            $positionTops = $this->getOnePlayer($playerId, $positions, $positionTops, $teamId, 0);
          }
        }

        asort($positionTops);
        $remainPosition = [];
        for ($i = 1; $i < count($positionTops); $i++) {
          if (array_values($positionTops)[$i][0] - array_values($positionTops)[$i - 1][0] > 1) {
            array_push($remainPosition, array_values($positionTops)[$i][0] - 1);
          }
        }

        // dd($remainPosition);
        if (!empty($remainPosition)) {
          // 빈 position 채우기
          foreach ($remainPosition as $position) {
            $positionTops = $this->getNextPlayer($teamId, $position, $positionTops);
          }
        }

        foreach ($positionTops as $playerId => $positions) {
          foreach ($positions as $position) {
            $players[$position] = $playerId;
          }
        }

        ksort($players);

        RefTeamCurrentMeta::withTrashed()->updateOrCreateEx(
          [
            'team_id' => $teamId,
            'season_id' => $this->seasonTeam[$teamId],
          ],
          [
            'representative_player' => $players
          ],
          false,
          true,
        );
      }
    } catch (Exception $th) {
      dd($th);
    }
  }

  private function getOnePlayer($playerId, $positions, $allPositions, $teamId, $rank)
  {
    try {
      $mainTeamFormation = RefTeamCurrentMeta::where([
        'team_id' => $teamId,
        'season_id' => $this->seasonTeam[$teamId]
      ])->value('main_formation_used');

      $recentSchedule = Schedule::withWhereHas('oneOptaPlayerDailyStat', function ($query) use ($playerId, $positions) {
        $query->where('player_id', $playerId)
          ->whereIn('formation_place', $positions)
          ->select('schedule_id', 'formation_place', 'team_id');
      })->whereRaw('CASE 
      WHEN home_team_id="' . $teamId . '" AND home_formation_used = ' . $mainTeamFormation . ' THEN home_formation_used 
      WHEN away_team_id="' . $teamId . '" AND away_formation_used = ' . $mainTeamFormation . ' THEN away_formation_used 
      ELSE 0 
    END > ?', [0])
        ->latest('started_at')
        ->first();

      // dd($recentSchedule);
      if (is_null($recentSchedule)) {
        logger($playerId);
        logger($positions);
        logger($mainTeamFormation);
        logger('이상한 선수');
      }

      if (!is_null($recentSchedule)) {
        $recentPosition = $recentSchedule->oneOptaPlayerDailyStat->formation_place;

        foreach ($positions as $position) {
          if ((int) $position === $recentPosition) {
            unset($allPositions[$playerId]);
            $allPositions[$playerId][] = $position;
          } else {
            // 해당 포지션의 차순위 구하기
            $playerArr = RefTeamFormationMap::where([
              ['team_id', $teamId],
              ['cnt_' . $position, '>', 0]
            ])->where('player_id', '<>', $playerId)
              ->orderByDesc('cnt_' . $position)
              ->get()?->toArray();

            if (isset($playerArr[$rank]['player_id'])) {
              if (!in_array($playerArr[$rank]['player_id'], array_keys($allPositions))) {
                $allPositions[$playerArr[$rank]['player_id']][] = $position;
              } else {
                $rank++;
                $allPositions = $this->getOnePlayer($playerArr[$rank - 1]['player_id'], $positions, $allPositions, $teamId, $rank);
              }
            } else {
              logger('차순위가 없어용');
              logger($teamId);
              logger($playerId);
              logger($position);
              $this->getNextPlayer($teamId, $position, $allPositions);
            }
          }
        }
      }
    } catch (Exception $th) {
      // RefTeamFormationMap::where([
      //   ['team_id', $teamId],
      //   ['cnt_' . $position, '>', 0]
      // ])->where('player_id', '<>', $playerId)->dd();
      logger($th);
    }

    return $allPositions;
  }

  private function getNextPlayer($teamId, $position, $allPositions)
  {
    try {
      // 1. 최근 경기에서 같은 formation 이라면 해당 place 로 뛴 선수 
      $mainTeamFormation = RefTeamCurrentMeta::where('team_id', $teamId)->value('main_formation_used');
      $lastMatchInfo = Schedule::where(function ($query) use ($teamId) {
        $query->where('home_team_id', $teamId)
          ->orWhere('away_team_id', $teamId);
      })->select('id', 'home_team_id', 'away_team_id', 'home_formation_used', 'away_formation_used')->latest('started_at')->first();
      if (($lastMatchInfo->home_team_id === $teamId && $lastMatchInfo->home_formation_used === $mainTeamFormation) || ($lastMatchInfo->away_team_id === $teamId && $lastMatchInfo->away_formation_used === $mainTeamFormation)) {
        $nextPlayer = OptaPlayerDailyStat::where([
          ['schedule_id', $lastMatchInfo->id],
          ['formation_place', $position]
        ])->value('player_id');
        if (!in_array($nextPlayer, array_keys($allPositions))) {
          $allPositions[$nextPlayer][] = $position;
        } else {
          // 2. 해당 선수가 이미 있거나, 없다면 해당 place 의 position 중
          // mins_played > fantasy_point_per90 > match_name 으로
          $placePosition = config('formation-by-position.formation_used')[$mainTeamFormation][$position];
          $nextPlayers = RefTeamFormationMap::where([
            ['team_id', $teamId],
            ['position', $placePosition],
          ])->orderByDesc('mins_played')
            ->orderByDesc('fantasy_point_per')
            ->orderBy('match_name')
            ->pluck('player_id')
            ->toArray();
          foreach ($nextPlayers as $player) {
            if (!in_array($player, array_keys($allPositions))) {
              $allPositions[$player][] = $position;
              break;
            }
          }
        }
      } else {
        // 2. 해당 선수가 이미 있거나, 없다면 해당 place 의 position 중
        // mins_played > fantasy_point_per90 > match_name 으로
        $placePosition = config('formation-by-position.formation_used')[$mainTeamFormation][$position];
        $nextPlayers = RefTeamFormationMap::where([
          ['team_id', $teamId],
          ['position', $placePosition],
        ])->orderByDesc('mins_played')
          ->orderByDesc('fantasy_point_per')
          ->orderBy('match_name')
          ->pluck('player_id')
          ->toArray();
        foreach ($nextPlayers as $player) {
          if (!in_array($player, array_keys($allPositions))) {
            $allPositions[$player][] = $position;
            break;
          }
        }
      }
    } catch (Exception $th) {
      logger($th);
    }

    return $allPositions;
  }

  private function nextMatchInfoUpdate()
  {
    foreach ($this->seasonTeam as $teamId => $seasonId) {
      $nextMatch = Schedule::where(function ($query) use ($teamId) {
        $query->where('home_team_id', $teamId)
          ->orWhere('away_team_id', $teamId);
      })->with([
        'home:' . implode(',', config('commonFields.team')),
        'away:' . implode(',', config('commonFields.team')),
      ])->has('league')
        ->where([
          ['status', ScheduleStatus::FIXTURE],
          ['season_id', $seasonId]
        ])
        ->orderBy('started_at')
        ->first();

      $nextMatchTeam['schedule_id'] = $nextMatch->id;
      if ($nextMatch->home_team_id === $teamId) {
        $nextMatchTeam['opposing_team'] = $nextMatch->away->toArray();
      } else if ($nextMatch->away_team_id === $teamId) {
        $nextMatchTeam['opposing_team'] = $nextMatch->home->toArray();
      }

      RefTeamCurrentMeta::withTrashed()->updateOrCreateEx(
        [
          'team_id' => $teamId,
          'season_id' => $seasonId,
        ],
        [
          'next_match_team' => $nextMatchTeam
        ],
        false,
        true,
      );
    }
  }

  private function getDailyIds()
  {
    return Season::idsOf([SeasonWhenType::CURRENT], SeasonNameType::ALL, 1);
  }

  private function getAllIds()
  {
    return Season::idsOf([SeasonWhenType::BEFORE, SeasonWhenType::CURRENT], SeasonNameType::ALL, 1);
  }

  public function start(): bool
  {
    $ids = [];
    switch ($this->parserMode) {
      case ParserMode::SYNC:
        if (!$this->setUpSyncFantasyParsing($this->feedNick)) return false;
        switch ($this->syncGroup) {
          case FantasySyncGroupType::ALL:
            $ids = $this->getAllIds();
            break;
          case FantasySyncGroupType::DAILY:
            $ids = $this->getDailyIds();
            break;
          default:
            break;
        }
      case ParserMode::PARAM:
        if ($this->getParam('mode') === 'all') {
          $ids = $this->getAllIds();
        } else if ($this->getParam('mode') === 'daily') {
          $ids = $this->getDailyIds();
        }
        break;
      default:
        // team_id 받아서 한팀씩 next_match update
        break;
    }

    try {
      if (!is_null($this->teamId)) {
        logger('start ' . $this->teamId . ' nextMatchInfo update');
        $this->baseUpdate([]);
        $this->nextMatchInfoUpdate();
        logger('team nextMatchInfo update 성공');
      } else {
        logger('start team formation update');
        $this->baseUpdate($ids);
        $this->formationUpdate();
        $this->mapUpdate();
        $this->mapExtraUpdate();
        $this->playerUpdate();
        $this->nextMatchInfoUpdate();
        logger('team formation update 성공');
      }
    } catch (Exception $e) {
      logger($e);
      logger('update team formation 실패');
      throw $e;
    }

    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}
