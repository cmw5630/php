<?php

namespace App\Http\Controllers;

use App\Libraries\Traits\LogTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *    title="Fantasy Soccer API Specification Document",
 *    version="1.0.0",
 * ),
 */
class Controller extends BaseController
{
  use AuthorizesRequests, DispatchesJobs, ValidatesRequests, LogTrait;
}
