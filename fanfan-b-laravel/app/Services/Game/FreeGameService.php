<?php

namespace App\Services\Game;

use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\FreeGame\FreeGameShuffleType;
use App\Enums\GameType;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Libraries\Classes\Exception;
use App\Libraries\Classes\FantasyCalculator;
use App\Models\data\OptaPlayerSeasonStat;
use App\Models\data\Schedule;
use App\Models\game\FreeCardMeta;
use App\Models\game\FreeCardShuffleMemory;
use App\Models\game\FreeGameLineupMemory;
use App\Models\game\Game;
use App\Models\game\GameJoin;
use App\Models\game\PlateCard;
use App\Models\user\UserMeta;
use Carbon\Carbon;
use DB;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use ReturnData;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

interface FreeGameServiceInterface
{
  public function makeShuffleCard(string $_leagueId, string $_position, int $_limit);
  public function sliceLevel(int $_restLevel, string $_position): array;
}

class FreeGameService
{
  protected ?Authenticatable $user;
  protected $baseResult;
  public function __construct(?Authenticatable $_user)
  {
    $this->user = $_user;
    $this->baseResult = ['shuffle' => null, 'lineup' => [
      PlayerPosition::ATTACKER => null,
      PlayerPosition::MIDFIELDER => null,
      PlayerPosition::DEFENDER => null,
      PlayerPosition::GOALKEEPER => null,
    ]];
  }

  public function isAvailableFreeGame($_gameId): bool
  {
    // 임시 true 처리
    return true;
    // 1. 게임 시작 30분 이전인지
    return Game::isFreeGame()
      ->where([
        ['id', $_gameId,],
        ['start_date', '>', Carbon::now()->addMinutes(config('constant.INGAME_POSSIBLE_TIME'))]
      ])->exists();
  }

  public function shuffleCommon($_gameId, $_shuffleType = FreeGameShuffleType::COUNT)
  {
    $pF = $this->getCurrentShuffleInfo($_gameId);
    if ($pF === null) {
      return ReturnData::setError('There are no shuffle cards')->send(Response::HTTP_BAD_REQUEST);
    }
    $targetPosition = $pF['position'];
    $formationPlace = $pF['formation_place'];
    $freeCardMetaId = $this->getFreeCardMetas($_gameId)['id'];
    if ($_shuffleType === FreeGameShuffleType::COUNT && !$this->shuffleCountCheck($_gameId, $freeCardMetaId)) {
      throw new Exception('have no chance to shuffle!', Response::HTTP_BAD_REQUEST);
    } else if ($_shuffleType === FreeGameShuffleType::POINT && !$this->shufflePointCheck($_gameId, $freeCardMetaId)) {
      throw new Exception('FAN is in short supply.', Response::HTTP_BAD_REQUEST);
    }
    // $result = ['shuffle_remained' => $this->freeGameService->getShuffleRemainCount($input['game_id']) - 1];
    $this->makeShuffleCard($freeCardMetaId, $_gameId, $targetPosition, $formationPlace);
  }

  private function shuffleCountUp($_freeCardMetaId)
  {
    $a = FreeCardMeta::whereId($_freeCardMetaId)->first();
    $a->shuffle_count += 1;
    $a->save();
  }

  private function shuffleCountCheck($_gameId, $freeCardMetaId): bool
  {
    $fcm = FreeCardMeta::whereId($freeCardMetaId)->first();
    if ($fcm->shuffle_count < 3) {
      $fcm->shuffle_count += $fcm->shuffle_count;
      $this->shuffleCountUp($freeCardMetaId);
      return true;
    }
    return false;
  }

  private function shufflePointCheck($_gameId): bool
  {
    // inner transaction!

    /**
     * @var FantasyCalculator $ffgCalculator
     */
    $ffgCalculator = app(FantasyCalculatorType::FANTASY_FREE_GAME, [0]);
    $ffgCalculator->getConfig();

    // 로그 필요
    $userMetaInst = UserMeta::where('user_id', $this->user['id'])->first();
    if ($userMetaInst->fan_point < 200) {
      return false;
    }
    $userMetaInst->fan_point -= 200;
    $userMetaInst->save();
    return true;
  }


  public function validatePosition(int $_gameId, string|null $_position): int
  {
    // $_position이 null이면 validation이 아닌 현재 free lineup remain totalCount를 얻기위한 용도
    // 변경: 1. validation, 2. full인지.
    $positionRemainCountsMap = [
      PlayerPosition::ATTACKER => 2,
      PlayerPosition::MIDFIELDER => 4,
      PlayerPosition::DEFENDER => 4,
      PlayerPosition::GOALKEEPER => 1,
    ];

    FreeCardMeta::where([
      ['user_id', $this->user['id']],
      ['game_id', $_gameId],
    ])->with('freeGameLineupMemory')
      ->get()
      ->map(function ($item) use (&$positionRemainCountsMap) {
        foreach ($item->toArray()['free_game_lineup_memory'] as $x => $v) {
          $positionRemainCountsMap[$v['position']]--;
        }
      });

    $totalLineupRemain = array_sum($positionRemainCountsMap);
    if ($_position !== null && $totalLineupRemain === 0) {
      throw new Exception('free lineup is full!');
    }

    if ($positionRemainCountsMap[$_position] === 0) {
      throw new Exception(sprintf('%s is now full! wrong position!', $_position));
    }

    return $totalLineupRemain;



    // $positionRemainCount = null;
    // $targetPosition = null;
    // foreach ($positionRemainCountsMap as $k => $v) {
    //   if ($v == 0) continue;
    //   $targetPosition = $k;
    //   $positionRemainCount = $v;
    //   break;
    // }
    // if ($targetPosition !== $_position && $_position !== null) {
    //   throw new Exception('position not available');
    // } else if ($_position === null) {
    //   $remainTotalCount = 0;
    //   foreach ($positionRemainCountsMap as $pos => $count) {
    //     $remainTotalCount += $count;
    //   }
    //   return $remainTotalCount;
    // }
    // return $positionRemainCount;
  }

  public function getCurrentShuffleInfo(int $_gameId): array|null
  {
    $tt = FreeCardMeta::where([
      ['game_id', $_gameId],
      ['user_id', $this->user['id']]
    ])->withHas('freeCardShuffleMemory')->first();

    if (!$tt) {
      return null;
    }

    return [
      'position' => $tt->freeCardShuffleMemory->toArray()[0]['position'],
      'formation_place' => $tt->freeCardShuffleMemory->toArray()[0]['formation_place']
    ];
  }

  public function getShuffleRemainCount($_game_id, $_shuffleCount = null)
  {
    if ($_shuffleCount === null) {
      $_shuffleCount = FreeCardMeta::where([
        ['game_id', $_game_id],
        ['user_id', $this->user->id]
      ])->first()['shuffle_count'];
    }
    return 3 - $_shuffleCount; // 3 -> config constant 변수로 처리하기!
  }

  private function validateFreeGameJoin($_gameId)
  {
    $hasJoined = GameJoin::where([
      ['user_id', $this->user['id']],
      ['game_id', $_gameId],
    ])->exists();

    if ($hasJoined) {
      throw new Exception('have aleady joined game!', Response::HTTP_BAD_REQUEST);
    }
  }

  public function validateFormationPlaceInFreeLineupMemory(array $_input): void
  {
    $this->validateFreeGameJoin($_input['game_id']);

    if ($_input['position'] xor $_input['formation_place']) {
      throw new Exception('incorrect position or formation_place!', Response::HTTP_BAD_REQUEST);
    } else if (!($_input['position'] && $_input['formation_place'])) {
      return;
    }

    $freeCardInfos = FreeCardMeta::where([
      ['user_id', $this->user['id']],
      ['game_id', $_input['game_id']],
    ])
      ->with('freeGameLineupMemory', function ($query) use ($_input) {
        $query->where([
          ['position', $_input['position']],
          ['formation_place', $_input['formation_place']],
        ]);
      })->with('freeCardShuffleMemory')
      ->first();
    if (!empty($freeCardInfos['freeGameLineupMemory']->toArray())) {
      throw new Exception('wrong position or formation_place!', Response::HTTP_BAD_REQUEST);
    }
    if (!empty($freeCardInfos['freeCardShuffleMemory']->toArray())) {
      throw new Exception('aleady shffle card exists!', Response::HTTP_BAD_REQUEST);
    }
  }

  public function isShuffleAvailable(string $_position, int $_gameId)
  {
    $positionAvailableCount = [
      PlayerPosition::ATTACKER => 2,
      PlayerPosition::MIDFIELDER => 4,
      PlayerPosition::DEFENDER => 4,
      PlayerPosition::GOALKEEPER => 1,
    ];

    $item = FreeCardMeta::where([
      ['user_id', $this->user['id']],
      ['game_id', $_gameId],
    ])->doesntHave(
      'freeCardShuffleMemory'
    )->with('freeGameLineupMemory')->first();

    if ($item === null) {
      return ReturnData::setError('not available shuffle!')->send(Response::HTTP_BAD_REQUEST);
    }

    foreach ($item->toArray()['free_game_lineup_memory'] as $lineup) {
      $positionAvailableCount[$lineup['position']]--;
    }
    if ($positionAvailableCount[$_position] === 0) {
      throw new Exception('not available position');
    }
  }

  // PlayerPosition::ATTACKER; // 2명
  // PlayerPosition::MIDFIELDER; // 4명
  // PlayerPosition::DEFENDER; // 4명
  // PlayerPosition::GOALKEEPER; // 1명
  public function getFreeCardMemories(string $_gameId, string|null $_position = null, $_formation_place = null)
  {
    if (!$this->isAvailableFreeGame($_gameId)) {
      throw new Exception('not available free game_id');
    }

    // 사용자 페이지 진입
    $result = [
      // 'shuffle_remained' => 0,
      'lineup_full' => null,
      // 'shuffle' => null, 
      'lineup' => [
        PlayerPosition::ATTACKER => null,
        PlayerPosition::MIDFIELDER => null,
        PlayerPosition::DEFENDER => null,
        PlayerPosition::GOALKEEPER => null,
      ]
    ];

    $cardMetaInst = FreeCardMeta::where([
      ['user_id', $this->user->id],
      ['game_id', $_gameId],
    ]);
    if (!$cardMetaInst->clone()->exists()) {
      $this->getFreeCardMetas($_gameId, true);
      $cardMetaInst = FreeCardMeta::where([
        ['user_id', $this->user->id],
        ['game_id', $_gameId],
      ]);
    }

    if ($_position !== null) {
      $this->isShuffleAvailable($_position, $_gameId);
      // 포지션 파라미터
      $result['shuffle'] = $this->makeShuffleCard($this->getFreeCardMetas($_gameId, true)['id'], $_gameId, $_position, $_formation_place);
    }

    $cardMetaInst = FreeCardMeta::where([
      ['user_id', $this->user->id],
      ['game_id', $_gameId],
    ])
      // ->with('freeCardShuffleMemory', function ($query) {
      //   $query
      // ->withWhereHas('plateCard', function ($query) {
      //   $query->with('league');
      // })
      // ->with('season')->with('team')->with('draftSchedule');
      // })
      ->with(['freeGameLineupMemory.plateCard.currentRefPlayerOverall' => function ($query) {
        $query->select('id', 'player_id', 'final_overall');
      }])
      ->first();

    // if ($_position === null) {
    //   foreach ($cardMetaInst['freeCardShuffleMemory']->toArray() as $idx => $item) {
    //     $item['player_name'] = $item['plate_card']['player_name'];
    //     $item['short_player_name'] = $item['plate_card']['short_player_name'];
    //     $item['player_name_eng'] = $item['plate_card']['player_name_eng'];
    //     $item['season_name'] = $item['season']['name'];
    //     $item['team_name'] = $item['plate_card']['team_name'];
    //     $item['team_short_name'] = $item['plate_card']['team_short_name'];
    //     $item['team_club_name'] = $item['plate_card']['team_club_name'];
    //     $item['league_code'] = $item['plate_card']['league']['league_code'];
    //     unset($item['plate_card']);
    //     unset($item['season']);
    //     unset($item['team']);
    //     $result['shuffle'][] = $item;
    //   }
    // }

    // if ($cardMetaInstWithLineup->clone()->exists()) {
    //   $cardMetaInst = $cardMetaInstWithLineup->clone()->first();
    // } else {
    //   $cardMetaInst = $cardMetaInst->clone()->first();
    // }

    // $result['shuffle_remained'] = $this->getShuffleRemainCount($_gameId, $cardMetaInst['shuffle_count']);

    if (isset($cardMetaInst['freeGameLineupMemory'])) {
      foreach ($cardMetaInst['freeGameLineupMemory']->toArray() as $item) {
        $item['player_name'] = $item['plate_card']['player_name'];
        $item['match_name'] = $item['plate_card']['match_name'];
        $item['short_player_name'] = $item['plate_card']['short_player_name'];
        $item['player_name_eng'] = $item['plate_card']['player_name_eng'];
        $item['team_name'] = $item['plate_card']['team_name'];
        $item['team_short_name'] = $item['plate_card']['team_short_name'];
        $item['team_club_name'] = $item['plate_card']['team_club_name'];
        $item['nationality_code'] = $item['plate_card']['nationality_code'];
        $item['final_overall'] = $item['plate_card']['currentRefPlayerOverall']['final_overall'] ?? 777;
        unset($item['plate_card']);
        $result['lineup'][$item['position']][] = $item;
      }
      if (count($cardMetaInst['freeGameLineupMemory']) === 11) {
        $result['lineup_full'] = true;
      } else {
        $result['lineup_full'] = false;
      }
    }

    return $result;
  }


  public function getFreeCardMemories2(string $_gameId, string|null $_position = null, $_formation_place = null)
  {
    if (!$this->isAvailableFreeGame($_gameId)) {
      throw new Exception('not available free game_id');
    }

    // 사용자 페이지 진입
    $result = [
      'shuffle_remained' => 0,
      'lineup_full' => null,
      'shuffle' => null,
      'lineup' => [
        PlayerPosition::ATTACKER => null,
        PlayerPosition::MIDFIELDER => null,
        PlayerPosition::DEFENDER => null,
        PlayerPosition::GOALKEEPER => null,
      ]
    ];

    $cardMetaInst = FreeCardMeta::where([
      ['user_id', $this->user->id],
      ['game_id', $_gameId],
    ]);
    if (!$cardMetaInst->clone()->exists()) {
      $this->getFreeCardMetas($_gameId, true);
      $cardMetaInst = FreeCardMeta::where([
        ['user_id', $this->user->id],
        ['game_id', $_gameId],
      ]);
    }

    if ($_position !== null) {
      $this->isShuffleAvailable($_position, $_gameId);
      // 포지션 파라미터
      $result['shuffle'] = $this->makeShuffleCard($this->getFreeCardMetas($_gameId, true)['id'], $_gameId, $_position, $_formation_place);
    }

    $cardMetaInst = FreeCardMeta::where([
      ['user_id', $this->user->id],
      ['game_id', $_gameId],
    ])->with('freeCardShuffleMemory', function ($query) {
      $query->withWhereHas('plateCard', function ($query) {
        $query->with('league');
      })->with('season')->with('team')->with('draftSchedule');
    })->with('freeGameLineupMemory.plateCard')->first();

    if ($_position === null) {
      foreach ($cardMetaInst['freeCardShuffleMemory']->toArray() as $idx => $item) {
        $item['player_name'] = $item['plate_card']['player_name'];
        $item['short_player_name'] = $item['plate_card']['short_player_name'];
        $item['player_name_eng'] = $item['plate_card']['player_name_eng'];
        $item['season_name'] = $item['season']['name'];
        $item['team_name'] = $item['plate_card']['team_name'];
        $item['team_short_name'] = $item['plate_card']['team_short_name'];
        $item['team_club_name'] = $item['plate_card']['team_club_name'];
        $item['league_code'] = $item['plate_card']['league']['league_code'];
        unset($item['plate_card']);
        unset($item['season']);
        unset($item['team']);
        $result['shuffle'][] = $item;
      }
    }

    // if ($cardMetaInstWithLineup->clone()->exists()) {
    //   $cardMetaInst = $cardMetaInstWithLineup->clone()->first();
    // } else {
    //   $cardMetaInst = $cardMetaInst->clone()->first();
    // }

    $result['shuffle_remained'] = $this->getShuffleRemainCount($_gameId, $cardMetaInst['shuffle_count']);

    if (isset($cardMetaInst['freeGameLineupMemory'])) {
      foreach ($cardMetaInst['freeGameLineupMemory']->toArray() as $item) {
        $item['player_name'] = $item['plate_card']['player_name'];
        $item['short_player_name'] = $item['plate_card']['short_player_name'];
        $item['player_name_eng'] = $item['plate_card']['player_name_eng'];
        $item['team_name'] = $item['plate_card']['team_name'];
        $item['team_short_name'] = $item['plate_card']['team_short_name'];
        $item['team_club_name'] = $item['plate_card']['team_club_name'];
        unset($item['plate_card']);
        $result['lineup'][$item['position']][] = $item;
      }
      if (count($cardMetaInst['freeGameLineupMemory']) === 11) {
        $result['lineup_full'] = true;
      } else {
        $result['lineup_full'] = false;
      }
    }

    foreach ($cardMetaInst['freeGameLineupMemory']->toArray() as $idx => $item) {
    }
    return $result;
  }


  public function getFreeCardMetas(int $_gameId, $_create_mode = false): array
  {
    // $_create_mode - 사용자가 
    $metaInst = FreeCardMeta::where([
      ['user_id', $this->user->id],
      ['game_id', $_gameId],
    ])->first();

    if ($metaInst !== null) {
      return [
        'id' => $metaInst->id,
        'shuffle_count' => $metaInst->shuffle_count,
      ];
    } else if ($_create_mode === false) {
      throw new Exception('can\'t create shuffle cards!');
    }

    $this->validateFreeGameJoin($_gameId);

    $fcmInst = (new FreeCardMeta);
    $fcmInst->user_id = $this->user['id'];
    $fcmInst->game_id = $_gameId;
    $fcmInst->save();

    return [
      'id' => $fcmInst->id,
      'shuffle_count' => $fcmInst->shuffle_count,
    ];
  }

  protected function getGameScheduleTeams(int $_gameId): array
  {
    $targetTeamIds = [];
    Game::with('gameSchedule.gamePossibleSchedule.schedule')
      ->whereId($_gameId)
      ->isFreeGame()
      ->get()->map(function ($item) use (&$targetTeamIds) {
        foreach ($item->gameSchedule as $idx => $value) {
          $targetTeamIds['teams'][]  = $value['gamePossibleSchedule']['schedule']['home_team_id'];
          $targetTeamIds['teams'][]  = $value['gamePossibleSchedule']['schedule']['away_team_id'];
          $targetTeamIds['teamScheduleMap'][$value['gamePossibleSchedule']['schedule']['home_team_id']] = $value['gamePossibleSchedule']['schedule']['id'];
          $targetTeamIds['teamScheduleMap'][$value['gamePossibleSchedule']['schedule']['away_team_id']] = $value['gamePossibleSchedule']['schedule']['id'];
        }
      });

    return $targetTeamIds;
  }

  public function getRandomDraftSchedule($_seasonId, $_teamId)
  {
    $schedules = Schedule::where([
      // ['season_id', $_seasonId],
      ['status', ScheduleStatus::PLAYED],
    ])
      ->where(function ($query) use ($_teamId) {
        $query->orWhere('home_team_id', $_teamId)
          ->orWhere('away_team_id', $_teamId);
      })->get()->toArray();
    return Arr::random($schedules);
  }

  // public function makeShuffleCard(int $_freeCardMetaId, int $_gameId, string|null $_position, int $_limit = 2)
  // {
  //   $result = [];
  //   while (true) {
  //     $_limit--;
  //     $result[] = $this->babo($_freeCardMetaId = $_freeCardMetaId, $_gameId = $_gameId, $_position = $_position);
  //     if ($_limit <= 0) {
  //       break;
  //     }
  //   }
  //   return $result;
  // }

  public function makeShuffleCard(int $_freeCardMetaId, int $_gameId, string|null $_position, $_formation_place, $_limit = 5)
  {
    /**
     * @var FantasyCalculator $fpCalculator
     **/

    /**
     * @var FantasyCalculator $freeGameCalculator
     */

    /**
     * @var FantasyCalculator $fpjCalculator
     */

    if ($_position === null) return;

    $fpjCalculator = app(FantasyCalculatorType::FANTASY_PROJECTION, [0]);

    $freeGameCalculator = app(FantasyCalculatorType::FANTASY_FREE_GAME, [0]);

    $fpCalculator = app(FantasyCalculatorType::FANTASY_INGAME_POINT, [0]);

    $seasonId = Game::whereId($_gameId)->value('season_id');

    $result = [];

    $this->clearShuffleCard($_freeCardMetaId);

    $teamScheduleMap = $this->getGameScheduleTeams($_gameId);

    $filterPlayers = [];
    FreeGameLineupMemory::where([
      ['free_card_meta_id', $_freeCardMetaId],
      ['position', $_position],
    ])->with('plateCard')->get()->map(function ($item) use (&$filterPlayers) {
      $filterPlayers[] = $item->plateCard->player_id;
    });

    $shuffleX = [];
    $playerIds = [];
    for ($i = 0; $i < $_limit; $i++) {
      while (true) {
        $shuffleOne = $freeGameCalculator->calculate([
          'position' => $_position ?? PlayerPosition::ATTACKER,
          'team_map' => $teamScheduleMap,
          'season_id' => $seasonId,
        ]);
        if (
          !in_array($shuffleOne['player']['player_id'], $playerIds) &&
          !in_array($shuffleOne['player']['player_id'], $filterPlayers)
        ) {
          $playerIds[$i] =  $shuffleOne['player']['player_id'];
          break;
        }
      }
      $shuffleX[$i] = $shuffleOne;
    }

    $playerIdSyncNumber = -1;
    PlateCard::isOnSale()->where([
      // 'player_id' => $shuffleOne['player']['player_id'],
      'season_id' => $seasonId,
    ])
      ->whereIn('player_id', $playerIds)
      ->whereIn('team_id', $teamScheduleMap['teams'])
      ->get()->map(function ($player) use ($shuffleX, $_freeCardMetaId, $fpCalculator, $fpjCalculator, $teamScheduleMap, &$result, $_formation_place, &$playerIdSyncNumber) {
        $playerIdSyncNumber++;
        $shufflePlayer = $shuffleX[$playerIdSyncNumber];
        $seasonStat = OptaPlayerSeasonStat::withWhereHas('season', function ($query) {
          $query->currentSeasons();
        })->where('player_id', $player->player_id)
          ->first(['season_id', 'appearances', 'goals', 'assists']);

        $playerId = $player->player_id;
        $plateCardId = $player->id;
        $isMom = $shufflePlayer['isMom'];
        $rating = Arr::random(range(0, 10)) / 10; // x 
        // $fantasyPoint = Arr::random(range(-220, 1000)) / 10;
        // $draftLevel = $totalLevel = Arr::random(range(0, 9));

        $oneResult = $this->sliceLevel($shufflePlayer['levels'], $player['position']);
        $oneResult['card_grade'] = $shufflePlayer['card_grade'];
        $oneResult['is_mom'] = $isMom;

        $oneResult['projection'] =  $fpjCalculator->calculate(['raw_data' => array_merge($oneResult, [
          'plate_card_id' => $plateCardId,
          'player_id' => $playerId,
          'card_grade' => $shufflePlayer['card_grade'],
        ])]);

        // $oneResult['levels'] = $shuffleOne['levels'];
        $oneResult['is_open'] = false;
        $oneResult['formation_place'] = $_formation_place;
        $oneResult['mp'] = $seasonStat['appearances'] ?? 0;
        $oneResult['goals'] = $seasonStat['goals'] ?? 0;
        $oneResult['assists'] = $seasonStat['assists'] ?? 0;
        $oneResult['season_id'] = $player->season_id;
        // $oneResult['draft_schedule_id'] = $this->getRandomDraftSchedule($player->season_id, $player->team_id)['id'];
        $oneResult['team_id'] = $player['team_id'];
        $oneResult['headshot_path'] = $player->headshot_path;
        $oneResult['special_skills'] = $shufflePlayer['three_strength']; //
        $oneResult['plate_card_id'] = $player['id'];
        $oneResult['position'] = $player['position'];
        $oneResult['free_card_meta_id'] = $_freeCardMetaId;
        $oneResult['rating'] = $rating;
        $oneResult['draft_level'] = array_sum($shufflePlayer['levels']);;
        $oneResult['level_weight'] = $fpCalculator->getAdditionalSpecialStatPoint($oneResult, $isMom);
        $oneResult['schedule_id'] = $teamScheduleMap['teamScheduleMap'][$player->toArray()['team_id']];
        $oneResult['id'] = FreeCardShuffleMemory::create($oneResult)->id;
        // $oneResult['plate_card'] = $player->toArray();
        $oneResult['player_name'] = $player['player_name'];
        $oneResult['short_player_name'] = $player['short_player_name'];
        $oneResult['player_name_eng'] = $player['player_name_eng'];
        $oneResult['team_name'] = $player['team_name'];
        $oneResult['team_short_name'] = $player['team_short_name'];
        $oneResult['team_club_name'] = $player['team_club_name'];

        $oneResult['player_name'] = $player['match_name'];
        $oneResult['season_name'] = $player['season']['name'];
        $oneResult['team_name'] = $player['team']['official_name'];
        $oneResult['league_code'] = $player['league']['league_code'];
        // $oneResult['draft_schedule'] = Schedule::whereId($oneResult['draft_schedule_id'])->first()->toArray();

        $result[] = $oneResult;
      });

    return $result;
  }


  protected function clearShuffleCard(int $_freeCardMetaId)
  {
    FreeCardShuffleMemory::where('free_card_meta_id', $_freeCardMetaId)->forceDelete();
  }

  public function sliceLevel(array $_shuffledLevels, string $_position): array
  {
    $result = ['attacking_level' => null, 'goalkeeping_level' => null, 'passing_level' => 0, 'defensive_level' => 0, 'duel_level' => 0, 'position' => $_position];
    foreach ($_shuffledLevels as $mainKey => $level) {
      $result[$mainKey . '_' . 'level'] = $level;
    }
    // if ($_position === PlayerPosition::GOALKEEPER) {
    //   $cates = ['goalkeeping_level', 'passing_level', 'defensive_level', 'duel_level'];
    // } else {
    //   $cates = ['attacking_level', 'passing_level', 'defensive_level', 'duel_level'];
    // }

    // foreach ($cates as $key => $value) {
    //   if ($value === 'duel_level') {
    //     $result[$value] = $_restLevel;
    //     break;
    //   }
    //   $partialLevel = Arr::random(range(0, $_restLevel));
    //   $_restLevel = $_restLevel - $partialLevel;
    //   $result[$value] = $partialLevel;
    // }

    return $result;
  }

  // public function makeCardGrade


}
