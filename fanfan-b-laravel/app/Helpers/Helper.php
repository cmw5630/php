<?php

use App\Enums\System\NotifyLevel;
use App\Enums\GradeCardLockStatus;
use App\Libraries\Classes\SendAction;
use App\Models\Code;
use App\Models\game\Game;
use App\Models\user\UserPlateCard;
use App\Notifications\TelegramDebugNotification;
use Psr\Log\LogLevel;

if (!function_exists('__setPaginateData')) {
  function __setPaginateData(array $_data, array $_condition, array $_extra = []): ?array
  {
    return $_condition + [
      'paginate' => [
        'current_page' => $_data['current_page'],
        'from' => $_data['from'],
        'to' => $_data['to'],
        'total' => $_data['total'],
        'per_page' => $_data['per_page'],
        'last_page' => $_data['last_page'],
      ],
      'list' => $_data['data'],
    ] + $_extra;
  }
}

if (!function_exists('__findKeyAll')) {
  function __findKeyAll($arr = [], $find = '', &$result = [], &$depth = [])
  {
    static $count = 0;
    foreach ($arr as $key => $value) {
      if (is_array($value) && !empty($value)) {
        $depth[] = $key;
        $result[$count] = $depth;
        __findKeyAll($value, $find, $result, $depth);
      } else {
        if ($value == $find) {
          $result[$count++][] = $key;
        }
      }
      if (array_key_last($arr) === $key) {
        unset($result[$count]);
        array_pop($depth);
      }
      if ($count > 0) {
        $result = array_values($result);
      }
    }

    return false;
  }
}

if (!function_exists('__getArrayDepth')) {
  function __getArrayDepth($array)
  {
    $depth = 0;
    $iteIte = new RecursiveIteratorIterator(new RecursiveArrayIterator($array));

    foreach ($iteIte as $ite) {
      $d = $iteIte->getDepth();
      $depth = $d > $depth ? $d : $depth;
    }

    return $depth;
  }
}

if (!function_exists('__sortByKey')) {
  function __sortByKey($array, $key, $direction = 'DESC')
  {
    switch (strtoupper($direction)) {
      case "ASC":
        usort($array, function ($first, $second) use ($key) {
          if (Str::contains($key, '.')) {
            foreach (explode('.', $key) as $depth) {
              $first = $first[$depth];
              $second = $second[$depth];
            }
          } else {
            $first = $first[$key];
            $second = $second[$key];
          }
          return $first <=> $second;
        });
        break;
      case "DESC":
        usort($array, function ($first, $second) use ($key) {
          if (Str::contains($key, '.')) {
            foreach (explode('.', $key) as $depth) {
              if (!isset($firstValue) || !isset($secondValue)) {
                $first = $first[$depth];
                $second = $second[$depth];
              }
            }
          } else {
            $first = $first[$key];
            $second = $second[$key];
          }
          return $second <=> $first;
        });
        break;
      default:
        break;
    }

    return $array;
  }
}

if (!function_exists('__sortByKeys')) {
  function __sortByKeys($array, $keysHows, $direction = 'DESC')
  {
    if (gettype($keysHows) === 'string') {
      $keysHows = ['keys' => [$keysHows], 'hows' => [$direction]];
    } else {
      $keysHowsdiff = count($keysHows['keys']) - count($keysHows['hows']);
      $hows = $keysHows['hows'];
      if ($keysHowsdiff > 0) {
        for ($i = 0; $i < $keysHowsdiff; $i++) {
          array_push($hows, $direction);
        }
        $keysHows['hows'] = $hows;
      }
      $hows_unique = array_values(array_unique($hows));
      if (count($hows_unique) > 2) throw new Exception('정렬 hows(의) 개수가 keys보다 많습니다.');
      for ($i = 0; $i < count($hows_unique); $i++) {
        if (!in_array(strtoupper($hows_unique[$i]), ['ASC', 'DESC'])) throw new Exception('정렬 (parameter)오류');
      }
    }

    usort($array, function ($first, $second) use ($array, $keysHows) {
      while (True) {
        if (empty($keysHows['keys'])) {
          return 0;
        }
        $keys = $keysHows['keys'];
        $hows = $keysHows['hows'];

        $compare_key = array_shift($keys);
        $compare_how = array_shift($hows);
        $keysHows = ['keys' => $keys, 'hows' => $hows];
        if ($first[$compare_key] === $second[$compare_key]) continue;

        if (strtoupper($compare_how) === 'ASC') {
          return $first[$compare_key] <=> $second[$compare_key];
        } else if (strtoupper($compare_how) === 'DESC') {
          return $second[$compare_key] <=> $first[$compare_key];
        }
      }
    });

    return $array;
  }
}

if (!function_exists('__loggerEx')) {
  function __loggerEx($_fileName, $_message)
  {
    // LogEx의 log level이 적용되지 않는 부분을 임시로 해결하는 헬퍼
    if (env('LOG_LEVEL', 'debug') === LogLevel::DEBUG) {
      LogEx::debug($_fileName, $_message);
    }
  }
}

if (!function_exists('__setSchedulerLog')) {
  function __setSchedulerLog(...$logs)
  {
    if (count($logs) < 3) {
      return;
    }

    static $startTime = null;
    $lastArg = $logs[count($logs) - 1];
    if ($lastArg === true) {
      $startTime = __microtime();

      array_pop($logs);

      array_walk($logs, function (&$log) {

        if (is_array($log)) {
          $log = json_encode($log, JSON_UNESCAPED_UNICODE);
        } else if (__isCollection($log)) {
          $log = json_encode($log->toArray(), JSON_UNESCAPED_UNICODE);
        } else if (is_object($log)) {
          $log = json_encode((array)$log, JSON_UNESCAPED_UNICODE);
        }
      });

      __loggerEx($logs[1], Str::padLeft('', 100, '-'));
      __loggerEx($logs[1], implode(' ', Arr::except($logs, 1)));
    } else if ($lastArg === false) {
      if ($startTime) {
        $timeLog = 'time:' . number_format(__microtime() - $startTime, 5);
        $startTime = null;

        array_pop($logs);
        $logs[] = $timeLog;
        __loggerEx($logs[1], implode(' ', Arr::except($logs, 1)));
      }
    } else {
      $logText = implode(' ', $logs);
      __loggerEx($logs[1], $logText);
    }
  }
}

if (!function_exists('__ranking')) {
  function __ranking($array, $_keyName)
  {
    $ranking = [];
    foreach ($array as &$first) {
      $key = 0;
      foreach ($array as $second) {
        if ($second[$_keyName] > $first[$_keyName]) {
          $key++;
        }
      }
      $ranking[$key][] = $first;
    }

    return $ranking;
  }
}

if (!function_exists('__isCollection')) {
  function __isCollection($param)
  {
    return (bool) (($param instanceof \Illuminate\Support\Collection) || ($param instanceof \Illuminate\Database\Eloquent\Collection));
  }
}

if (!function_exists('__microtime')) {
  function __microtime()
  {
    return array_sum(explode(' ', microtime()));
  }
}

if (!function_exists('__changeKeyName')) {
  function __changeKeyName($array, $old, $new)
  {
    foreach ($array as $key => $value) {
      if (is_array($value) && !empty($value)) {
        $array[$key] = __changeKeyName($array[$key], $old, $new);
      } else if ($key === $old) {
        $array[$new] = $array[$old];

        unset($array[$old]);
      }
    }

    return $array;
  }
}

if (!function_exists('__viewQuery')) {
  function __viewQuery($query)
  {
    return Str::replaceArray('?', $query->getBindings(), $query->toSql());
  }
}

if (!function_exists('__setDecimal')) {
  function __setDecimal($data, $precision, $math = 'round')
  {
    if ($precision === 0) {
      return $math($data);
    }
    switch ($math) {
      case 'ceil': // 소수점 자리 지정 올림
        $number = explode('.', $data);
        if (count($number) === 2) {
          $number[1] = substr_replace($number[1], '', $precision + 1, strlen($number[1]) - $precision - 1);
          $data = $number[0] . '.' . $number[1];
          $data = bcdiv(ceil(bcmul($data, pow(10, $precision), $precision)), pow(10, $precision), $precision);
        }
        break;
      case 'floor': // 소수점 자리 지정 내림
        $number = explode('.', $data);
        if (count($number) === 2) {
          $number[1] = substr_replace($number[1], '', $precision, strlen($number[1]) - $precision);
          if ($number[0] < 0) $number[1]++;
          $data = $number[0] . '.' . $number[1];
          $data = bcdiv(floor(bcmul($data, pow(10, $precision), $precision + 1)), pow(10, $precision), $precision);
        }
        break;
      default:
        $data = round((float) $data, $precision);
        break;
    }
    return (float) $data;
  }
}


if (!function_exists('__reverseKey')) {
  function __reverseKey($data)
  {
    foreach ($data as $key => $value) {
      $data[Str::reverse($key)] = $value;
      unset($data[$key]);
    }

    return $data;
  }
}

if (!function_exists('__replaceValueArray')) {
  function __replaceValueArray(array $_data, string $_search = '/', string $_replace = '')
  {
    foreach ($_data as &$value) {
      if (!is_array($value)) {
        $value = Str::startsWith($value, $_search) ? Str::replaceFirst(
          $_search,
          $_replace,
          $value
        ) : $value;
      } else {
        $value = __replaceValueArray($value);
      }
    }

    return $_data;
  }
}

if (!function_exists('__isJsonData')) {
  function __isJsonData($data)
  {
    if (!empty($data)) {
      return is_string($data) &&
        is_array(json_decode($data, true));
    }
    return false;
  }
}

if (!function_exists('__parseApiURI')) {
  function __parseApiURI(array $_data)
  {
    return preg_replace_callback('/\{([^\}]+)\}/', function ($match) use ($_data) {
      return $_data['data'][$match[1]] ?? $match[0];
    }, $_data['endPoint']);
  }
}

if (!function_exists('__originTable')) {
  function __originTable($_tableName)
  {
    return DB::connection('origin')
      ->table($_tableName);
  }
}

if (!function_exists('__getBrowserName')) {
  function __getBrowserName($agent)
  {
    $agent = Str::lower($agent);
    // 엣지, 파폭, 크롬, 사파리, 오페라
    $browserList = ['edge', 'edg/', 'firefox', 'chrome', 'safari', 'opr'];
    $browserName = 'other';

    foreach ($browserList as $browser) {
      if (strpos($agent, $browser)) {
        if ($browser === 'edg/') {
          $browserName = 'edge';
        } else if ($browser === 'opr') {
          $browserName = 'opera';
        } else {
          $browserName = $browser;
        }
        break;
      }
    }

    return Str::ucfirst($browserName);
  }
}

if (!function_exists('__removeAccents')) {
  function __removeAccents($_str)
  {
    $accents = config('constant.accent_alphabets');
    $asis = array_keys($accents);
    $tobe = array_values($accents);
    return str_replace($asis, $tobe, $_str);
  }
}

if (!function_exists('__getDefault')) {
  function __getDefault(array $_array, string $_key, $_default = null)
  {
    if (isset($_array[$_key])) {
      return $_array[$_key];
    }
    return $_default;
  }
}



if (!function_exists('__telegramNotify')) {
  function __telegramNotify(string $_notifyLevel, $_subject, $_message)
  {
    $channels = [
      NotifyLevel::DEBUG => env('TELEGRAM_' . strtoupper(env('APP_ENV')) . '_BOT_CHAT_ID'),
      NotifyLevel::INFO => env('TELEGRAM_' . strtoupper(env('APP_ENV')) . '_BOT_CHAT_ID'),
      NotifyLevel::WARN => env('TELEGRAM_' . strtoupper(env('APP_ENV')) . '_BOT_CHAT_ID'),
      NotifyLevel::CRITICAL => env('TELEGRAM_' . strtoupper(env('APP_ENV')) . '_BOT_CHAT_ID'),
    ];
    try {
      $sendAction = SendAction::getInstance();
      $ip = $sendAction->send('get', ['https://api.ip.pe.kr/json']);
      Notification::route('telegram', $channels[$_notifyLevel])->notify(new TelegramDebugNotification('(' . Str::upper($_notifyLevel) . ') ' . '-' . $ip[0]['ip'] . '-' . $_subject, $_message));
    } catch (\Exception $e) {
      logger('telegram request error:' . $e->getMessage());
      // logger('telegram message message:' . $_message ?? 'error');
    }
  }
}


if (!function_exists('__canAccessUserPlateCard')) {
  function __canAccessUserPlateCardWithLock(int $_userPlateCardId, string $_checkAccessType, ?bool $_boolCasting = true): bool|string
  {
    // $_checkAccessType으로 UserPlateCard를 접근여부 검사
    $baseQuery = UserPlateCard::whereId($_userPlateCardId);

    $currentLockStatus = $baseQuery->clone()->value('lock_status');
    $futureStatus = config('lockstatus.enter')[$_checkAccessType][$currentLockStatus];

    if ($_boolCasting) {
      $futureStatus = (bool)$futureStatus;
    }
    // if ($baseQuery->whereNull('lock_status')->exists()) {
    //   return $futureStatus;
    // }

    // $canAccess = false;

    // switch ($_checkAccessType) {
    //   case GradeCardLockStatus::INGAME:
    //   case GradeCardLockStatus::SIMULATION:
    //     // user_card 하나로 여러 경기를 참가할 수 있으므로
    //     if ($currentLockStatus === GradeCardLockStatus::INGAME || $currentLockStatus === GradeCardLockStatus::SIMULATION)
    //       $canAccess = true;
    //     break;
    //   case GradeCardLockStatus::MARKET:
    //   default:
    //     # code...
    //     break;
    // }
    // return $canAccess;
    return $futureStatus;
  }
}


if (!function_exists('__startUserPlateCardLock')) {
  function __startUserPlateCardLock(int $_userPlateCardId, string $_checkAccessType): bool
  {
    $futureStatus = __canAccessUserPlateCardWithLock($_userPlateCardId, $_checkAccessType, false);
    if ((bool)$futureStatus) {
      $userPlateCardInst = UserPlateCard::whereId($_userPlateCardId)->first();
      // if ($userPlateCardInst->lock_status === null) {
      $userPlateCardInst->lock_status = $futureStatus;
      $userPlateCardInst->save();
      // }
      return true;
    }
    return false;
  }
}

if (!function_exists('__endUserPlateCardLock')) {
  function __endUserPlateCardLock(
    int $_userPlateCardId,
    string $_checkAccessType,
    string|null $_currentScheduleId = null, // INGAME 일 때 schedule_id 필요
  ) {
    if ($_checkAccessType === GradeCardLockStatus::INGAME && $_currentScheduleId === null) {
      logger('INGAME lock_status를 다룰 때는 schedule_id 파라미터 필요!!!');
      throw new Exception('현재 schedule_id 필요');
    } else if ($_checkAccessType === GradeCardLockStatus::INGAME) {
      if (Game::isThereAnotherProceedingGame($_userPlateCardId, $_currentScheduleId)) { // 하나의 카드가 여러 game 참가 할 수 있다
        return;
      }
    }

    // $_currentScheduleId - null일 땐 admin 처리일 것이다.
    $userPlateCardInst = UserPlateCard::whereId($_userPlateCardId)->first();
    $result = $userPlateCardInst->toArray();
    $lockStatus = $result['lock_status'];
    // $playerId = $result['player_id'];


    $futureStatus = config('lockstatus.exit')[$_checkAccessType][$lockStatus];
    if ($futureStatus === 'babo') {
      __telegramNotify(
        NotifyLevel::CRITICAL,
        'lock_staus Inconsistency',
        [
          'acces_lock_type' => $_checkAccessType,
          'card_lock_type' => $lockStatus,
          'user_plate_card_id' => $_userPlateCardId,
        ]
      );
      logger('accessType과 현재 user_plate_cards의 lock_status 불일치.');
      throw new Exception('accessType과 현재 user_plate_cards의 lock_status 달라.');
    }
    // switch ($_checkAccessType) {
    //   case GradeCardLockStatus::MARKET:
    //     $userPlateCardInst->lock_status = null;
    //     break;
    //   case GradeCardLockStatus::INGAME:
    //   case GradeCardLockStatus::SIMULATION:
    //     logger($userPlateCardInst->id . 'lock_status change');
    //     $userPlateCardInst->lock_status = null;
    //     break;
    //   default:
    //     # code...
    //     break;
    // }
    $userPlateCardInst->lock_status = $futureStatus;
    $userPlateCardInst->save();
  }
}

if (!function_exists('__getCodeInfo')) {
  // withKey 는 map 에 쓸 때 편하도록
  function __getCodeInfo($category, $withKey = true)
  {
    $data = Code::query()
      ->select(['code', 'name'])
      ->where('category', $category)
      ->whereNotNull('code')
      ->orderBy('order_no')
      ->get();

    if ($withKey) {
      return $data->keyBy('code')->toArray();
    }

    return $data->toArray();
  }

  function __pregReplacement($pattern, array $replacements, $subject, $_limit = null)
  {
    return preg_replace_callback($pattern, function ($matches) use ($replacements, $_limit) {
      if (count($matches) > 1) {
        $result = $replacements[$matches[1]];

        if (!is_null($_limit) && strlen($result) > $_limit) {
          // 한글은 3바이트
          $result = mb_strcut($result, 0, $_limit, 'UTF-8') . '...';
        }

        return $result;
      }
    }, $subject);
  }
}
