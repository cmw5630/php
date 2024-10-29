<?php

namespace App\Console\Commands\BetRadarParsers;

use App\Console\Commands\DataControll\GaRoundUpdator;
use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\ParserMode;
use App\Libraries\Traits\FantasyMetaTrait;
use App\Models\data\BrSchedule;
use App\Models\data\BrSeason;
use App\Models\data\BrTeam;
use App\Models\data\Schedule;
use Carbon\Carbon;

class SeasonScheduleParser extends BaseBetRadarParser
{
  use FantasyMetaTrait;
  private const REQUEST_COUNT_AT_ONCE = 1;
  private array $brOptaTeamMap;
  private array $brOptaLeagueSeasonMap;

  public function __construct()
  {
    parent::__construct();
    $this->feedType = 'season_schedule';
    $this->feedNick = 'BR_SS';
    $brOptaTeamMap = [];
    BrTeam::get(['br_team_id', 'opta_team_id'])
      ->map(function ($item) use (&$brOptaTeamMap) {
        return $brOptaTeamMap[$item['br_team_id']] = $item['opta_team_id'];
      });
    $this->brOptaTeamMap = $brOptaTeamMap;

    $this->brOptaLeagueSeasonMap = BrSeason::get(['opta_league_id', 'opta_season_id', 'br_season_id'])->keyBy('br_season_id')->toArray();
  }


  private function getBrIdNumber($_subject, $_brId)
  {
    $pattern = sprintf('/sr:%s:([0-9]*)/', $_subject);
    preg_match($pattern, $_brId, $matches);
    return $matches[1];
  }

  private function getOptaScheduleId(&$_brScheduleSet): string|null
  {
    $optaHomeTeamId = $this->brOptaTeamMap[$_brScheduleSet['br_home_team_id'] ?? null] ?? null;
    $optaAwayTeamId = $this->brOptaTeamMap[$_brScheduleSet['br_away_team_id'] ?? null] ?? null;
    $_brScheduleSet['opta_home_team_id'] = $optaHomeTeamId;
    $_brScheduleSet['opta_away_team_id'] = $optaAwayTeamId;
    $startTime = $_brScheduleSet['start_time'];

    $result = Schedule::whereLike('started_at', Carbon::parse($startTime)->format('Y-m-d'))
      ->where(function ($query) use ($optaHomeTeamId, $optaAwayTeamId) {
        $query->where('home_team_id', $optaHomeTeamId)
          ->orWhere('away_team_id', $optaAwayTeamId);
      })->first();
    if ($result !== null) {
      $result = $result['id'];
    }
    return $result;
  }


  protected function customParser($_parent_key, $_key, $_value)
  {
    if ($_key === 'schedules') {
      $valueSet = [];
      foreach ($_value as $idx => $scheduleSet) {
        $valueSet['sport_event_id'] = $this->getBrIdNumber('sport_event', $scheduleSet['sport_event']['id']);
        $valueSet['start_time'] = $scheduleSet['sport_event']['start_time'];
        $valueSet['br_league_id'] = $this->getBrIdNumber('competition', $scheduleSet['sport_event']['sport_event_context']['competition']['id']);
        $brSeasonId = $valueSet['br_season_id'] = $this->getBrIdNumber('season', $scheduleSet['sport_event']['sport_event_context']['season']['id']);
        $optaLeagueId = $this->brOptaLeagueSeasonMap[$brSeasonId]['opta_league_id'];
        $optaSeasonId = $this->brOptaLeagueSeasonMap[$brSeasonId]['opta_season_id'];
        $valueSet['opta_league_id'] = $optaLeagueId;
        $valueSet['opta_season_id'] = $optaSeasonId;
        $valueSet['round'] = $scheduleSet['sport_event']['sport_event_context']['round']['number'] ?? null;
        foreach ($scheduleSet['sport_event']['competitors'] as $competitorSet) {
          if ($competitorSet['qualifier'] === 'home') {
            $valueSet['br_home_team_id'] = $this->getBrIdNumber('competitor', $competitorSet['id']);
          } else if ($competitorSet['qualifier'] === 'away') {
            $valueSet['br_away_team_id'] = $this->getBrIdNumber('competitor', $competitorSet['id']);
          }
        }
        $valueSet['opta_schedule_id'] = $this->getOptaScheduleId($valueSet);
        // $valueSet['opta_schedule_id'];
        // $valueSet['opta_home_team_id'];
        // $valueSet['opta_away_team_id'];

        $this->appendTargetSpecifiedAttrsByIndex(
          'schedules',
          $idx,
          $valueSet,
        );
      }
    }
    if ($_key === 'contestant') {
      $this->contestantCommonNamingSnippet($_key, $_value);
    }
  }


  protected function customCommonParser($_parentKey, $_key, $_value)
  {
    // do nothing;
  }

  // public function start(bool $_act = false)
  // {
  //   return $this->parse($_act);
  // }

  // protected function parse(bool $_act)
  // {
  //   return $this->getCommentary();
  // }

  private function getDailyIds(): array
  {
    return BrSeason::optaCurrentSeason()
      ->get()->pluck('br_season_id')->toArray();
  }

  private function getAllIds(): array
  {
    return $this->getDailyIds();
  }


  protected function parse(bool $_act)
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
    $this->setKGsToCustom(['/schedules']);
    // optaParser 설정 <<--

    // 비동기 동시처리 수로 쪼개기
    $idChunks = array_chunk($ids, self::REQUEST_COUNT_AT_ONCE);
    $totalChucks = count($idChunks);
    foreach ($idChunks as $idx => $idChunk) {
      if (isset($this->param['chunk']) && $idx < $this->param['chunk']) {
        continue;
      }

      __loggerEx($this->feedType, 'loop $i : ' . $idx + 1 . ' / ' . $totalChucks);

      sleep(2);
      $responses = $this->betRadarRequest($idChunk);

      $this->insertOptaDatasToTables(
        $responses,
        // [
        //   'common_table_name' => MatchPreview::class,
        //   'conditions' => ['home_team_id', 'away_team_id'],
        // ],
        null,
        [
          [
            'specifiedInfoMap' => ['schedules' => BrSchedule::class],
            'conditions' => ['br_home_team_id', 'br_away_team_id']
          ],
        ],
        $_act
      );
    }
    (new GaRoundUpdator)->update();

    $parsingStatus = $this->setCompleteFantasyParsing();
    $this->wrapUpFantasyParsing($this->feedNick);
    return $parsingStatus;
  }
}
