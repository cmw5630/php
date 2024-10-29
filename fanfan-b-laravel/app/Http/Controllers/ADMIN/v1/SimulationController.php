<?php

namespace App\Http\Controllers\ADMIN\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Simulation\UploadRefSequenceRequest;
use App\Imports\SequenceImport;
use App\Imports\SimulationSequenceImport;
use App\Jobs\JobSimulationSequenceImport;
use App\Jobs\JobPlateCardChangeUpdate;
use App\Libraries\Classes\SimulationSequenceUpdater;
use DB;
use Excel;
use Exception;
use ReturnData;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SimulationController extends Controller
{
  //
  public function uploadRefSequence(UploadRefSequenceRequest $request)
  {
    $input = $request->only([
      'file',
    ]);
    try {
      Excel::queueImport(new SimulationSequenceImport, $input['file']);
    } catch (Throwable $th) {
      return ReturnData::setError($th->getMessage())->send(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    return ReturnData::send(Response::HTTP_OK);

  }
}
