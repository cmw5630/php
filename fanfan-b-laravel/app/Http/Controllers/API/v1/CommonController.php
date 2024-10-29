<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\API\HeaderSearchRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Common\AlarmListRequest;
use App\Http\Requests\Api\Common\AlarmUpdateRequest;
use App\Http\Requests\Api\Common\CodeListRequest;
use App\Http\Requests\Api\Common\RoundSchedulesRequest;
use App\Libraries\Classes\Exception;
use App\Models\alarm\AlarmLog;
use App\Models\alarm\AlarmRead;
use App\Models\Code;
use App\Models\user\User;
use App\Services\Data\DataService;
use ReturnData;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CommonController extends Controller
{
  protected int $limit = 20;

  protected DataService $dataService;

  public function __construct(DataService $_dataService)
  {
    $this->dataService = $_dataService;
  }

  public function leaguesWithRound()
  {
    try {
      $data = $this->dataService->leaguesWithRound();

      return ReturnData::setData($data)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function roundSchedules(RoundSchedulesRequest $request)
  {
    $input = $request->only([
      'season',
      'round'
    ]);

    $input['status'] = null;
    try {
      $data = $this->dataService->getSchedules($input);

      return ReturnData::setData($data)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function headerSearch(HeaderSearchRequest $request)
  {
    $input = $request->only([
      'q',
    ]);

    $input['name'] = $input['q'];
    unset($input['q']);
    $players = $this->dataService->getPlayers($input, 4, ['player_id', 'league_id', 'season_id', 'team_id', 'headshot_path', ...config('commonFields.player')]);
    $leagues = $this->dataService->getLeagues($input, 3, ['id', 'name']);
    $teams = [];
    $input['season'] = null;
    $this->dataService->getTeams($input, 3, ['team_id', 'code', 'name', 'short_name', 'season_id'])
      ->map(function ($item) use (&$teams) {
        $team['id'] = $item->team_id;
        $team['league_id'] = $item->season->league_id;
        $team['code'] = $item->code;
        $team['name'] = $item->name;
        $team['short_name'] = $item->short_name;
        $team['league_code'] = $item->season->league->league_code;
        $teams[] = $team;
      });

    return ReturnData::setData(compact('players', 'leagues', 'teams'))->send(Response::HTTP_OK);
  }

  public function alarmList(AlarmListRequest $request)
  {
    $filter = $request->only([
      'offset',
      'limit',
    ]);

    $this->limit = $filter['limit'];
    try {
      $myJoinDate = User::where('id', $request->user()->id)->value('created_at');
      $lastMonth = now()->subMonth();
      if ($lastMonth->isBefore($myJoinDate)) {
        $lastMonth = $myJoinDate;
      }
      $hasNew = AlarmLog::whereDoesntHave('alarmRead', function ($alarmLog) use ($request) {
        $alarmLog->where('user_id', $request->user()->id);
      })
        ->where(function ($query) use ($request) {
          $query->where('user_id', $request->user()->id)
            ->orWhereNull('user_id');
        })
        ->where('created_at', '>', $lastMonth)
        ->exists();

      $alarmLog = AlarmLog::withCount([
        'alarmRead as is_read' => function ($query) use ($request) {
          $query->where('user_id', $request->user()->id);
        }
      ])
        ->where(function ($query) use ($request) {
          $query->where('user_id', $request->user()->id)
            ->orWhereNull('user_id');
        })
        ->where('created_at', '>', $lastMonth)
        ->when($filter['offset'], function ($query, $offset) {
          $query->where('id', '<', $offset);
        })
        ->limit($this->limit)
        ->latest()
        ->get()
        ->map(function ($item) {
          $item->makeVisible('created_at');
          $alert = app('alarm',
            ['id' => $item->alarm_template_id])->setAlarmLog($item)->params($item->dataset);
          foreach ($alert->getConvertedData() as $key => $val) {
            $item->{$key} = $val;
          }
          // 불필요한 데이터 제거
          unset($item->alarmTemplate, $item->dataset, $item->user_id);

          return $item;
        });

      $hasMore = null;
      if (!is_null($alarmLog->last())) {
        $hasMore = AlarmLog::where('created_at', '>', now()->subMonth())
          ->where(function ($query) use ($request) {
            $query->where('user_id', $request->user()->id)
              ->orWhereNull('user_id');
          })
          ->where('id', '<', $alarmLog->last()->id)
          ->limit($this->limit)
          ->exists();
      }

      $result = [
        'list' => $alarmLog,
        'has_new' => $hasNew,
        'has_more' => $hasMore,
      ];

    } catch (Throwable $th) {
      return ReturnData::setError($th->getMessage())->send(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return ReturnData::setData($result, $request)->send(Response::HTTP_OK);
  }

  public function alarmUpdate(AlarmUpdateRequest $request)
  {
    $input = $request->only('alarm_id');

    try {
      if (!isset($input['alarm_id'])) {
        $logIds = AlarmLog::query()
          ->where(function ($query) use ($request) {
            $query->where('user_id', $request->user()->id)
              ->orWhereNull('user_id');
          })
          ->where('created_at', '>', now()->subMonth())
          ->pluck('id')->toArray();

        foreach ($logIds as $id) {
          AlarmRead::updateOrCreate(
            [
              'user_id' => $request->user()->id,
              'alarm_log_id' => $id,
            ],
            []);
        }
      } else {
        AlarmRead::updateOrCreate(
          [
            'user_id' => $request->user()->id,
            'alarm_log_id' => $input['alarm_id'],
          ],
          []);
      }



    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
    return ReturnData::send(Response::HTTP_OK);
  }

  public function codes(CodeListRequest $request)
  {
    $filter = $request->only('category');

    $codes = [];

    Code::when($filter['category'], function ($whenCate, $category) {
      $whenCate->whereIn('category', $category);
    }, function ($query) {
      $query->whereNull('code');
    })
      ->get()
      ->groupBy('category')
      ->map(function ($group) use (&$codes) {
        $code = [];
        $group->sortBy('order_no')->map(function ($item) use (&$code) {
          if (is_null($item->code)) {
            $code = $item->toArray();
          } else {
            $code['list'][] = $item->toArray();
          }
        });
        $codes[] = $code;
      });

    return ReturnData::setData($codes)->send(Response::HTTP_OK);
  }
}
