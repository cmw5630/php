<?php

declare(strict_types=1);

namespace App\Console\Commands\BetRadarParsers;

use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\Opta\Player\PlayerDailyPosition;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\System\NotifyLevel;
use App\Exceptions\Custom\Parser\OTPInsertException;
use App\Exceptions\Custom\Parser\OTPDataMissingException;
use App\Exceptions\Custom\Parser\OTPRequestException;
use App\Libraries\Classes\FantasyCalculator;
use App\Libraries\Classes\SendAction;
use App\Models\game\Player;
use Arr;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use LogEx;

abstract class BaseBetRadarParser
{
  protected string $feedType;
  protected string $feedNick;
  protected array $config;
  protected bool $dbErrorThrowFlag = false;

  public function __construct()
  {
    $this->config = config('betradar');
  }

  protected function makeApiPathExtras(array $_values = []): array
  // TypeA(matchid 등의 id가 outletkey/id?쿼리문자... 형태의 주소)
  // outletkey 이후 주소(쿼리문자열 포함)
  {
    $replacement = [];
    $replacement['endPoint'] = $this->config['endPoints'][$this->feedNick];

    if (count($_values) > 0) {
      return array_map(function ($id) use ($replacement) {
        $replacement['data'] = [
          'id' => $id
        ];

        return __parseApiURI($replacement);
      }, $_values);
    }

    return [__parseApiURI($replacement)];
  }

  protected function optaRequestUrl(array $paths, $_saltParams): array
  {
    return array_map(function ($path) use ($_saltParams) {
      return $this->config['basic']['host'] . $path . $_saltParams;
    }, $paths);
  }

  protected function betRadarRequest($ids = [], $_saltParams = '')
  {
    // generate path
    $paths = $this->makeApiPathExtras($ids);
    // generate url
    $urls = $this->optaRequestUrl($paths, $_saltParams);
    foreach ($urls as $url) {
      __loggerEx($this->feedType, $url . '&' . http_build_query($this->config['basic']['params']));
    }

    // get data
    $sendAction = SendAction::getInstance();

    $result =  $sendAction->send('GET', $urls, [], $this->config['basic']['params'], $paths);
    foreach ($result as $urlKey => $value) {
      if (isset($value['errorCode'])) {
        // LogEx::error('http-client-error', $this->feedNick . ' ' . $urlKey . '&' . http_build_query($this->config['basic']['params']) . $_saltParams . ' ' . json_encode($value));
        report(new OTPRequestException(
          null,
          [
            'feed_nick' => $this->feedNick,
            'error_code' => $value['errorCode'],
            'path' => $urlKey . '&' . http_build_query($this->config['basic']['params']) . $_saltParams,
          ]
        ));
        unset($result[$urlKey]);
      }
    }
    return $result;
    // return $sendAction->send('GET', $urls, [], $this->config['basic']['params'], $paths);
  }

  // ------------------->> test --ing

  protected $columns;
  protected $commonAttrs = [];
  protected $targetSpecifiedAttrs = []; // ex) player, goals, ..
  protected $glueChildKeys = [
    'id',
    'name', // ...
    'ht',
    'ft',
    'total',
    'home',
    'away', //scores
    'officialName',
    'code',
    'position', // contestant
    'longName',
    'neutral', // venue
    'shortName', //contestant, venue,
    'formatId',
    'vertical', // stage
    'startDate',
    'endDate', //stage, tournamentCalendar
    'StartDate',
    'EndDate', //stage, tournamentCalendar
    'fact', // matchfacts
    'time', // MA6 commentary
  ];

  protected $keyNameTransMap = [ // key 조합 예외 처리 map
    'touches' => 'touches_opta',
    'matchInfoId' => 'matchId',
    'matchInfoTime' => 'time',
    'Id' => 'id',
    'Name' => 'name',
  ];

  // snake case로
  protected $nullToZeroAttrKeys = [
    'score',
    'goalkeeping',
    'passing',
    'attacking',
    'shooting',
    'possession',
    'defending', // MA20
    'current_power_rating',
    'highest_season_rating',
    'lowest_season_rating',
    'average_season_rating',
    'current_competition_rank',
    'current_confederation_rank',
    'current_global_rank', // MA1_detailed
    'scores_ht_home',
    'scores_ht_away',
    'scores_ft_home',
    'scores_ft_away',
    'scores_total_home',
    'scores_total_away', // MA1
    'home_score',
    'away_score', // MA2 ??
  ];

  protected $keysIgnored = []; // 필요없는 [key => array] 의 key 집합

  protected $commonKeyGroupsToCustom = []; // custom이 필요한 common keys 설정

  // protected $keysSpecified = []; // goal, player와 같이 N개의 row를 가질 수 있는 배열에 대한 key

  protected $keyGroupsToCustom = []; // 커스텀할 json 구조에 대한 key

  protected function setColumns($_columns)
  {
    $this->columns = $_columns;
  }

  protected function getColumns()
  {
    return $this->columns;
  }

  protected function setCommonAttrs($_parentKey, $_key, $value)
  {
    $this->commonAttrs[$this->correctKeyName($_parentKey, $_key)] = $value;
  }

  protected function getCommonAttrs(): array
  {
    return $this->commonAttrs;
  }

  protected function setTargetSpecifiedAttrs($_specified_key, $_temp_target_array)
  {
    $this->targetSpecifiedAttrs[$_specified_key][] = $_temp_target_array;
  }

  protected function getTargetSpecifiedAttrs(): array
  {
    return $this->targetSpecifiedAttrs;
  }

  protected function setKeyNameTransMap($_map)
  {
    $this->keyNameTransMap = $_map;
  }

  protected function getKeyNameTransMap()
  {
    return $this->keyNameTransMap;
  }

  protected function setKeysToIgnore(array $_keysIgnored)
  {
    $this->keysIgnored = $_keysIgnored;
  }

  protected function getKeysIgnored(): array
  {
    return $this->keysIgnored;
  }


  ///--------------------KG---------------------------->
  protected function setKGsToCustom(array $_keyGroupsToCustom)
  {
    $this->keyGroupsToCustom = $_keyGroupsToCustom;
  }

  protected function getKGsToCustom(): array
  {
    return $this->keyGroupsToCustom;
  }

  protected function setGlueChildKeys(array $_glueChildKeys)
  {
    $this->glueChildKeys = $_glueChildKeys;
  }

  protected function getGlueChildKeys(): array
  {
    return $this->glueChildKeys;
  }

  protected function setCommonKGsToCustom(array $_commonKeyGroupsToCustom)
  {
    // custom이 필요한 common keys 설정
    $this->commonKeyGroupsToCustom = $_commonKeyGroupsToCustom;
  }

  protected function getCommonKGsCustomed(): array
  {
    return $this->commonKeyGroupsToCustom;
  }

  protected function isKGOnCustomedList($_parentKey, $_key)
  {
    // KG == KeyGroup Ex) $_parentKey . '/' . $_key
    //info((string) $_parentKey . '/' . (string) $_key, $this->getKGsToCustom());

    return in_array((string) $_parentKey . '/' . (string) $_key, $this->getKGsToCustom());
  }

  protected function isCommonKGOnCustomedList($_parentKey, $_key)
  {
    // KG == KeyGroup Ex) $_parentKey . '/' . $_key
    return in_array((string) $_parentKey . '/' . (string) $_key, $this->getCommonKGsCustomed());
  }

  ///<--------------------KG----------------------------

  protected function clearCommonCache()
  {
    $this->commonAttrs = [];
  }

  protected function clearSpecifiedCache()
  {
    $this->targetSpecifiedAttrs = [];
  }

  protected function clearAllAttrsCache()
  {
    $this->clearCommonCache();
    $this->clearSpecifiedCache();
  }

  protected function appendTargetSpecifiedAttrsByIndex($_main_key, $_idx, $_temp_target_array)
  {
    // specified 정보 저장: $arr[$mainKey][$_idx] == array 구조,
    // $_idx에 따라 $_temp_target_array의 요소들이 append 됨.
    if (!isset($this->targetSpecifiedAttrs[$_main_key])) {
      $this->targetSpecifiedAttrs[$_main_key] = [];
      $this->targetSpecifiedAttrs[$_main_key][$_idx] = [];
    } else if (!isset($this->targetSpecifiedAttrs[$_main_key][$_idx])) {
      $this->targetSpecifiedAttrs[$_main_key][$_idx] = [];
    }
    // array_push($this->targetSpecifiedAttrs[$_main_key][$_idx], $_temp_target_array);
    $this->targetSpecifiedAttrs[$_main_key][$_idx] =
      array_merge(
        $this->targetSpecifiedAttrs[$_main_key][$_idx],
        $_temp_target_array
      );
    // $this->targetSpecifiedAttrs[$_main_key][$_idx] + $_temp_target_array;
  }

  protected function correctKeyName($_parentKey, $_attrKey): string
  {
    if (in_array($_attrKey, $this->glueChildKeys)) {
      $keyChanged = $_parentKey . Str::ucfirst($_attrKey);

      // return $this->keyNameTransMap[$keyChanged] ?? $keyChanged;
      return $this->getKeyNameTransMap()[$keyChanged] ?? $keyChanged;
    } else {
      return $this->getKeyNameTransMap()[$_attrKey] ?? $_attrKey;
    }
  }

  protected function correctColumnsAndDatetimeType($_row)
  {
    // 현재 설정된 table에 의존적
    // 1. 결과 데이터 db table column에 맞춤.
    // 2. datetime 속성 정제
    // 3. 기타 custom 추가
    //->>temp code

    if (isset($_row['date']) && isset($_row['time'])) {
      if (empty($_row['time'])) {
        $_row['time'] = '00:00';
        $_row['undecided'] = true;
      }
      $_row['started_at'] = Carbon::parse($_row['date'] . ' ' . $_row['time'])->toDatetimeString();
    }
    if (isset($_row['local_date']) && isset($_row['local_time'])) {
      $_row['local_started_at'] = Carbon::parse($_row['local_date'] . ' ' . $_row['local_time'])->toDatetimeString();
    }

    foreach ($_row as $key => $value) {
      if (!in_array($key, $this->getColumns())) {
        unset($_row[$key]);
        continue;
      } else if ($value === 0 || $value === "0") { // "0" 주의 false로 처리됨.
        $_row[$key] = 0;
      } else if (gettype($value) === 'string' && (empty(trim($value)) || $value === '""')) {
        if (in_array($key, $this->nullToZeroAttrKeys)) {
          $_row[$key] = 0;
        } else {
          $_row[$key] = null; // \"\" -> null 처리
        }
      }

      if ($this->isDateTimeType($key)) {
        if (!empty($value)) {
          if (Str::length($value) > 11) {
            // datetime 형식
            $_row[$key] = Carbon::parse($value, 'UTC')->toDateTimeString();
          } else {
            // date 형식
            $_row[$key] = Carbon::parse($value, 'UTC')->toDateString();
          }
          if ($key === 'time') {
            $_row[$key] = Str::after($_row[$key], ' ');
          }
        }
      }
    }

    return $_row;
  }

  protected $currentContestantMap;

  protected function getCurrentContestantMap()
  {
    return $this->currentContestantMap;
  }

  protected function setCurrentContestantMap($_contestantMap)
  {
    $this->currentContestantMap = $_contestantMap;
  }

  abstract protected function customParser($_parentKey, $_key, $_value);

  abstract protected function customCommonParser($_parentKey, $_key, $_value);

  // abstract protected function makeUrlExtras(array $_ids = [], array $_queries = []): string;
  // abstract protected function makeUrlExtras(): string;

  public function start(bool $_act = false): bool // $_act === false면 1번의 request 샘플에 대한 수집 가공된 형식이 로그에 찍힘.
  {
    __loggerEx($this->feedType, $this->feedNick . ' START->');
    return $this->parse($_act);
    __loggerEx($this->feedType, $this->feedNick . ' <-END');
  }

  abstract protected function parse(bool $_act);

  protected function typeValueToKeyValue($_type_value_collection_array): array
  {
    $temp_array = [];
    foreach ($_type_value_collection_array as $idx => $collection) {
      if (isset($collection['type']) and isset($collection['value'])) {
        $temp_array[$this->normalizeColumnName($collection['type'])] = $collection['value'];
      }
    }

    return $temp_array;
  }

  protected function keyTransAfterCustom($_key): void
  {
    $targetList = [];
    switch ($_key) {
      case 'lineUp':
        $targetList = ['player'];
        break;
      case 'stat':
        $targetList = ['teamStat'];
        break;
      case 'player':
        $targetList = ['playerStat'];
        break;
      default:
    }

    if (empty($targetList)) {
      return;
    }

    foreach ($this->getTargetSpecifiedAttrs() as $target => $values) {
      if (!in_array($target, $targetList)) continue;
      foreach ($values as $idx => $items) {
        foreach ($items as $key => $item) {
          $this->targetSpecifiedAttrs[$target][$idx][$this->correctKeyName(
            null,
            $key
          )] = $item;
        }
      }
    }
  }

  protected function attrExtractor(
    $_response,
    $_parentKey = null,
    $_isCommonAttr = true,
    array $_metaInfos = null
  ) {
    // recursive 호출이 이루어지므로 여기에 $this->clearAllCache() 절대 호출 하지 말 것!
    foreach ($_response as $key => $value) { //[$key1 => $value1, $key2 => $value2, ...] 구조

      if (in_array((string) $key, $this->getKeysIgnored())) { //forbidden keys
        continue;
      }

      if (is_array($value)) {
        // if(in_array((string)$_parentKey . '/' . (string)$key, $this->getKeysCustomed())){
        if ($this->isKGOnCustomedList($_parentKey, $key)) {
          $this->customParser($_parentKey, $key, $value);
          $this->keyTransAfterCustom($key);
        } else {
          if (gettype($key) === 'string') { // associated array
            //logger("common key -". $key);
            if ($this->isCommonKGOnCustomedList($_parentKey, $key)) {
              //logger($_parentKey);
              //logger($key);
              $this->customCommonParser($_parentKey, $key, $value);
            } else {
              //logger('진행 :' . $_parentKey . ' -> ' . $key);
              $this->attrExtractor($value, $key, $_isCommonAttr, $_metaInfos);
            }
          } else if (gettype($key) === 'integer') { // index 배열 보통 1->N(2) 구조(multi row 생성, 보통 팀별 정보)
            $_isCommonAttr = false;
            $targetArray = [];
            foreach ($value as $dataKey => $dataValue) { // $value(보통 각 팀별 attrs collection)
              //logger("specified key -". $dataKey);
              if (is_array($dataValue)) {
                // specfied - array 구조
                // if (in_array((string)$dataKey, $this->getKeysIgnored())) { //forbiden keys
                //   continue;
                // } else { // parent_key => [0=>arr, 1=>arr, ...] 구조 중 secified나 ignore 되지 않은 기타 parent key에 대한 arr 처리;
                //logger('-----:' . $key_in_collection);
                $_metaInfos = [
                  'main_key' => $_metaInfos['main_key'] ?? $_parentKey, // depth가 깊어져도 main_key 유지
                  // 'main_key'=> $_parentKey,
                  // 'idx'=>$_metaInfos['idx'] ?? $key, // depth가 깊어져도 index 유지 // 주의 index array 내부에 또 index array가 있는 구조는 customSpecifiedParser를 구현하여 해결
                  'idx' => $key, // index 배열 내에 index 배열에 대해서는 customSpecifiedParser를 구현하여 해결할 것.
                ];
                $this->attrExtractor(
                  $dataValue,
                  $dataKey,
                  $_isCommonAttr,
                  $_metaInfos,
                );
                // }
              } else {  // depth가 없는 attr value(단일 scalar 값)
                $targetArray[$this->correctkeyName($_parentKey, $dataKey)] = $dataValue;
              }
            }
            //logger('save');
            $this->appendTargetSpecifiedAttrsByIndex(
              // $_parentKey,  // main_key
              // $_metaInfos['idx'],
              $_metaInfos['main_key'] ?? $_parentKey, // depth가 깊어져도 main_key 유지
              // 'main_key'=> $_parentKey,
              $_metaInfos['idx'] ?? $key, // depth가 깊어져도 index 유지 // 주의 index array 내부에 또 index array가 있는 구조는 customSpecifiedParser를 구현하여 해결
              $targetArray,
            ); // ex) lineUp->0->contestantId 속성을 저장
          }
        }
      } else { //scalar
        if ($_isCommonAttr) {
          //logger('is_common_attr' . $_isCommonAttr . ':' . $_parentKey);
          $this->setCommonAttrs($_parentKey, $key, $value);
        } else if (isset($_metaInfos)) { // 팀별 기타 정보 (ex) lineUp->0->teamOfficial, lineUp->1->teamOfficial, contestant->0->contry
          $this->appendTargetSpecifiedAttrsByIndex(
            $_metaInfos['main_key'],
            $_metaInfos['idx'],
            [
              $this->correctkeyName($_parentKey, $key) => $value
            ]
          );
        }
      }
    }
    /// 참여 속성 모두 초기화 필요
  }

  // <<------------------

  // protected function getFeedType(array &$_responses)
  // {
  //   $feed = $_responses['feed'];
  //   unset($_responses['feed']);
  //   return $feed;
  // }

  protected $current_url_extra;

  protected function getCurrentUrlExtra()
  {
    return $this->current_url_extra;
  }

  protected function setCurrentUrlExtra($_current_ue): void
  {
    $this->current_url_extra = $_current_ue;
  }

  protected function generateColumnNames(): void
  {
    $commonAttrs = $this->getCommonAttrs();
    $specifiedAttrs = $this->getTargetSpecifiedAttrs();
    $storeColumn = [];
    foreach ($specifiedAttrs as $mainKey => $values) {
      $columns = [];
      foreach ($values as $v) {
        $columns = array_unique(array_merge($columns, array_keys($v)));
      }
      $storeColumn[$mainKey] = array_merge(array_keys($commonAttrs), $columns);
    }
  }

  protected function normalizeColumnName($_name): string
  // opta 속성 key를 column으로 바로 사용할 수 없는 경우 (-, &, 공백 등의 문자가 들어간 경우)
  {
    $colName = Str::before($_name, '(');
    $colName = str_replace(['&', '/', '-', ' ', '%'], ['And', '', '_', '', 'Per'], trim($colName));
    $colName = strtolower(substr($colName, 0, 1)) . substr($colName, 1);

    return $colName;
  }

  protected array $ubiquitousKeyMap = [
    'competition' => 'league',
    'tournament_calendar' => 'season',
    // 'tournament' => 'season',
    'contestant' => 'team',
    'week' => 'round',
    'match' => 'schedule'
  ];

  protected array $ubiquitousIgnoreKeyMap = [
    'match_name',
    'match_length_min',
    'match_length_sec',
  ];

  protected function ubTransKeysName($_key_name): string
  {
    $result = Str::snake(Str::before($_key_name, '('));
    foreach ($this->ubiquitousKeyMap as $x_key => $value) {
      if (Str::contains($result, $x_key) && !in_array($result, $this->ubiquitousIgnoreKeyMap)) {
        return Str::replace($x_key, $value, $result);
      }
    }

    return $result;

    // if (in_array((string)$_key_name, array_keys($this->ubiquitousKeyMap))) {
    //   dd($_key_name);
    //   $_key_name = $this->ubiquitousKeyMap[$_key_name];
    // }
  }

  protected function preProcessResponse($_urlKey, $_response)
  {
    $this->setCurrentUrlExtra($_urlKey); // error log 기록용

    // error 발생 - 처리 - continue
    $this->clearAllAttrsCache();

    // MA2 player_game_stats contestant name 추가
    $this->setCurrentContestantMap(null); //초기화

    if (isset($_response['matchInfo']['contestant'])) {
      $contestant = $_response['matchInfo']['contestant'];
      $contestantData = [];
      foreach ($contestant as $team) {
        $contestantData[$team['id']] = $team;
      }
      $this->setCurrentContestantMap($contestantData);
    }

    // -> parser error (critical)
    $this->attrExtractor($_response);
    // -<
    $commonRowOrigin = $this->getCommonAttrs();
    $specifiedAttrs = $this->getTargetSpecifiedAttrs();

    // dd($specifiedAttrs);
    // key를 snake 형태로 and ->
    // competition -> league, tournament_calendar -> season, contestant -> team, week -> round, match -> schedule
    $snakeCommonRows = [];
    $snakeSpecifiedAttrs = [];

    foreach ($specifiedAttrs as $key => $arrays) {
      foreach ($arrays as $item_idx => $items) {
        foreach ($items as $item_key => $value) {
          $snakeSpecifiedAttrs[$key][$item_idx][$this->ubTransKeysName($item_key)] = $value;
        }
      }
    }

    $specifiedAttrs = $snakeSpecifiedAttrs;
    foreach ($commonRowOrigin as $key => $value) {
      $snakeCommonRows[$this->ubTransKeysName($key)] = $value;
    }
    $commonRowOrigin = $snakeCommonRows;
    // <- key를 snake 형태로

    return [
      'commonRowOrigin' =>  $commonRowOrigin,
      'specifiedAttrs' => $specifiedAttrs,
    ];
  }

  protected function insertDatas($_commonInfoToStore, $_specifiedInfoToStore, $_datas, $_quietly = true)
  {
    $commonRowOrigin = $_datas['commonRowOrigin'];
    $specifiedAttrs = $_datas['specifiedAttrs'];
    if ($_commonInfoToStore) {
      $commonModel = $_commonInfoToStore['common_table_name'];
      $this->setColumns((new $commonModel)->getTableColumns(true));

      // -> common columns 전처리 error (critical)
      $commonInsertRow = $this->correctColumnsAndDatetimeType($commonRowOrigin);
      // -<
      $conditions = [];

      // -> data insert error (critical)
      try {
        if (!empty($_commonInfoToStore['conditions'])) {
          foreach ($_commonInfoToStore['conditions'] as $c) {
            $conditions[$c] = $commonInsertRow[$c] ?? null;
            unset($commonInsertRow[$c]);
          }
          // softdeleted 처리 된 것도 update 해야함.
          $commonModel::withTrashed()->updateOrCreateEx(
            $conditions,
            $commonInsertRow,
            $_quietly,
            true,
          );
        } else {
          $commonModel::insert($commonInsertRow);
        }
      } catch (Exception $e) {
        // LogEx::error($this->feedType, 'Error Info(Critical) - ' . '위치 : ' . 'common insert' . ' / ' . 'feed : ' . $this->feedType . ' / ' . 'url_extra : ' . $this->current_url_extra . 'error message : ' . $e->getMessage());
        if ($this->dbErrorThrowFlag) {
          __telegramNotify(NotifyLevel::CRITICAL, 'db insert error', $this->feedNick);
          throw (new OTPInsertException(null, ['feed_nick' => $this->feedNick, 'path' => $this->current_url_extra,], $e, $commonInsertRow));
        } else {
          report(new OTPInsertException(null, ['feed_nick' => $this->feedNick, 'path' => $this->current_url_extra,], $e, $commonInsertRow));
        }
      }
      // <-
    }

    if ($_specifiedInfoToStore) {
      foreach ($_specifiedInfoToStore as $specifiedValue) {
        $specifiedKey = array_keys($specifiedValue['specifiedInfoMap'])[0];
        $model = array_values($specifiedValue['specifiedInfoMap'])[0];
        // 각 table 에서의 id 값을 매핑시키려면 conditions 에서의 key 값을 가져와야 함.
        $this->setColumns(array_unique(array_merge((new $model)->getTableColumns(true),
          $specifiedValue['conditions']
        )));

        if (!isset($specifiedAttrs[$specifiedKey])) {
          continue;
        }

        foreach ($specifiedAttrs[$specifiedKey] as $value) {
          $storeDataSet = array_merge($commonRowOrigin, $value);
          // -> specified columns 전처리 error (critical)
          $storeDataSet = $this->correctColumnsAndDatetimeType($storeDataSet);
          // if (!isset($storeDataSet['started_at'])) {
          //   continue;
          // }
          // $storeDataSet = $this->keynameMapping($storeDataSet);

          //logger($storeDataSet);
          // <-
          $conditions = [];

          // -> data insert error (critical)
          try {
            if (!empty($specifiedValue['conditions'])) {
              foreach ($specifiedValue['conditions'] as $key => $c) {
                $dataKey = is_numeric($key) ? $c : $key;
                $conditions[$c] = $storeDataSet[$dataKey] ?? null;
                unset($storeDataSet[$dataKey]);
              }

              // softdeleted 처리 된 것도 update 해야함.
              // insert 가 아닌 update 되기 위해 globalScope 제거
              $model::withTrashed()->withoutGlobalScopes()->updateOrCreateEx(
                $conditions,
                $storeDataSet,
                $_quietly,
                true,
              );
            } else {
              $model::insert(
                $storeDataSet
              );
            }
          } catch (Exception $e) {
            // LogEx::error($this->feedType, 'Error Info(Critical) - ' . 'table:' . $model::getTableName() . ' / ' . '위치:' . 'specified insert' . ' / ' . 'feed:' . $this->feedType . ' / ' . 'url_extra:' . $this->current_url_extra . ' / ' . 'error message:' . $e->getMessage());
            if ($this->dbErrorThrowFlag) {
              __telegramNotify(NotifyLevel::CRITICAL, 'db insert error', $this->feedNick);
              throw (new OTPInsertException(null, ['feed_nick' => $this->feedNick, 'path' => $this->current_url_extra,], $e, $storeDataSet));
            } else {
              report(new OTPInsertException(null, ['feed_nick' => $this->feedNick, 'path' => $this->current_url_extra,], $e, $storeDataSet));
            }
          }
          // <-
        }
      }
    }
  }


  protected function insertOptaDatasToTables(
    array $_responses,
    array $_commonInfoToStore = null,  //ex) ['common_table_name' => 'common_table_name', 'conditions' => []],
    array $_specifiedInfoToStore = null, //ex) [ [ 'specifiedInfoMap' => ['penaltyShot' => 'penalty_shots'], 'conditions' => ['matchId', 'timestamp'] ] ],
    $_realStore = false,
  ): void {
    foreach ($_responses as $urlKey => $response) { // 비동기 응답s 처리
      if ($response === null) {
        logger($urlKey . '의 $response is null입니다.');
        continue;
      }
      $datas = $this->preProcessResponse($urlKey, $response);

      // data 체크->
      if (!$_realStore) {
        logger($datas['commonRowOrigin']);
        logger($datas['specifiedAttrs']);
        $this->generateColumnNames();
        dd('-xTestx-');
      }
      // data 체크<-

      $this->insertDatas($_commonInfoToStore, $_specifiedInfoToStore, $datas);
    }
  }

  protected function isDateTimeType($key): bool
  {
    $time_kind_keys = [
      'timestamp',
      'last_updated',
      'start',
      'end',
      'start_date',
      'end_date',
      'Start_date',
      'End_date',
      'date',
      'time',
      'last_modified', // MA6 commentary
      'deletion_time', // deletion
      'date_of_birth', // PE3 referee
      'announced_date', // PE3 referee
    ];
    foreach ($time_kind_keys as $value) {
      if (Str::endsWith($key, $value)) {
        return true;
      }
    }

    return false;
    // if (in_array((string)$key, $time_kind_keys)) return true;
    // return false;
  }

  protected function getColumnNames($_connection, $_table_name)
  {
    return Schema::connection($_connection)->getColumnListing($_table_name);
    // return DB::getSchemaBuilder()->getColumnListing($_table_name);
  }


  protected function uniqueValueListFromColumn(string $_model, array $_columns): array
  {
    // 컬럼 2개 이상 필요한 케이스가 생길 경우 해당 상황에 맞게 확장예정
    $values = $_model::select($_columns)->get()->toArray();

    $valuesFiltered = $values;

    if (count($_columns) === 1) {
      $valuesFiltered = array_unique(array_column($values, Arr::first($_columns)));
    }

    return $valuesFiltered;
  }

  // SNIPPETS -->>
  protected function extractRepeatedSpecifiedWithId(
    $_value,
    $_outerKey,
    $_outerCollectionId,
    $_innerKey,
    $_innerCollectionId = null,
    $_lastStatKey = 'xxyyzzabcdpp'
  ) // 
  {
    foreach ($_value as $outer_idx => $outerCollection) {
      $outerTempAttrs = [];
      $innerCollection = $outerCollection[$_innerKey];
      unset($outerCollection[$_innerKey]);
      $playerUniqueKey = $outerCollection[$_outerCollectionId];
      foreach ($outerCollection as $outer_collection_key => $outer_collection_value) {
        $outerTempAttrs[$this->correctKeyName($_outerKey, $outer_collection_key)] = $outer_collection_value;
      }
      //logger($person_temp_attrs);
      foreach ($innerCollection as $innerCollectionIdx => $innerCollectionValue) {
        $innerTempAttrs = $outerTempAttrs;
        $innerTempAttrs['membershipIdx'] = $innerCollectionIdx;
        $itemUniqueKey = null;
        foreach ($innerCollectionValue as $key => $value) {
          $itemUniqueKey = $playerUniqueKey;
          $itemUniqueKey = $innerCollectionIdx . '_' . $itemUniqueKey . '_' .  $innerCollectionValue[$_innerCollectionId];
          if (gettype($key) === 'string' and $key !== $_lastStatKey) { // depth가 더 없다면
            $innerTempAttrs[$this->correctKeyName($_innerKey, $key)] = $value;
          } elseif ($key === $_lastStatKey) {

            foreach ($value as $statKey => $statAttrs) {
              // $inner_temp_attrs = array_merge($inner_temp_attrs, $last_stat_attrs); 
              $innerTempAttrs[$this->correctKeyName($_lastStatKey, $statKey)] = $statAttrs;
            }
          }
        }
        $this->appendTargetSpecifiedAttrsByIndex(
          $_outerKey,
          $itemUniqueKey,
          $innerTempAttrs
        );
        //logger($membership_temp_attrs);
      }
    }
  }

  private function getFantasyCalculateAvailableColumns()
  {
    /**
     * @var FantasyCalculator $fpCalculator
     */

    $fpCalculator = app(FantasyCalculatorType::FANTASY_POINT, [0]);
    /**
     * @var FantasyCalculator $ratingCalculator
     */
    $ratingCalculator = app(FantasyCalculatorType::FANTASY_RATING, [0]);
    // FANTASY_DRAFT 타입 컬럼은 현재 모두 중복됨

    $a = $fpCalculator->getAllColums();
    $b = $ratingCalculator->getAllColums();
    // $c = $draftCalculator->getAllColums();
    return array_values(array_unique(array_merge($a, $b)));
  }


  protected function playerParseOnLineupSnippet($_value)
  // (보통 lineUp 하위 구조) player 뽑는데 사용
  // $_contestants_info : contestant 정보(name을 linup 정보에 추가하기 위함)
  {
    $contestantNameMap = $this->getCurrentContestantMap();

    foreach ($_value as $teamData) {
      if (isset($teamData['player'])) {
        foreach ($teamData['player'] as $player) {
          $playerKey = $teamData['contestantId'] . '_' . $player['playerId'];
          if (!Player::whereId($player['playerId'])->limit(1)->exists()) {
            LogEx::warning($this->feedType, '플레이어 정보를 찾을 수 없음. : ' . $playerKey);
            continue;
          }
          if (isset($teamData['contestantId'])) {
            $player['contestantId'] = $teamData['contestantId'];
          }
          if (isset($teamData['contestantId']) && $contestantNameMap) {
            $player['contestantName'] = $contestantNameMap[$player['contestantId']]['name'];
          }
          if (isset($teamData['formationUsed'])) {
            $player['formationUsed'] = $teamData['formationUsed'];
          }

          if (isset($player['stat'])) {
            foreach ($player['stat'] as $items) {
              if (isset($items['type']) and isset($items['value'])) {
                $player[$items['type']] = $items['value'];
              }
              unset($player['stat']);
            }
          }
          // 번복 가능한 스탯에 대하여 존재하지 않는 다음 스탯에 초기값 0 부여, 의존되는 코드가 많음(LIVE파서 라인업) 리스트 goals, goal_assist 삭제 금지.
          $fantasyColumns = $this->getFantasyCalculateAvailableColumns();
          $etcColumns = ['is_mom', 'redCard', 'yellowCard', 'secondYellow', 'goals', 'goalAssist', 'gameStarted', 'totalSubOn'];
          $targetColumns = array_merge($fantasyColumns, $etcColumns);

          foreach ($targetColumns as $type) {
            if (!isset($player[Str::camel($type)])) $player[$type] = 0;
          }

          if ($player['position'] === PlayerDailyPosition::SUBSTITUTE) {
            if (isset($player['subPosition']) && $player['subPosition'] === PlayerPosition::UNKNOWN) {
              continue;
            }
          }
          $this->appendTargetSpecifiedAttrsByIndex(
            'player',
            $playerKey,
            $player
          );
        }
      }
    }
  }

  protected function playerParseOnTeamSnippet($_main_key, $_value)
  // PE4
  {
    foreach ($_value as $team_idx => $team_collection) {
      $team_id = $team_collection['id'] ?? null;
      $team_name = $team_collection['name'] ?? null;
      if (isset($team_collection['player'])) {
        foreach ($team_collection['player'] as $player_idx => $player_attrs) {
          $player_temp_attrs = [];
          try {
            $player_id = $player_attrs['id'];
          } catch (exception $e) {
            // LogEx::error($this->feedType, 'errorlogger(critical) - ' . 'specified_name:' . $_main_key . ' / ' . '위치:' . 'specified insert' . ' / ' . 'feed:' . $this->feedType . ' / ' . 'url_extra:' . $this->current_url_extra . ' / ' . 'error message:' . $e->getmessage());
            report(new OTPDataMissingException(null, ['feed_nick' => $this->feedNick, 'path' => $this->current_url_extra,], $e));
            continue;
          }
          $player_temp_attrs['contestantId'] = $team_id;
          $player_temp_attrs['contestantName'] = $team_name;
          $player_temp_attrs['playerId'] = $player_id ?? null;
          $player_temp_id = $team_id . '_' . $player_id;
          // isset($team_collection['contestantId']) ? $player_attrs['contestantId'] = $team_collection['contestantId']:null;
          if (isset($player_attrs['stat'])) {
            $player_temp_attrs = array_merge($player_temp_attrs, $this->typeValueToKeyValue($player_attrs['stat']));
          }
          $this->appendTargetSpecifiedAttrsByIndex(
            $_main_key,
            $player_temp_id,
            $player_temp_attrs
          );
        }
      }
    }
  }


  protected function teamStatParserOnLineupSnippet($_value)
  //team_game_stat
  {
    foreach ($_value as $teamKey => $teamData) {
      $team = [];
      $team['contestantId'] = $teamData['contestantId'];
      if (isset($teamData['stat'])) {
        foreach ($teamData['stat'] as $stat) {
          $team[$stat['type']] = $stat['value'];
        }
        $this->appendTargetSpecifiedAttrsByIndex(
          'teamStats',
          $teamKey,
          $team
        );
      }
    }
  }

  protected function teamStatParserOnTeamSnippet($_main_key, $_value)
  //PE4
  {
    foreach ($_value as $team_idx => $team_collection) {
      $team_temp_attrs = [];
      $team_temp_attrs['contestantName'] = $team_collection['name'];
      try {
        $team_temp_attrs['contestantId'] = $team_collection['id'];
      } catch (Exception $e) {
        // LogEx::error($this->feedType, 'Error Info(Critical) - ' . 'specified_name:' . $_main_key . ' / ' . '위치:' . 'specified insert' . ' / ' . 'feed:' . $this->feedType . ' / ' . 'url_extra:' . $this->current_url_extra . ' / ' . 'error message:' . $e->getMessage());
        report(new OTPDataMissingException(null, ['feed_nick' => $this->feedNick, 'path' => $this->current_url_extra,], $e));
        continue;
      }
      if (isset($team_collection['stat'])) {
        $team_temp_attrs = array_merge($team_temp_attrs, $this->typeValueToKeyValue($team_collection['stat']));
        // foreach($team_collection['stat'] as $stat_idx => $stat_values) {
        //   $team_temp_attrs[$stat_values['type']] = $stat_values['value'];
        // }
      }
      $this->appendTargetSpecifiedAttrsByIndex(
        $_main_key,
        $team_idx,
        $team_temp_attrs
      );
    }
  }


  protected function contestantCommonNamingSnippet($_key, $_value): void
  // homeContestant~, awayContestant~ , contestant 정보를 commonAttr로 변경
  {
    $temp = [];
    foreach ($_value as $idx => $attrs) {
      $position = $attrs['position'];
      foreach ($attrs as $k => $v) {
        if ($k === 'country') {
          foreach ($v as $countryKey => $countryValue) {
            $this->setCommonAttrs(
              null,
              $position . Str::ucfirst($_key) . Str::ucfirst($k) . Str::ucfirst($countryKey),
              $countryValue
            );
            // $temp[$position . Str::ucfirst($_key) . Str::ucfirst($k) . Str::ucfirst($countryKey)]=$countryValue;
          }
        } else {
          $this->setCommonAttrs(
            null,
            $position . Str::ucfirst($_key) . Str::ucfirst($k),
            $v
          );
          // $temp[$position . Str::ucfirst($_key) . Str::ucfirst($k)]=$v;
        }
      }
    }
  }
}
