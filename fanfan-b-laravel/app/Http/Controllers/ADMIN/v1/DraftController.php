<?php

namespace App\Http\Controllers\ADMIN\v1;

use App\Console\Commands\DataControll\PlateCardBase;
use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\Opta\Card\DraftCardStatus;
use App\Enums\Opta\Card\PlateCardStatus;
use App\Enums\Opta\YesNo;
use App\Enums\PlateCardFailLogType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Draft\CardOrderUpgradeRequest;
use App\Http\Requests\Admin\Draft\OverActivePostRequest;
use App\Http\Requests\Admin\Draft\OverCardPostRequest;
use App\Http\Requests\Admin\Draft\OverSquadPostRequest;
use App\Http\Requests\Admin\Draft\PlayerManageOverActiveRequest;
use App\Http\Requests\Admin\Draft\PlayerManageOverSquadRequest;
use App\Http\Requests\Admin\Draft\PlayerManageRequest;
use App\Http\Requests\Admin\Draft\PriceGradeRequest;
use App\Http\Requests\Admin\Fantasy\DraftPricesRequest;
use App\Http\Requests\Api\PlateCard\PlateCardListRequest;
use Symfony\Component\HttpFoundation\Response;
use App\Libraries\Classes\Exception;
use App\Models\data\Squad;
use App\Models\game\DraftSelection;
use App\Models\game\PlateCard;
use App\Models\log\DraftSelectionLog;
use App\Models\log\PlateCardFailLog;
use App\Models\meta\RefDraftPrice;
use App\Models\meta\RefPlateGradePrice;
use App\Models\order\DraftOrder;
use App\Models\order\OrderPlateCard;
use App\Services\Game\DraftService;
use Auth;
use DB;
use Illuminate\Contracts\Auth\Authenticatable;
use ReturnData;

class DraftController extends Controller
{
  protected int $limit;
  protected DraftService $draftService;

  public function __construct(DraftService $_draftService)
  {
    $this->limit = 20;
    $this->draftService = $_draftService;
  }

  public function getDraftPrices()
  {
    try {
      $result = RefDraftPrice::get()->map(function ($item) {
        unset($item['id']);
        return $item;
      });
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function setDraftPrices(DraftPricesRequest $_request)
  {
    $input = $_request->all();
    $input['base_prices']['level'] = 1;
    $input['base_prices']['rate'] = 1;

    /**
     * @var FantasyCalculator $draftCalculator
     */
    $draftCalculator = app(FantasyCalculatorType::FANTASY_DRAFT, [0]);

    $newPrices = $draftCalculator->makeDraftPriceTableData($input['base_prices'], $input['level_rates']);

    try {
      foreach ($newPrices as $row) {
        RefDraftPrice::updateOrCreateEx(
          ['level' => $row['level']],
          $row,
        );
      }

      $result = RefDraftPrice::get()->map(function ($item) {
        unset($item['id']);
        return $item;
      });
      return ReturnData::setData($result)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }


  public function playerManageOverActive(PlayerManageOverActiveRequest $request)
  {
    $input = $request->only([
      'league',
      'club',
      'player_name',
    ]);

    try {

      $playerIdList = PlateCardFailLog::where('fail_type', PlateCardFailLogType::OVERACTIVE)->pluck('player_id');
      $list = [];

      Squad::withTrashed()
        ->with('team')
        ->selectRaw("id,player_id, season_id, league_id, team_id," . implode(',', config('commonFields.player')) . ",position, updated_at")
        ->select(['id', 'player_id', 'season_id', 'league_id', 'team_id', 'position', 'first_name', 'last_name', 'short_first_name', 'short_last_name', 'match_name', 'known_name', 'updated_at'])
        ->whereIn('player_id', $playerIdList)
        ->currentSeason()
        ->activePlayers()
        ->applyFilters($input)
        ->get()
        ->map(function ($oneRow) use (&$list) {
          $oneRow->makeVisible('updated_at');
          $oneRow['match_name_eng'] = __removeAccents($oneRow['match_name']);
          $oneRow['first_name_eng'] = __removeAccents($oneRow['first_name']);
          $oneRow['last_name_eng'] = __removeAccents($oneRow['last_name']);

          $oneRow['player_name'] = $oneRow['first_name'] . ' ' . $oneRow['last_name'];
          $oneRow['short_player_name'] = $oneRow['short_first_name'] . ' ' . $oneRow['short_last_name'];
          $oneRow['player_name_eng'] = $oneRow['first_name_eng'] . ' ' . $oneRow['last_name_eng'];
          $oneRow['squad_id'] = $oneRow['id'];
          unset($oneRow['id']);

          $list[$oneRow['player_id']][] = $oneRow->toArray();
        });
      return ReturnData::setData($list)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function playerManageOverSquad(PlayerManageOverSquadRequest $request)
  {
    $input = $request->only([
      'league',
      'club',
      'player_name',
      'page',
      'per_page',
      'position',
      'active',
    ]);

    try {
      $this->limit = $input['per_page'];
      $playerIdList = PlateCardFailLog::where('fail_type', PlateCardFailLogType::OVERSQUAD)->pluck('player_id');

      $list = tap(
        Squad::withTrashed()
          ->with('team')
          ->selectRaw("id,player_id, season_id, league_id, team_id," . implode(',', config('commonFields.player')) . ",position, updated_at")
          ->select(['id', 'player_id', 'season_id', 'league_id', 'team_id', 'position', 'first_name', 'last_name', 'short_first_name', 'short_last_name', 'match_name', 'known_name', 'updated_at'])
          ->whereIn('player_id', $playerIdList)
          ->currentSeason()
          ->activePlayers()
          ->applyFilters($input)
          ->orderBy('id')
          ->paginate($this->limit, ['*'], 'page', $input['page'])
      )->map(function ($oneRow) {
        $oneRow->makeVisible('updated_at');
        $oneRow['match_name_eng'] = __removeAccents($oneRow['match_name']);
        $oneRow['first_name_eng'] = __removeAccents($oneRow['first_name']);
        $oneRow['last_name_eng'] = __removeAccents($oneRow['last_name']);

        $oneRow['player_name'] = $oneRow['first_name'] . ' ' . $oneRow['last_name'];
        $oneRow['short_player_name'] = $oneRow['short_first_name'] . ' ' . $oneRow['short_last_name'];
        $oneRow['player_name_eng'] = $oneRow['first_name_eng'] . ' ' . $oneRow['last_name_eng'];
        $oneRow['squad_id'] = $oneRow['id'];
        unset($oneRow['id']);

        return $oneRow;
      })->toArray();
      return ReturnData::setData(__setPaginateData($list, []), $request)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }



  public function playerManage(PlayerManageRequest $request, $type)
  {
    $input = $request->only([
      'league',
      'club',
      'player_name',
      'page',
      'per_page',
      'position',
      'active',
    ]);
    $failTypeContainer = [PlateCardFailLogType::OVERCARD];

    try {
      $this->limit = $input['per_page'];

      $list = tap(
        PlateCard::selectRaw("id,player_id, season_id, price_init_season_id, league_id, team_id, grade, price, " . implode(',', config('commonFields.player')) . ",position, updated_at, deleted_at")
          ->when($type === 'all', function ($query) use ($input) {
            $query->when($input['active'] === YesNo::YES, function ($query) use ($input) {
              // $query->has('league')->currentSeason();
              $query->isOnSale(true);
            })->when($input['active'] === YesNo::NO, function ($query) use ($input) {
              $query->has('league')->currentSeason()->onlyTrashed();
            })->when($input['active'] === 'non_salary', function ($query) use ($input) {
              $query->isOnSale(false);
            })->when($input['active'] === null, function ($query) use ($input) {
              $query->currentSeason()->withTrashed();
            });
          })
          ->when(in_array($type, $failTypeContainer), function ($query) use ($type) {
            $query->currentSeason()->whereHas('plateCardFailLog', function ($query) use ($type) {
              $query->where('fail_type', $type);
            });
          })
          ->applyFilters($input)
          ->orderByDesc('updated_at')
          ->orderByDesc('match_name_eng')
          ->paginate($this->limit, ['*'], 'page', $input['page'])
      )->map(function ($cardOne) {
        $cardOne->makeVisible(['deleted_at', 'updated_at']);
        $cardOne->active = false;
        if ($cardOne->deleted_at !== null) {
          $cardOne->active = false;
        } else if (
          $cardOne->season_id === $cardOne->price_init_season_id &&
          isset($cardOne->grade) &&
          isset($cardOne->price)
        ) {
          $cardOne->active = true;
        } else {
          $cardOne->active = null;
        }
        $result = [];
        $team = [];
        foreach (config('commonFields.team') as $columnName) {
          $team[$columnName] = $cardOne->team->{$columnName};
        }
        $result = $cardOne;
        unset($result['team']);
        $result['team'] = $team;
        return $result;
      })->toArray();

      return ReturnData::setData(__setPaginateData($list, []), $request)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function selectCard(OverActivePostRequest $_request)
  {
    //overactive
    DB::beginTransaction();
    try {
      $input = $_request->only([
        'squad_id',
        'player_id',
        'season_id',
        'club',
      ]);
      $squadRow = Squad::withTrashed()->with('league')->find($input['squad_id']);
      PlateCardFailLog::where([
        ['player_id', $input['player_id']],
        ['fail_type', PlateCardFailLogType::OVERACTIVE],
      ])->update(['done' => true]);
      (new PlateCardBase)->upsertOnePlateCard($squadRow->toArray());
      DB::commit();
      return ReturnData::setData([])->send(Response::HTTP_OK);
    } catch (Exception $th) {
      DB::rollBack();
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function makeCard(OverSquadPostRequest $_request)
  {
    // oversquad
    DB::beginTransaction();
    try {
      $input = $_request->only([
        'squad_id',
        'player_id',
        'season_id',
        'club',
      ]);
      $squadRow = Squad::withTrashed()->with('league')->find($input['squad_id']);
      PlateCardFailLog::where([
        ['player_id', $input['player_id']],
        ['fail_type', PlateCardFailLogType::OVERSQUAD],
      ])->update(['done' => true]);
      (new PlateCardBase)->upsertOnePlateCard($squadRow->toArray());
      DB::commit();
      return ReturnData::setData([])->send(Response::HTTP_OK);
    } catch (Exception $th) {
      DB::rollBack();
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function removeCard(OverCardPostRequest $_request)
  {
    // overcard
    DB::beginTransaction();
    try {
      $input = $_request->only([
        'plate_card_id',
        'player_id',
        'season_id',
        'club',
      ]);
      PlateCardFailLog::where([
        ['player_id', $input['player_id']],
        ['fail_type', PlateCardFailLogType::OVERCARD],
      ])->update(['done' => true]);
      PlateCard::where('player_id', $input['player_id'])->delete();
      DB::commit();
      return ReturnData::setData([])->send(Response::HTTP_OK);
    } catch (Exception $th) {
      DB::rollBack();
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }



  public function cards(PlateCardListRequest $request)
  {
    $input = $request->only([
      'league',
      'club',
      'player_name',
      'page',
      'per_page',
      'position',
      'other'
    ]);

    try {
      $this->limit = $input['per_page'];

      $list = tap(
        PlateCard::isOnSale()
          ->selectRaw("id,player_id, league_id, team_id, grade, price, " . implode(',', config('commonFields.player')) . ",position, updated_at")
          ->applyFilters($input)
          ->orderBy('grade')
          ->paginate($this->limit, ['*'], 'page', $input['page'])
      )->map(function ($cardOne) {
        $result = [];
        $cardOne->makeVisible('updated_at');
        $cardOne->league_code = $cardOne->league->league_code;
        $team = [];
        foreach (config('commonFields.team') as $columnName) {
          $team[$columnName] = $cardOne->team->{$columnName};
        }
        $result = $cardOne;
        unset($result['team']);
        $result['team'] = $team;
        return $result;
      })
        ->toArray();

      return ReturnData::setData(__setPaginateData($list, []), $request)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function orders(CardOrderUpgradeRequest $request)
  {
    $input = $request->only([
      'league',
      'team',
      'q',
      'page',
      'per_page',
    ]);

    $this->limit = $input['per_page'];
    try {
      $list = tap(
        OrderPlateCard::with([
          'leagueNoGlobal',
          'plateCardWithTrashed',
          'team',
          'order.user' => function ($query) {
            $query->withoutGlobalScope('excludeWithdraw');
          }
        ])
          ->when($input['league'], function ($leagueQuery, $league) {
            $leagueQuery->where('league_id', $league);
          })->when($input['team'], function ($teamQuery, $team) {
            $teamQuery->where('team_id', $team);
          })->when($input['q'], function ($whenQuery, $name) {
            $whenQuery->where(function ($query) use ($name) {
              $query->whereHas('plateCardWithTrashed', function ($plateCard) use ($name) {
                $plateCard->nameFilterWhere($name);
              })->orWhereHas('order.user', function ($user) use ($name) {
                $user->whereLike(['email', 'name'], $name)
                  ->withoutGlobalScope('excludeWithdraw');
              });
            });
          })
          ->latest()
          ->paginate($this->limit, ['*'], 'page', $input['page'])
      )->map(function ($info) {
          $info->league_code = $info->leagueNoGlobal->league_code;
          $info->name = $info->order->user->name;
          $info->total_price = $info->order->total_price;
          $info->makeVisible('created_at');
          foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
            $name[$field] = $info->plateCardWithTrashed->{$field};
          }
          $info->player_name = $name;

          foreach (config('commonFields.team') as $field) {
            $team[$field] = $info->team->{$field};
          }
          unset($info->team);
          $info->team = $team;
          unset($info->plateCardWithTrashed);
          unset($info->order);
          unset($info->leagueNoGlobal);
          return $info;
        })
        ->toArray();
      return ReturnData::setData(__setPaginateData($list, []), $request)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function upgrades(CardOrderUpgradeRequest $request)
  {
    $input = $request->only([
      'league',
      'team',
      'q',
      'page',
      'per_page',
      'status'
    ]);
    try {
      $this->limit = $input['per_page'];

      $list = tap(
        DraftSelectionLog::with(['league' => function ($query) {
          $query->withoutGlobalScope('serviceLeague');
        },
          'user'=> function ($userQuery) {
            $userQuery->withoutGlobalScope('excludeWithdraw');
          },
          'userPlateCard',
          'plateCardWithTrashed',
          'league',
          'team',
          'schedule'
          ])
          ->when($input['league'], function ($leagueQuery, $league) {
            $leagueQuery->where('league_id', $league);
          })->when($input['team'], function ($teamQuery, $team) {
            $teamQuery->where('team_id', $team);
          })->when($input['q'], function ($whenQuery, $name) {
            $whenQuery->where(function ($query) use ($name) {
              $query->whereHas('plateCardWithTrashed', function ($plateCard) use ($name) {
                $plateCard->nameFilterWhere($name);
              })->orWhereHas('user', function ($user) use ($name) {
                $user->whereLike(['email', 'name'], $name)
                  ->withoutGlobalScope('excludeWithdraw');
              });
            });
          })->withWhereHas('userPlateCard', function ($query) use ($input) {
            $query->withoutGlobalScope('excludeBurned')
              ->when($input['status'], function ($userPlateCard, $status) {
                if ($status === DraftCardStatus::COMPLETE) {
                  $userPlateCard->where('status', PlateCardStatus::COMPLETE);
                } else {
                  if ($status === DraftCardStatus::UPGRADING) {
                    $userPlateCard->where('status', PlateCardStatus::UPGRADING);
                  } else {
                    $userPlateCard->where('status', PlateCardStatus::PLATE);
                  }
                }
              });
          })->select('id', 'user_id', 'user_plate_card_id', 'player_id', 'league_id', 'team_id', 'schedule_id', 'selection_level as draft_level', 'selection_point as draft_point', 'summary_position as position', 'created_at')
          ->orderByDesc('id')
          ->paginate($this->limit, ['*'], 'page', $input['page'])
      )->map(function ($info) {
        $info->user_name = $info->user->name;
        unset($info->user);
        if (!is_null($info->userPlateCard?->position)) {
          $info->position = $info->userPlateCard->position;
        }

        foreach ([...config('commonFields.player'), ...config('commonFields.combined_player')] as $field) {
          $name[$field] = $info->plateCardWithTrashed->{$field};
        }

        $info->league_code = $info->league->league_code;

        if (!is_null($info->userPlateCard?->draft_team_id)) {
          $teamNames = $info->userPlateCard->draft_team_names;
          $team['id'] = $info->userPlateCard->draft_team_id;
          $team['code'] = $teamNames['team_code'];
          $team['name'] = $teamNames['team_name'];
          $team['short_name'] = $teamNames['team_short_name'];
        } else {
          foreach (config('commonFields.team') as $field) {
            $team[$field] = $info->team->{$field};
          }
        }
        unset($info->team);
        $info->player_name = $name;
        $info->team = $team;
        $info->makeVisible('created_at');

        // 해당 내역의 상태 확인하기
        $info->status = $info->userPlateCard?->status;
        $selectionScheduleId = DraftSelection::where('user_plate_card_id', $info->user_plate_card_id)->value('schedule_id');

        if (!is_null($selectionScheduleId) && $selectionScheduleId !== $info->schedule_id) {
          $info->status = PlateCardStatus::PLATE;
        }

        $info->card_grade = $info->userPlateCard?->card_grade;
        if (!is_null($info->userPlateCard?->draft_level)) {
          $info->draft_level = $info->userPlateCard->draft_level;
        }
        $info->schedule_status = $info->schedule->status;

        unset($info->plateCardWithTrashed);
        unset($info->userPlateCard);
        unset($info->league);
        unset($info->schedule);
      })->toArray();

      return ReturnData::setData(__setPaginateData($list, []), $request)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function draftDetail($_id)
  {
    try {
      $draftSelectionLog = DraftSelectionLog::whereId($_id);
      $list = $draftSelectionLog->clone()->with([
        'user' => function ($query) {
          $query->withoutGlobalScope('excludeWithdraw')->select(['id', 'name', 'status']);
        },
        'league:id,league_code',
        'userPlateCard' => function ($query) {
          $query->withoutGlobalScope('excludeBurned')->select([
            'id',
            'position',
            'card_grade',
            'draft_level'
          ]);
        },
        'plateCardWithTrashed:id,player_id,headshot_path,' . implode(',', config('commonFields.player')),
        'team:' . implode(',', config('commonFields.team')),
        'schedule:id,home_team_id,away_team_id,score_home,score_away,started_at',
        'schedule.home:' . implode(',', config('commonFields.team')),
        'schedule.away:' . implode(',', config('commonFields.team')),
      ])
        ->select('id', 'user_id', 'user_plate_card_id', 'player_id', 'league_id', 'team_id', 'schedule_id', 'user_name', 'selection_level as draft_level', 'selection_point as draft_point', 'summary_position as position', 'created_at')
        ->first()
        ->makeVisible('created_at');

      // Front DataSet 맞추기 위함
      $list['draft_team_id'] = $list->team_id;
      $list['plate_card_id'] = $list->plateCardWithTrashed->id;
      $list['card_grade'] = $list->userPlateCard->card_grade;

      $draftSeason['id'] = null;
      $draftSeason['name'] = null;
      $draftSeason['league'] = $list->league;
      $list['draft_season'] = $draftSeason;

      $draftTeamNames['team_code'] = $list->team->code;
      $draftTeamNames['team_name'] = $list->team->name;
      $draftTeamNames['team_short_name'] = $list->team->short_name;
      $list['draft_team_names'] = $draftTeamNames;

      unset($list->league);
      unset($list->team_id);
      unset($list->userPlateCard);
      unset($list->team);

      // 마지막 draft_order 찾기
      $list['draft_order'] = DraftOrder::where([
        ['user_id', $list->user_id],
        ['user_plate_card_id', $list->user_plate_card_id]
      ])->with(['user' => function ($query) {
        $query->withoutGlobalScope('excludeWithdraw')->select(['id', 'name', 'status']);
      }])
        ->select('user_id', 'user_plate_card_id', 'upgrade_point', 'upgrade_point_type', 'order_status')
        ->latest()->first()->toArray();

      $list['skills'] = $this->draftService->getDraftSelections($draftSelectionLog->first(), true);

      return ReturnData::setData($list)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function getGradePrices()
  {
    try {
      $data = RefPlateGradePrice::get(['grade', 'price'])
        ->map(function ($item) {
          $item->gold = $item->price;
          $item->cash = null;
          unset($item->price);
          return $item;
        });

      return ReturnData::setData($data)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }

  public function setGradePrices(PriceGradeRequest $_request)
  {
    try {
      $input = $_request->only(['grade', 'gold']);

      $data = RefPlateGradePrice::where([
        ['grade', $input['grade']],
      ])->first();
      $data->price = $input['gold'];
      $data->save();
      return ReturnData::setData($data)->send(Response::HTTP_OK);
    } catch (Exception $th) {
      $this->errorLog($th->getMessage());
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }
  }
}
