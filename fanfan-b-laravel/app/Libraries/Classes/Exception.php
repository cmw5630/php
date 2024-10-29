<?php

namespace App\Libraries\Classes;

use Exception as BaseException;

// exception에 array를 사용하기 위해 확장함
class Exception extends BaseException
{
  public function __construct($message = null, $code = 0, Exception $previous = null)
  {
    parent::__construct(is_array($message) ? json_encode($message, JSON_UNESCAPED_UNICODE) : $message, $code, $previous);
  }

  public function getMessageEx($assoc = false, $notification = false, $notification_info = [])
  {
    $message = $this->getMessage();

    if ($notification === true) {
      SystemError::regSystemErrorLog([
        'command' => $notification_info['command'],
        'message' => $message,
        'path' => $notification_info['path']
      ]);
    }

    return $this->jsonValidate($message) ? json_decode($message, $assoc) : $message;
  }

  private function jsonValidate($string)
  {
    // decode the JSON data
    json_decode($string);

    // switch and check possible JSON errors
    switch (json_last_error()) {
      case JSON_ERROR_NONE:
        $error = ''; // JSON is valid // No error has occurred
        break;
      case JSON_ERROR_DEPTH:
        $error = 'The maximum stack depth has been exceeded.';
        break;
      case JSON_ERROR_STATE_MISMATCH:
        $error = 'Invalid or malformed JSON.';
        break;
      case JSON_ERROR_CTRL_CHAR:
        $error = 'Control character error, possibly incorrectly encoded.';
        break;
      case JSON_ERROR_SYNTAX:
        $error = 'Syntax error, malformed JSON.';
        break;
        // PHP >= 5.3.3
      case JSON_ERROR_UTF8:
        $error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
        break;
        // PHP >= 5.5.0
      case JSON_ERROR_RECURSION:
        $error = 'One or more recursive references in the value to be encoded.';
        break;
        // PHP >= 5.5.0
      case JSON_ERROR_INF_OR_NAN:
        $error = 'One or more NAN or INF values in the value to be encoded.';
        break;
      case JSON_ERROR_UNSUPPORTED_TYPE:
        $error = 'A value of a type that cannot be encoded was given.';
        break;
      default:
        $error = 'Unknown JSON error occured.';
        break;
    }

    if ($error !== '') {
      // throw the Exception or exit // or whatever :)
      return false;
    }

    // everything is OK
    return true;
  }
}
