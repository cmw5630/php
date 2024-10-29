<?php

namespace App\Exceptions\Custom\Parser;

use Log;
use Throwable;

use function Psy\debug;

class OptaSampleException extends OTPInsertException
{
  static $id = '77';
  static $idSum;
  protected $code;
  public function __construct(string $_message = null, array $_ctx = [], Throwable $_previousError = null, array $_datas = [])
  {
    parent::__construct(
      null,
      empty($_datas) ? $_ctx : array_merge($_ctx, $this->moreContext($_datas)),
      $_previousError
    );
    $this->message = $_message ?? 'Opta Request Error';
    self::$idSum = parent::$idSum . self::$id;
    $this->code = str_pad(sprintf('%s', self::$idSum), config('constant.ERROR_CODE_LENGTH'), 0);
  }

  // can override
  protected function moreContext(array $_datas = []): array
  {
    $datas = [];
    $datas['a'] = $_datas['a'] + 1; // 가공
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
