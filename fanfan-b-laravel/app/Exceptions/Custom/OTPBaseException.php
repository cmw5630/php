<?php

namespace App\Exceptions\Custom;

use App\Enums\LogType;
use Exception;
use Throwable;

class OTPBaseException extends Exception
{
  static $id = '55';
  static $idSum = '55';
  protected $code = '55';
  public $ctx = [];
  public $ctxP = null;
  public $logType = LogType::PARSER;
  public function __construct(string $_message = null, array $_ctx = [], Throwable $_previousError = null)
  {
    $this->ctx = $_ctx;
    $this->ctxP = $_previousError ? $this->makePreviousErrorContext($_previousError) : [];
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
    $datas = ['data' => $_datas]; // 가공
    return $datas;
  }

  public function report()
  {
    // 텔레그램 알림 등등 처리
    return false; // 기본 error 채널, error handler 동작
    // return true; // 기본 error 채널, error handler 동작 안함.
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
