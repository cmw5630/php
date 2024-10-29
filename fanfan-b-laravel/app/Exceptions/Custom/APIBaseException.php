<?php

namespace App\Exceptions\Custom;

use App\Enums\LogType;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class APIBaseException extends Exception
{
  static $id = '11';
  static $idSum = '11';
  protected $code = '11';
  protected $statusCode = Response::HTTP_BAD_REQUEST;
  public $ctx = [];
  public $cause = [];
  public $ctxP = null;
  public $logType = LogType::API;
  public function __construct(string $_message = null, array $_ctx = [], Throwable $_previousError = null, array $_datas = [], array $_cause = [])
  {
    $this->ctx = $_ctx;
    $this->cause = $_cause;
    $this->ctxP = $_previousError ?
      $this->makePreviousErrorContext($_previousError) : [];
  }

  public function makePreviousErrorContext(Throwable $_eP): array
  {
    $newTrace = $_eP->getTrace()[0];
    unset($newTrace['args']);
    return [
      'message' => $_eP->getMessage(),
      'file' => $_eP->getFile(),
      'line' => $_eP->getLine(),
      'traceback' => $newTrace,
    ];
  }

  public function getStatusCode()
  {
    return $this->statusCode;
  }
  public function getCause()
  {
    return $this->cause;
  }

  public function getCtx()
  {
    return $this->ctx;
  }

  public function getCtxP()
  {
    return $this->ctxP;
  }

  public function getLogType()
  {
    return $this->logType;
  }

  // can override
  protected function moreContext(array $_datas = []): array
  {
    $datas = $_datas; // 가공
    return $datas;
  }

  public function report()
  {
    // 텔레그램 알림 등등 처리
    return false; // 기본 error 채널, error handler 동작
    // return true; // error 채널, error handler 동작 안함.
  }

  // /**
  //  * Get the exception's context information.
  //  *
  //  * @return array
  //  */
  // public function context()
  // {
  //   return [ //];
  // }

  // public function render($request)
  // {
  // }
}
