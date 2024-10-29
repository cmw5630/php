<?php

namespace App\Console\Commands\DataControll;

use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Season\SeasonNameType;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\Opta\YesNo;
use App\Enums\ParserMode;
use App\Enums\PlayerStrengthStandardType;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\data\Squad;
use App\Models\meta\RefPlayerSeasonStrengths;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DB;
use Exception;

class PlayerStrengthRefUpdator
{
  use FantasyMetaTrait;

  protected $feedNick;

  protected $isCompleted = false;
  protected $playerData = [
    'player_id' => null,
    'season_id' => null,
    'deleted_at' => null
  ];

  public function __construct()
  {
    $this->feedNick = 'PSRU';

    foreach (config('refplayerstrength.categories') as $category => $strength) {
      foreach ($strength as $column => $value) {
        $this->playerData = array_merge($this->playerData, [$column => null]);
      }
    }
  }

  private function preCheckStandard($stat, $seasonId, $totalMP)
  {
    if ($stat === 'att_freekick_total') {
      return true;
    }

    $maxRound = Schedule::where('season_id', $seasonId)
      ->whereIn('status', [ScheduleStatus::PLAYED, ScheduleStatus::AWARDED])
      ->orderByDesc('round')
      ->value('round');

    $standard = BigDecimal::of($maxRound)->multipliedBy(90)->multipliedBy(0.15, 2, RoundingMode::HALF_UP);
    if ($standard > $totalMP) {
      return false;
    } else {
      return true;
    }
  }

  private function statCalculator($type, $data)
  {
    switch ($type) {
      case PlayerStrengthStandardType::AVERAGE:
        if ($data['matches'] > 0) {
          return BigDecimal::of($data['stat'])->dividedBy($data['matches'], 2, RoundingMode::HALF_UP);
        }
      case PlayerStrengthStandardType::PER:
        if ($data['total_mins_played'] > 0) {
          return BigDecimal::of($data['stat'])->dividedBy(BigDecimal::of($data['total_mins_played'])->dividedBy(90, 10, RoundingMode::DOWN), 2, RoundingMode::HALF_UP);
        }
      case PlayerStrengthStandardType::SUM:
        return BigDecimal::of($data['stat']);
      default:
        return;
    }
  }

  private function insertDatas($_data)
  {
    RefPlayerSeasonStrengths::withTrashed()
      ->updateOrCreateEx(
        [
          'player_id' => $_data['player_id'],
          'season_id' => $_data['season_id'],
        ],
        $_data,
        false,
        true,
      );
  }

  public function updatePlayerStrengthRank($_seasonIds)
  {
    $resetBool = true;

    $columns = '';
    foreach (config('refplayerstrength.categories') as $category => $strength) {
      foreach ($strength as $column => $value) {
        $columns .= 'SUM(' . $value['col_name'][0] . ') AS ' . $value['col_name'][0] . ', ';
      }
    }
    $columns .= 'season_id, player_id, SUM(mins_played) AS total_mins_played, COUNT(*) AS total_games';

    OptaPlayerDailyStat::whereIn('season_id', $_seasonIds)
      ->where('mins_played', '>', 0)
      ->with('plateCardWithTrashed:player_id,position')
      ->has('season.league')
      ->has('plateCardWithTrashed')
      ->selectRaw($columns)
      ->groupBy(['season_id', 'player_id'])
      ->get()
      ->map(
        function ($info) use (&$resetBool) {
          if ($resetBool) {
            RefPlayerSeasonStrengths::where('season_id', $info->season_id)->delete();
            $resetBool = false;
          }

          if (Squad::withTrashed()
            ->where([
              ['season_id', $info->season_id],
              ['player_id', $info->player_id],
              ['active', YesNo::YES]
            ])->exists()
          ) {
            $player = $this->playerData;
            $player['player_id'] = $info->player_id;
            $player['season_id'] = $info->season_id;
            foreach (config('refplayerstrength.categories') as $category => $strength) {
              foreach ($strength as $column => $value) {
                if ($this->preCheckStandard($value['col_name'][0], $info->season_id, $info->total_mins_played)) {
                  $calResult = $this->statCalculator($value['standard'], ['stat' => $info->{$value['col_name'][0]}, 'matches' => $info->total_games, 'total_mins_played' => $info->total_mins_played]);
                  foreach ($value['cut_count'] as $cut) {
                    if ($calResult->compareTo($cut['count']) >= 0) {
                      switch ($column) {
                        case 'long_passes':
                          if ($info->plateCardWithTrashed->position !== PlayerPosition::GOALKEEPER) {
                            $player[$column] = $cut['type'];
                          }
                          break 2;
                        case 'clean_sheet':
                          if ($info->plateCardWithTrashed->position === PlayerPosition::GOALKEEPER) {
                            $player[$column] = $cut['type'];
                          }
                          break 2;
                        default:
                          $player[$column] = $cut['type'];
                          break 2;
                      }
                    }
                  }
                }
              };
            }
            $this->insertDatas($player);
          }
        }
      );
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
        # code...
        break;
    }

    DB::beginTransaction();
    try {
      $this->updatePlayerStrengthRank($ids);

      DB::commit();
      info('update 성공');
    } catch (Exception $e) {
      DB::rollBack();
      info($e);
      info('실패(RollBack)');
      return false;
    }

    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}
