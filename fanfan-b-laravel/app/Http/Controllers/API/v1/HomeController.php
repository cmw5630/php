<?php

namespace App\Http\Controllers\API\v1;

use App\Enums\Opta\Player\PlayerPosition;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Home\BestLineupRequest;
use App\Libraries\Classes\Exception;
use App\Libraries\Traits\CommonTrait;
use App\Models\data\League;
use App\Models\data\Season;
use App\Models\game\PlateCard;
use App\Services\Data\DataService;
use App\Services\Data\StatService;
use App\Services\Game\DraftService;
use App\Services\Game\GameService;
use DB;
use Illuminate\Support\Facades\Redis;
use ReturnData;
use Symfony\Component\HttpFoundation\Response;

class HomeController extends Controller
{
  use CommonTrait;
  private DraftService $draftService;
  private GameService $gameService;
  private DataService $dataService;
  private StatService $statService;

  public function __construct(
    DraftService $_draftService,
    GameService $_gameService,
    DataService $_dataService,
    StatService $_statService
  ) {
    $this->draftService = $_draftService;
    $this->gameService = $_gameService;
    $this->dataService = $_dataService;
    $this->statService = $_statService;
  }

  public function main()
  {
    try {
      $hasPopularData = true;

      $popularByPosition = [
        PlayerPosition::ATTACKER => [],
        PlayerPosition::MIDFIELDER => [],
        PlayerPosition::DEFENDER => [],
        PlayerPosition::GOALKEEPER => []
      ];

      if (Redis::exists($this->getRedisCachingKey('plate_card_daily_tops','',  now()->subDay()->format('Y-m-d')))) {
        $jsonData = json_decode(Redis::get($this->getRedisCachingKey('plate_card_daily_tops','',  now()->subDay()->format('Y-m-d'))), true);
        foreach ($jsonData as $item) {
          $player = PlateCard::where('player_id', $item['player_id'])->first();
          foreach (config('commonFields.team') as $field) {
            $team[$field] = $player->team->{$field};
          }
          foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
            $item[$field] = $player->{$field};
          }
          $item['plate_card_id'] = $player->id;
          $item['team_id'] = $player->team_id;
          $item['headshot_path'] = $player->headshot_path;
          $item['team'] = $team;

          $popularByPosition[$item['position']][] = $item;
        }
      }

      foreach ($popularByPosition as $player) {
        if (empty($player)) {
          $hasPopularData = false;
          break;
        }
      }

      if (!$hasPopularData) {
        // 무조건 지난 시즌 데이터로 처리
        $defaultLeagueId = League::defaultLeague()->id;
        $beforeSeasonId = Season::getBeforeCurrentMapCollection()->where('league_id', $defaultLeagueId)->value('before_id');
        // todo: 포지션별 하나라도 없으면 포지션별 지정된 선수 출력
        PlateCard::whereIn('player_id', [
          'atzboo800gv7gic2rgvgo0kq1',
          'dqfk7czrayirrvonirtk2mk15',
          'dxze3b3fsfl814bjcs7q6wcet',
          '2f79rc4i712sxstahjkq6pxp1'
        ])->with('team')
          ->selectRaw('id AS plate_card_id,player_id,season_id,position,headshot_path,team_id,' . implode(',', config('commonFields.player')))
          ->get()
          ->map(function ($item) use (&$popularByPosition, $beforeSeasonId) {
            $seasonStat = $this->statService->getSeasonStatSummary($item->player_id, $beforeSeasonId);
            foreach (config('commonFields.team') as $field) {
              $team[$field] = $item->team->{$field};
            }
            $popularByPosition[$item->position][] = array_merge($item->toArray(), $seasonStat['stat'], ['team' => $team]);
          });
      }

      $popular = [];

      foreach ($popularByPosition as $pos => $item) {
        $popular[$pos] = $item[0] ?? null;
      }

      $games = $this->gameService->getHomeGames();

      $banners = $this->getBanners([10, 19]);

      $eplId = League::defaultLeague()->id;
      $defaultLeague = League::whereHas('seasons', function ($query) {
        $query->where([
          ['start_date', '<=', now()->toDateString()],
          ['end_date', '>=', now()->toDateString()]
        ]);
      })
        ->orderByRaw("
        case when id = '{$eplId}' then 1
        else 2 end
        ")
        ->orderBy('league_code')
        ->value('id');

      $result = [
        'popular' => $popular,
        'games' => $games,
        'banners' => $banners,
        'default_league_id' => $defaultLeague,
      ];
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
    return ReturnData::setData($result)->send(Response::HTTP_OK);
  }

  public function bestLineup(BestLineupRequest $request)
  {
    $input = $request->only([
      'season',
      'round',
      'schedule',
    ]);

    // $input['status'] = ScheduleStatus::PLAYED;
    $input['status'] = null;

    try {
      $options = $this->dataService->leaguesWithRound();
      $schedules = $this->dataService->getSchedules($input, false);
      $bestLineup = $this->dataService->bestLineup($input);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }

    return ReturnData::setData(compact('schedules', 'bestLineup', 'options'))->send(Response::HTTP_OK);
  }
}
