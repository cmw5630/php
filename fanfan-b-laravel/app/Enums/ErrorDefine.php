<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class ErrorDefine extends Enum
{
  const BAD_REQUEST =           'BAD_REQUEST';
  const FAIL_AUTHORIZATION =    'FAIL_AUTHORIZATION';
  const NOT_FOUND_API =         'NOT_FOUND_API';
  const DENY_USER =             'DENY_USER';
  const NOT_ALLOW =             'NOT_ALLOW';
  const EXPIRED_TOKEN =         'EXPIRED_TOKEN';
  const NEED_TOKEN =            'NEED_TOKEN';
  const VERIFY_TOKEN_FAIL =     'VERIFY_TOKEN_FAIL';
  const TIME_OUT =              'TIME_OUT';
  const WITHDRAWAL_USER =       'WITHDRAWAL_USER';
  const VALIDATION_ERROR =      'VALIDATION_ERROR';
  const TOO_MANY_REQUEST =      'TOO_MANY_REQUEST';
  const INTERNAL_SERVER_ERROR = 'INTERNAL_SERVER_ERROR';

  // GAME
  const UNKNOWN_POSITION =      'UNKNOWN_POSITION';
  const NO_MATCH_POSITION =     'NO_MATCH_POSITION';
  const POSITION_DUPLICATED =   'POSITION_DUPLICATED';
  const NO_MATCH_TEAMS =        'NO_MATCH_TEAMS';
  const JOIN_CLOSED =           'JOIN_CLOSED';
  const JOIN_DUPLICATED =       'JOIN_DUPLICATED';
  const PARTICIPANT_LIMITED =   'PARTICIPANT_LIMITED';
  const NOT_JOINED  =           'NOT_JOINED';
  const GAME_CANCELED  =        'GAME_CANCELED';

  // CHAT / COMMENT
  const CANNOT_ADDED_COMMENT =  'CANNOT_ADDED_COMMENT';
  const DO_NOT_USE_SLANG =      'DO_NOT_USE_SLANG';
}
