<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Libraries\Classes\Exception;
use App\Services\Data\DataService;
use ReturnData;
use Symfony\Component\HttpFoundation\Response;

class PreFetchController extends Controller
{
  private DataService $dataService;

  public function __construct(DataService $_dataService)
  {
    $this->dataService = $_dataService;
  }
  public function __invoke()
  {
    try {
      $leagues = $this->dataService->leaguesQuery()
        ->withoutGlobalScopes()
        ->select([
          'id',
          'name',
          'league_code as code',
          'country_id',
          'country_code',
          'order_no',
          'status',
          'schedule_status',
        ])
        ->whereNotNull('order_no')
        ->orderBy('order_no')
        ->get()
        ->toArray();
    } catch (Exception $th) {
      return ReturnData::setError($th->getMessage())->send($th->getCode());
    }

    return ReturnData::setData(compact('leagues'))->send(Response::HTTP_OK);
  }
}
