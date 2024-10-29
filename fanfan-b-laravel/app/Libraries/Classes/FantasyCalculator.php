<?php

namespace App\Libraries\Classes;

use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\FantasyCalculator\FantasyDraftCategoryType;
use App\Enums\FantasyCalculator\FantasyPointCategoryType;
use App\Enums\FantasyCalculator\FantasyPolicyType;
use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Card\OriginGrade;
use App\Enums\Opta\Card\PlateCardStatus;
use App\Enums\Opta\Card\PowerGrade;
use App\Enums\Opta\Player\PlayerDailyPosition;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Schedule\ScheduleWinnerStatus;
use App\Enums\Opta\Season\SeasonNameType;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\SimulationCalculator\SimulationCategoryType;
use App\Enums\StatCategory;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\game\DraftComplete;
use App\Models\game\FreeFieldLevelGradePool;
use App\Models\game\FreeGameLineup;
use App\Models\game\FreeGameLineupMemory;
use App\Models\game\FreeKeeperLevelGradePool;
use App\Models\game\FreePlayerPool;
use App\Models\game\PlateCard;
use App\Models\game\RefPlayerBaseProjection;
use App\Models\log\PlateCardPriceChangeLog;
use App\Models\meta\RefBurnCard;
use App\Models\meta\RefDraftPrice;
use App\Models\meta\RefPlayerOverall;
use App\Models\meta\RefPlayerOverallHistory;
use App\Models\meta\RefPointcQuantile;
use App\Models\meta\RefTeamDefaultProjection;
use App\Models\meta\RefTeamProjectionWeight;
use App\Models\simulation\SimulationOverall;
use App\Models\TempUserFPAdd;
use App\Models\user\UserPlateCard;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DivisionByZeroError;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Schema;

class FantasyCalculator
{
  private const  FP_RESULT_PRECISION = 1; // 반올림
  private const  FIP_RESULT_PRECISION = 1; // 반올림
  private const  RATING_RESULT_PRECISION = 1; // 버림
  private const  POWERRANKING_RESULT_PRECISION = 2; // 반올림
  private $fantasyCalculatorType;
  private $version; // 각 계산타입의 참조 테이블 version
  protected $FantasyCalculatorRefTableMap; // 계산 참조 테이블 MAP

  protected $positionMap = [
    'position' => [
      PlayerDailyPosition::STRIKER => PlayerPosition::ATTACKER,
      PlayerDailyPosition::ATTACKING_MIDFIELDER => PlayerPosition::MIDFIELDER,
      PlayerDailyPosition::MIDFIELDER => PlayerPosition::MIDFIELDER,
      PlayerDailyPosition::DEFENSIVE_MIDFIELDER => PlayerPosition::MIDFIELDER,
      PlayerDailyPosition::DEFENDER => PlayerPosition::DEFENDER,
      PlayerDailyPosition::WING_BACK => PlayerPosition::DEFENDER,
      PlayerPosition::GOALKEEPER => PlayerPosition::GOALKEEPER,
    ],
    'subPosition' => [
      PlayerPosition::ATTACKER => PlayerPosition::ATTACKER,
      PlayerPosition::MIDFIELDER => PlayerPosition::MIDFIELDER,
      PlayerPosition::DEFENDER => PlayerPosition::DEFENDER,
      PlayerPosition::GOALKEEPER => PlayerPosition::GOALKEEPER,
    ]
  ];


  public function __construct(string $_fantasyCalculatorType, int $_version)
  {
    $this->fantasyCalculatorType = $_fantasyCalculatorType;
    $this->version = $_version;

    $this->FantasyCalculatorRefTableMap[$_fantasyCalculatorType] = [
      'config' => Str::lower($_fantasyCalculatorType),
      'table' => sprintf('%s_REFERENCE_TABLE', Str::upper($_fantasyCalculatorType)),
    ];
  }

  public function getFpPrecision()
  {
    return self::FP_RESULT_PRECISION;
  }

  public function getRatingPrecision()
  {
    return self::RATING_RESULT_PRECISION;
  }

  public function checkConfig(): void
  {
    if (!in_array($this->fantasyCalculatorType, FantasyCalculatorType::getValues())) {
      logger('사용전 setCalculator 메소드 호출 하여 fantasyCalculatorType 설정 필요');
    } else if (gettype($this->version) !== 'integer') {
      logger('사용전 setCalculator 메소드 호출 하여 version 설정 필요');
    }
  }

  public function getFantasyTableConfigPath(): string
  {
    return sprintf(
      '%s.%s_V%s',
      $this->FantasyCalculatorRefTableMap[$this->fantasyCalculatorType]['config'],
      $this->FantasyCalculatorRefTableMap[$this->fantasyCalculatorType]['table'],
      $this->version,
    );
  }

  public function getTargetCombTable(array $_playerStats): array
  {
    $targetTablePosition = $this->getDailyPosition($_playerStats);

    return config(
      // ex) fantasypoint.FANTASY_POINT_REFERENCE_TABLE_V0.Attacker
      sprintf(
        '%s.CombTable.%s.%s',
        $this->getFantasyTableConfigPath(),
        $targetTablePosition['posType'],
        $targetTablePosition['posValue'],
      )
    );
  }

  public function getDraftCategoryMetaTable($_position = null): array
  {
    $table = config(
      sprintf(
        '%s.Categories',
        $this->getFantasyTableConfigPath(),
      )
    );
    if ($_position !== null) {
      if ($_position === PlayerPosition::GOALKEEPER) {
        unset($table[FantasyDraftCategoryType::ATTACKING]);
      } else {
        unset($table[FantasyDraftCategoryType::GOALKEEPING]);
      }
    }
    return $table;
  }


  public function getCombsWithCategoryTable(): array
  {
    // $targetTablePosition = $this->getDailyPosition($_playerStats);

    $table = config(
      // ex) fantasypoint.FANTASY_POINT_REFERENCE_TABLE_V0.Attacker
      sprintf(
        '%s.Categories',
        $this->getFantasyTableConfigPath(),
      )
    );
    if ($this->fantasyCalculatorType === FantasyCalculatorType::FANTASY_DRAFT) {
      foreach ($table as $cate => $values) {
        foreach ($values as $name => $elements) {
          $table[$cate][$name] = $table[$cate][$name]['column'];
        }
      }
    }
    return $table;
  }

  public function getCombRepresentationNames(): array
  {
    $pointCategories = config(
      sprintf(
        '%s.Categories',
        $this->getFantasyTableConfigPath(),
      )
    );
    $totalStatKeys = [];
    foreach ($pointCategories as $category => $valuesInCate) {
      foreach ($valuesInCate as $kk => $vv) {
        if (is_numeric($kk)) {
          $totalStatKeys[] = $vv;
        } else {
          $totalStatKeys[] = $kk;
        }
      }
    }
    return $totalStatKeys;
  }

  public function getOrderNumberRefTable()
  {
    return config(
      sprintf(
        '%s.OrderRef',
        $this->getFantasyTableConfigPath(),
      )
    );
  }

  public function getConfig()
  {
    return config(
      sprintf(
        '%s',
        $this->getFantasyTableConfigPath(),
      )
    );
  }


  public function getStatCategoryByStatKey(string $_statkey): null|string
  {
    foreach ($this->getCombsWithCategoryTable() as $cate => $StatKeyGroup) {
      foreach ($StatKeyGroup as $key => $value) {
        if (
          (is_numeric($key) && $_statkey === $value)
          ||
          (!is_numeric($key) && $_statkey === $key)
        ) {
          return $cate;
        }
      }
    }
    return null;
  }


  public function getDraftPolicy(string $_priceGrade)
  {
    $draftPolicy = config(
      // ex) fantasypoint.FANTASY_POINT_REFERENCE_TABLE_V0.Policy
      sprintf(
        '%s.%s',
        $this->getFantasyTableConfigPath(),
        'DraftPolicy'
      )
    ) ?? [];

    $draftPriceTable = RefDraftPrice::query()->select(['level', sprintf('%s as %s',  $_priceGrade, 'price')])->get()->toArray();
    $draftPolicy['price']['table'] = $draftPriceTable;
    $draftPolicy['price']['price_grade'] = $_priceGrade;
    return $draftPolicy;
  }


  public function DeprecatedmakeDraftPriceTableData(array $_levelOnePrices, array $_rates): array|null
  {
    // fantasydraft 전용
    if ($this->fantasyCalculatorType !== FantasyCalculatorType::FANTASY_DRAFT) return null;

    $seedData[] = $_levelOnePrices;
    foreach ($_rates as $level => $rate) {
      foreach ($seedData as $priceData) {
        if ($priceData['level'] === $level - 1) {
          $nextSet = [
            'level' => $level,
            OriginGrade::SS => $priceData[OriginGrade::SS] + $priceData[OriginGrade::SS] * $rate,
            OriginGrade::S => $priceData[OriginGrade::S] + $priceData[OriginGrade::S] * $rate,
            OriginGrade::A => $priceData[OriginGrade::A] + $priceData[OriginGrade::A] * $rate,
            OriginGrade::B => $priceData[OriginGrade::B] + $priceData[OriginGrade::B] * $rate,
            OriginGrade::C => $priceData[OriginGrade::C] + $priceData[OriginGrade::C] * $rate,
            OriginGrade::D => $priceData[OriginGrade::D] + $priceData[OriginGrade::D] * $rate,
            'rate' => $rate,
          ];

          $seedData[] = $nextSet;
          break;
        }
      }
    }
    return $seedData;
  }

  public function makeDraftPriceTableData(array $_levelOnePrices): array|null
  {
    // fantasydraft 전용
    if ($this->fantasyCalculatorType !== FantasyCalculatorType::FANTASY_DRAFT) return null;

    $seedData = [];
    foreach ($_levelOnePrices as $level => $price) {
      $nextSet = [
        'level' => $level,
        OriginGrade::SS => $price,
        OriginGrade::S => $price,
        OriginGrade::A => $price,
        OriginGrade::B => $price,
        OriginGrade::C => $price,
        OriginGrade::D => $price,
        'rate' => null,
      ];

      $seedData[] = $nextSet;
    }
    return $seedData;
  }


  private function getPolicy(string $_priceGrade = null): array
  {
    $policy = config(
      // ex) fantasypoint.FANTASY_POINT_REFERENCE_TABLE_V0.Policy
      sprintf(
        '%s.%s',
        $this->getFantasyTableConfigPath(),
        'Policy'
      )
    ) ?? [];

    return $policy;
  }


  private function getStatCombPolicy(string $_statCombName): array|null
  {
    return $this->getPolicy()[$_statCombName] ?? null;
  }


  public function getDailyPosition(array $_playerStats): array
  {
    // player_daily_stats 테이블에 들어오는 position
    if ($_playerStats['position'] === PlayerDailyPosition::SUBSTITUTE && $_playerStats['sub_position'] !== null) {
      return [
        'posType' => 'sub_position',
        'posValue' => $_playerStats['sub_position'],
      ];
    }
    return [
      'posType' => 'position',
      'posValue' => $_playerStats['position'],
    ];
  }


  public function getPositionSummary(array $_playerStats): string
  {
    if ($_playerStats['position'] === PlayerDailyPosition::SUBSTITUTE) {
      return $this->positionMap['subPosition'][$_playerStats['sub_position']];
    } else {
      return $this->positionMap['position'][$_playerStats['position']];
    }
  }



  public function getOperationTokens(string $_statCombName): array
  {
    // 단순한 공식일 경우 안전하게 사용.(준비중)
    return [];
  }

  public function getStatOperationTokens2(string $_statCombName): array
  {
    // 복잡한 공식일 경우
    $statCombOperationTokens = [];
    if (Str::contains($_statCombName, ['-', '+', '/', '*'])) {
      for ($i = 0; $i < 100; $i++) { // while(true) ...
        if (preg_match('/(?:\s*)([\(\+\-\*\/\)\s]*)(?:\s*)(\w+)(?:\s*)([\+\-\*\/\)\s]*)(?:\s*)(.*)/', $_statCombName, $matches) !== false) {
          for ($j = 1; $j <= 3; $j++) {
            if ($matches[$j] !== '') $statCombOperationTokens[] = $matches[$j];
          }
          if ($matches[4] === '') break;
          $_statCombName = $matches[4];
        }
      }
    } else {
      $statCombOperationTokens = [$_statCombName];
    }
    return $statCombOperationTokens;
  }


  public function quantileMinValueChecker(array $_statCombPolicy, string $_statCombName, array $_playerStats): bool
  {
    // FantasyPolicyType이 같다고 하더라도 statComb마다 적용 방식이 달라질 수 있으므로 컬럼마다 분기할 필요가 있을 수도 있음.
    if ($_statCombName === 'accurate_pass/total_pass') {
      $playerPos = $this->getDailyPosition($_playerStats);
      $minRefValue = $_statCombPolicy['minValueRef'][$playerPos['posType']][$playerPos['posValue']];
      $minCombValue = $this->statCombinationResolver($_statCombPolicy['minValueCombName'], $_playerStats);
      return $minCombValue['statCombPoint'] < $minRefValue;
    }
    return false; // 충족
  }


  public function getAllColums(): array
  {
    $columns = [];
    foreach ($this->getCombsWithCategoryTable() as $cate => $combSet) {
      foreach ($combSet as $key => $statCombName) {
        // statComb string 표현식을 계산
        $operationTokens = $this->getStatOperationTokens2($statCombName);
        foreach ($operationTokens as $token) {
          if (Str::contains($token, ['-', '+', '/', '*', '(', ')'])) {
            continue;
          } else {
            $columns = array_merge($columns, [$token]);
          }
        }
      }
    }
    return $columns;
  }


  public function statCombinationResolver(string $_statCombName, array $_playerStats): array
  {
    // statComb string 표현식을 계산
    $operationTokens = $this->getStatOperationTokens2($_statCombName);
    $expression = '';
    foreach ($operationTokens as $token) {
      if (Str::contains($token, ['-', '+', '/', '*', '(', ')'])) {
        $expression = $expression . $token;
      } else {
        $expression = $expression . (isset($_playerStats[$token]) ? $_playerStats[$token] : 0);
      }
    }

    $result =  [
      'statCombName' => $_statCombName,
      'statCombPoint' => 0,
    ];

    try {
      eval("\$result['statCombPoint'] = $expression;");
    } catch (DivisionByZeroError $e) {
      // pass
    }
    return $result;
  }

  public function cutPointValueFrom(array $_statCombRefValue, float $_statCombPoint): float|array
  {
    // 코드 복잡도를 줄이기 위해 판타지 Calculator의 Policy에 관련한 참조(Ref) array는 key에 대한 DESC 정렬을 기본으로하여 정렬 알고리즘을 현재 메소드에 커플링!
    $_cutPointRefArrayDesc = Arr::sort($_statCombRefValue, function ($value, $key) {
      return -$key;
    });
    foreach ($_cutPointRefArrayDesc as $cutPoint => $applyPoint) {
      if ($_statCombPoint >= $cutPoint) {
        return $applyPoint;
      }
    }
  }

  public function shiftPoint(float $_data, int $_shift): int|float
  {
    // 코드 유지
    $data = (string)$_data;
    $sign = '';
    if ($_data < 0) {
      $sign = '-';
      $data = substr($_data, 1);
    }
    $beforeAfter = explode('.', $data);
    $before = $beforeAfter[0];
    $after = isset($beforeAfter[1]) ? $beforeAfter[1] : null;

    $beforeLength = isset($beforeAfter[0]) ? Str::length($before) : 0;
    $afterLength = isset($beforeAfter[1]) ? Str::length($after) : 0;
    $newNumber = '';
    if ($_shift > 0) {
      $loopCount = max($afterLength, $_shift);
      $numElements = $after !== null ? str_split($after, 1) : [];
      for ($i = 0; $i < $loopCount; $i++) {
        $appendNumber = isset($numElements[$i]) ? $numElements[$i] : 0;
        if ($i === $_shift) {
          $newNumber .=  '.' . $appendNumber;
          continue;
        }
        $newNumber .=  $appendNumber;
      }
      if ($before !== '0') {
        $newNumber = $before . $newNumber;
      }
      $newNumber = $sign . $newNumber;
      return (float)$newNumber;
    } else if ($_shift === 0) {
      return $_data;
    } else {
      $loopCount = abs($_shift);
      $numElements = $before !== null ? str_split($before, 1) : [];
      $pointPosition = $beforeLength - abs($_shift);
      if ($pointPosition > 0) {
        $newNumber = '';
        $newBefore = array_slice($numElements, 0, $pointPosition);
        $newAfter = array_slice($numElements, $pointPosition, $pointPosition + 1);
        array_push($newBefore, '.');
        $numElements = array_merge($newBefore, $newAfter);
      } else {
        $newNumber = '0.';
        for ($i = 0; $i < abs($pointPosition); $i++) {
          array_unshift($numElements, '0');
        }
      }
      foreach ($numElements as $num) {
        $newNumber .= $num;
      }
      $newNumber = $sign . $newNumber . $after;
      return (float)$newNumber;
    }
  }


  public  function decimalOperate(int|float $_a, int|float $_b, string $_opreator): int|float
  {
    // 사용안함. 코드 유지
    $a = explode('.', (string)$_a);
    $b = explode('.', (string)$_b);
    $aShift = isset($a[1]) ? Str::length($a[1]) : 0;
    $bShift = isset($b[1]) ? Str::length($b[1]) : 0;


    switch ($_opreator) {
      case '+':
      case '-':
      case '/':
        $shift = max($aShift, $bShift);
        $decimalA = $this->shiftPoint($_a, $shift);
        $decimalB = $this->shiftPoint($_b, $shift);
        if ($_opreator === '+') {
          $result =  $this->shiftPoint($decimalA + $decimalB, -$shift);
        } else if ($_opreator === '-') {
          $result =  $this->shiftPoint($decimalA - $decimalB, -$shift);
        } else if ($_opreator === '/') {
          $result = $this->shiftPoint($decimalA / $decimalB, 0);
        }
        break;
      case '*':
        $decimalA = $this->shiftPoint($_a, $aShift);
        $decimalB = $this->shiftPoint($_b, $bShift);
        $result =  $this->shiftPoint($decimalA * $decimalB, - ($aShift + $bShift));
        break;
    }
    return $result;
  }


  private function applyStatCombinationFpPolicy(array $_playerStats, array $_resolvedStatComb, float|array $_statCombRefValue): float
  {
    // 각 statComb 표현식 결과에 정책 적용 또는 가중치 곱을 하는 메소드
    $statCombName = $_resolvedStatComb['statCombName'];
    $statCombPolicy = $this->getStatCombPolicy($statCombName);
    $statCombPoint = $_resolvedStatComb['statCombPoint'];

    // 정책이 적용된 statComb
    if (!is_null($statCombPolicy)) {

      // strategy pattern 적용가능.
      if ($statCombPolicy['type'] === FantasyPolicyType::QUANTILE) {
        $_statCombRefValue = $this->cutPointValueFrom(
          $_statCombRefValue,
          $statCombPoint
        );
        // return $this->cutPointValueFrom(
        //   $_statCombRefValue,
        //   $statCombPoint
        // );
      } else if ($statCombPolicy['type'] === FantasyPolicyType::QUANTILE_MIN_VALUE) {
        if ($this->quantileMinValueChecker($statCombPolicy, $statCombName, $_playerStats)) return 0;
        $_statCombRefValue =  $this->cutPointValueFrom(
          $_statCombRefValue,
          $statCombPoint
        );
      } else if ($statCombPolicy['type'] === FantasyPolicyType::QUANTILE_QUANTILE_CONDITIONS) {
        // policty가 있는 statCombination의 (quantile 참조 테이블 구조) 
        // 예) [statCombPoint => [statCombPoint_a => statCombRefValue_a, statCombPoint_b => statCombRefValue_b, ...]] statCombRefValue 내에서 반복구조.
        // 기본 statComb 
        // logger($_statCombRefValue);
        // logger($statCombPoint);
        $_statCombRefValue = $this->cutPointValueFrom(
          $_statCombRefValue,
          $statCombPoint
        );
        // policy condition resolve
        $conditionCombRefValue = $_statCombRefValue;
        // logger($conditionCombRefValue);
        foreach ($statCombPolicy['conditionCombNames'] as $conditionCombName) {
          // condition RefValues...
          $conditionCombValueMap = $this->statCombinationResolver($conditionCombName, $_playerStats);
          $conditionCombPoint = $conditionCombValueMap['statCombPoint'];
          // logger($conditionCombPoint);
          $conditionCombRefValue = $this->cutPointValueFrom(
            $conditionCombRefValue,
            $conditionCombPoint
          );
          // logger($conditionCombRefValue);
          if (is_array($conditionCombRefValue)) {
            continue;
          } else { // $_statCombRefValue is float // ValueRef 구조에 따라 모든 condition을 다 검사하지 않을 수 있음.
            // logger('---------------------------');
            $_statCombRefValue = $conditionCombRefValue;
            break;
            // return $conditionCombRefValue * $conditionCombPoint;
          }
        }
      }
      if ($statCombPolicy['weight']) {
        return $statCombPoint * $_statCombRefValue;
      } else {
        return $_statCombRefValue;
      }
    }

    // 정책없는 statComb
    return $statCombPoint * $_statCombRefValue;
  }

  public function makePointSetWithRefName(array $_playerStats, $_noCate = false, $_withOrigin = false, $_withOrder = false, $_withTotalPoint = false): array
  {
    $this->checkConfig();

    $CombCategoryTable = $this->getCombsWithCategoryTable();

    $statCombTable = $this->getTargetCombTable($_playerStats);

    $orderNumberRefTable = $this->getOrderNumberRefTable();

    $pointSet = [];
    $totalPoint = 0;
    foreach ($CombCategoryTable as $category => $colNameToCombs) {
      foreach ($colNameToCombs as $key => $statCombName) {
        $originStat = $this->statCombinationResolver($statCombName, $_playerStats);
        $statPoint = $this->applyStatCombinationFpPolicy(
          $_playerStats,
          $originStat,
          $statCombTable[$statCombName],
        );
        $totalPoint += $statPoint;
        $statPoint = __setDecimal($statPoint, self::FP_RESULT_PRECISION);
        if ($statPoint == 0) continue;

        if ($_noCate) {
          $store = &$pointSet;
        } else {
          $store = &$pointSet[$category];
        }
        if (getType($key) === 'integer') {
          if ($this->fantasyCalculatorType === FantasyCalculatorType::FANTASY_POINT) {
            $store[$statCombName]['fantasy'] = $statPoint;
          } else if ($this->fantasyCalculatorType === FantasyCalculatorType::FANTASY_RATING) {
            $store[$statCombName]['fantasy'] = $statPoint;
          } else {
            $store[$statCombName]['fantasy'] = $statPoint; // custom
          }
          if ($_withOrigin) $store[$statCombName]['origin'] = $originStat['statCombPoint'];
          if ($_withOrder) $store[$statCombName]['order'] = $orderNumberRefTable[$statCombName];
        } else {
          if ($this->fantasyCalculatorType === FantasyCalculatorType::FANTASY_POINT) {
            $store[$key]['fantasy'] = __setDecimal($statPoint, self::FP_RESULT_PRECISION);
          } else if ($this->fantasyCalculatorType === FantasyCalculatorType::FANTASY_RATING) {
            $store[$key]['fantasy'] = $statPoint;
          } else {
            $store[$key]['fantasy'] = $statPoint; // custom
          }
          if ($_withOrigin) {
            $store[$key]['origin'] = __setDecimal($originStat['statCombPoint'], 2);
          }
          if ($_withOrder) $store[$key]['order'] = $orderNumberRefTable[$key];
        }
      }
    }
    if ($_withTotalPoint) {
      return [__setDecimal($totalPoint, 1), $pointSet];
    }
    return $pointSet;
  }


  public function makePointSet(array $_playerStats): array
  {
    $this->checkConfig();

    $CombCategoryTable = $this->getCombsWithCategoryTable();

    $statCombTable = $this->getTargetCombTable($_playerStats);

    $pointSet = [];
    foreach ($CombCategoryTable as $category => $colNameToCombs) {
      $pointSet[$category] = [];
      foreach ($colNameToCombs as $key => $statCombName) {
        $statPoint = $this->applyStatCombinationFpPolicy(
          $_playerStats,
          $this->statCombinationResolver($statCombName, $_playerStats),
          $statCombTable[$statCombName],
        );
        if ($statPoint == 0) continue;
        if (getType($key) === 'integer') {
          if ($this->fantasyCalculatorType === FantasyCalculatorType::FANTASY_POINT) {
            $pointSet[$category][$statCombName] = __setDecimal($statPoint, self::FP_RESULT_PRECISION);
          } else if ($this->fantasyCalculatorType === FantasyCalculatorType::FANTASY_RATING) {
            $pointSet[$category][$statCombName] = $statPoint;
          } else {
            $pointSet[$category][$statCombName] = $statPoint; // custom
          }
        } else {
          if ($this->fantasyCalculatorType === FantasyCalculatorType::FANTASY_POINT) {
            $pointSet[$category][$key] = __setDecimal($statPoint, self::FP_RESULT_PRECISION);
          } else if ($this->fantasyCalculatorType === FantasyCalculatorType::FANTASY_RATING) {
            $pointSet[$category][$key] = $statPoint;
          } else {
            $pointSet[$category][$key] = $statPoint; // custom
          }
        }
      }
    }
    return $pointSet;
  }

  public function temporaryValidateDraftSelection()
  {
    return [
      'isValid' => true,
      'message' => '임시 validation pass',
      'totalCost' => 0,
      'totalLevel' => 0,
      'totalPrice' => 0,
      'priceType' => 'gold',
      // 'rateType' => $policy['price']['rate']['type']
    ];
  }



  public function validateDraftSelection(array $_input): array
  {
    /**
     * draft only
     * 1. 항목이름 검사.
     * 2. cost, level, price 여부 검사
     */
    $selections = $_input['selections'];
    $rCost = (int)$_input['cost'];
    $rLevel = (int)$_input['level'];
    $rTotalPrice = (int)$_input['totalPrice'];

    $card = PlateCard::wherePlayerId($_input['player_id'])->first();

    $policy = $this->getDraftPolicy($card['grade']);

    $valid = true;
    $message = '정상처리';
    if ($_input['position'] === PlayerPosition::GOALKEEPER) {
      $exceptionNames = array_keys($this->getDraftCategoryMetaTable()[FantasyDraftCategoryType::ATTACKING]);
    } else {
      $exceptionNames = array_keys($this->getDraftCategoryMetaTable()[FantasyDraftCategoryType::GOALKEEPING]);
    }
    $availableNames = $this->getCombRepresentationNames();
    foreach ($selections as $key => $x) {
      if (!in_array($key, $availableNames)) {
        $valid = false;
        $message = '항목 이름 오류';
        break;
      } else if (in_array($key, $exceptionNames)) {
        $valid = false;
        $message = sprintf('%s 포지션은 %s 를 선택할 수 없음.', $_input['position'], $key);
        break;
      }
    }
    $totalCost = 0;
    $totalLevel = 0;
    $totalPrice = 0;
    $result = [
      'is_valid' => $valid,
      'message' => $message,
      'total_cost' => $totalCost,
      'total_level' => $totalLevel,
      'total_price' => $totalPrice,
      'price_type' => $policy['price']['type'],
      // 'rateType' => $policy['price']['rate']['type']
    ];


    // cost, level 계산
    foreach ($this->getDraftCategoryMetaTable() as $cate => $values) {
      foreach ($values as $name => $meta) {
        if (in_array($name, array_keys($selections))) {
          $totalCost += $meta['cost'];
          $totalLevel += $selections[$name];
          // if ($meta['levelMap']['price'])
          if (empty($meta['levelMap']['value'][$selections[$name]])) {
            $result['isValid'] = false;
            $result['message'] = sprintf('%s 스탯 레벨(%s) 초과 오류', $name, $selections[$name]);
            return $result;
          }
          // $price += $meta['levelMap']['price'][$selections[$name]];
        }
      }
    }

    // cost, level 유효성 검사 
    if ($policy['max']['cost'] < $totalCost) {
      $valid = false;
      $message = sprintf('코스트 (%s) 초과 오류', $totalCost);
    } else if ($policy['max']['level'] < $totalLevel) {
      $valid = false;
      $message = sprintf('레벨 (%s) 초과 오류', $totalLevel);
    } else { // cost, level 유효성 통과
      // price 정책 적용
      foreach ($policy['price']['table'] as $idx => $ref) {
        if ($ref['level'] === $totalLevel) {
          $totalPrice = $ref['price'];
          break;
        }
      }
    }

    if ($valid) {
      // request 정보와 일치여부 검사(요청이 구체적으로 완성되었을 때 주석 제거)
      if ($rCost !== $totalCost) {
        $valid = false;
        $message = sprintf('요청 cost(%s) 와 계산 cost(%s)가 일치하지 않음', $rCost, $totalCost);
      } else if ($rLevel !== $totalLevel) {
        $valid = false;
        $message = sprintf('요청 level(%s) 와 계산 level(%s)가 일치하지 않음', $rLevel, $totalLevel);
      } else if ($rTotalPrice !== $totalPrice) {
        $valid = false;
        $message = sprintf('요청 price(%s) 와 계산 price(%s)가 일치하지 않음', $rTotalPrice, $totalPrice);
      }
    }

    $result['is_valid'] = $valid;
    $result['message'] = $message;
    $result['total_cost'] = $totalCost;
    $result['total_level'] = $totalLevel;
    $result['total_price'] = $totalPrice;

    return $result;
  }

  public function isBenchPlayer(array $_playerStats): bool
  {
    if (
      (isset($_playerStats['game_started']) && $_playerStats['game_started'] == 1) ||
      (isset($_playerStats['total_sub_on']) && $_playerStats['total_sub_on'] == 1)
    ) {
      return false; // 통과 (계산 하기)
    }
    return true; // 계산 안함
  }

  public function addPowerRankingGameWonLost(&$_playerStats)
  {
    try {
      $schedule = Schedule::withUnrealSchedule()->where('id', $_playerStats['schedule_id'])->first()->toArray();
      $playerTeamId = $_playerStats['team_id'];
    } catch (Exception $e) {
      logger('--->');
      logger($_playerStats['schedule_id']);
      logger($_playerStats);
      logger('<---');
      logger($e);
      dd('xlxlxlx');
    }
    /** 
     * schedules 테이블의 수집 시점이 과거일 경우 winner 정보가 NULL일 수 있으므로 
     * opta에서 실시간 파싱한 데이터(status가 Played인)에서 winner를 $_playerStats에 주입하여 winner 정보를 파악한다.
     */
    $winner = $_playerStats['winner'];
    //-> 임시 tray catch
    try {
      if ($winner === ScheduleWinnerStatus::DRAW) {
        $_playerStats['game_won'] = (int)false;
        $_playerStats['game_lost'] = (int)false;
      } else if ($schedule[$winner . '_' . 'team_id'] === $playerTeamId) {
        $_playerStats['game_won'] = (int)true;
        $_playerStats['game_lost'] = (int)false;
      } else {
        $_playerStats['game_won'] = (int)false;
        $_playerStats['game_lost'] = (int)true;
      }
    } catch (\Exception $e) {
      logger('winner Error');
      logger($schedule);
      logger($_playerStats);
    }
    //<- 임시 tray catch
  }

  public function makeFreePlayerPool()
  {
    // fantasy free game
    // transaction 내에서 처리 필요
    $gradeMap = $this->getConfig()['gradeMap'];

    FreePlayerPool::whereHas('season', function ($query) {
      $query->currentSeasons(false);
    })->delete();

    $plateCard = PlateCard::selectRaw('player_id, season_id as cur_season_id, team_id, position, price')->isOnSale();

    $pCardlogTableName = PlateCardPriceChangeLog::getModel()->getTable();

    $prankInst = PlateCardPriceChangeLog::whereNotNull('schedule_id')
      ->selectRaw(
        sprintf(
          'cur_season_id as season_id,
            %s.player_id, 
            team_id, 
            position, 
            price, 
            AVG(power_ranking) avg_pw,
            ROW_NUMBER() over(ORDER BY AVG(power_ranking) DESC) AS nrank',
          $pCardlogTableName
        )
      )->joinSub($plateCard, 'pcard', function ($join) use ($pCardlogTableName) {
        $join->on($pCardlogTableName . '.player_id', 'pcard.player_id');
      })->groupBy(['season_id', 'player_id', 'team_id', 'position']);

    $prankInst->get()->groupBy('season_id')->map(function ($item) use ($gradeMap, &$result) {
      $totalCount = $item->count();
      foreach ($gradeMap as $grade => $percent) {
        $rank = __setDecimal(($totalCount * ($percent / 100)), 0, 'round');
        $gradeRankMap[$grade] = (int)$rank;
      }
      $gradeRankMap = array_reverse($gradeRankMap);
      $grouped = $item->toArray();
      foreach ($grouped as $rank => &$oneSet) {
        foreach ($gradeRankMap as $powerGrade => $rankPoint) {
          if ($rank > $rankPoint) {
            $oneSet['power_grade'] = $powerGrade;
            $oneSet['nrank'] = $rank;
            FreePlayerPool::where([
              ['season_id', $oneSet['season_id']],
              ['team_id', $oneSet['team_id']],
              ['player_id', $oneSet['player_id']],
            ])->restore();
            FreePlayerPool::updateOrCreateEx(
              [
                'season_id' => $oneSet['season_id'],
                'team_id' => $oneSet['team_id'],
                'player_id' => $oneSet['player_id'],
              ],
              $oneSet
            );
            break;
          }
        }
      }
    });

    // 시즌 시작(가격변동 데이터 없을 경우)보정 pool 생성
    PlateCard::isOnSale()->doesntHave('freePlayerPool')->with(['freePlayerPool' => function ($query) {
      $sIds = Season::idsOf([SeasonWhenType::BEFORE], SeasonNameType::ALL, 1, [config('constant.LEAGUE_CODE.UCL')]);
      $query->whereIn('season_id', $sIds)->withTrashed();
    }])->get()->map(function ($item) {
      $one  = [
        'season_id' => $item->season_id,
        'player_id' => $item->player_id,
        'team_id' => $item->team_id,
        'position' => $item->position,
        'price' => $item->price,
        'avg_pw' => 99999,
        'nrank' => 99999,
        'power_grade' => $item->grade === OriginGrade::SS ? PowerGrade::S : $item->grade,
      ];
      if (empty($item->freePlayerPool->toArray())) {
      } else {
        $one['position'] = $item->freePlayerPool[0]->position;
        $one['power_grade'] = $item->freePlayerPool[0]->power_grade;
      }
      $poolOne = (new FreePlayerPool);
      foreach ($one as $col => $value) {
        $poolOne->{$col} = $value;
      }
      $poolOne->save();
    });
  }

  public function calculate(array|int $_playerStats, $_withMeta = false, $_isFree = false): null|float|array
  {
    /**
     * player당 fantasy point 계산 시간 약 0.004초~0.005초
     * player 20만명 -> 800초~1000초 -> 약 15분
     */

    // calculator type 에 따른 계산 분기
    if (
      $this->fantasyCalculatorType === FantasyCalculatorType::FANTASY_CARD_GRADE ||
      $this->fantasyCalculatorType === FantasyCalculatorType::FANTASY_DRAFT ||
      $this->fantasyCalculatorType === FantasyCalculatorType::FANTASY_INGAME_POINT ||
      $this->fantasyCalculatorType === FantasyCalculatorType::FANTASY_POINT_REWARD ||
      $this->fantasyCalculatorType === FantasyCalculatorType::FANTASY_MOMENTUM ||
      $this->fantasyCalculatorType === FantasyCalculatorType::FANTASY_PROJECTION ||
      $this->fantasyCalculatorType === FantasyCalculatorType::FANTASY_BURN ||
      $this->fantasyCalculatorType === FantasyCalculatorType::FANTASY_FREE_GAME ||
      $this->fantasyCalculatorType === FantasyCalculatorType::FANTASY_OVERALL
    ) {
      return $this->{'get' . $this->fantasyCalculatorType}($_playerStats, $_withMeta, $_isFree);
    }

    if ($this->isBenchPlayer($_playerStats)) {
      return 0;
    }

    // calculator type에 따른 전처리
    switch ($this->fantasyCalculatorType) {
      case FantasyCalculatorType::FANTASY_POWER_RANKING:
        $this->addPowerRankingGameWonLost($_playerStats);
        break;
      case FantasyCalculatorType::FANTASY_RATING:
        if (isset($_playerStats['saves'])) {
          $_playerStats['saves_bonus'] = $_playerStats['saves'];
        }
        break;
      default:
        # code...
        break;
    }

    $this->checkConfig();

    $statCombTable = $this->getTargetCombTable($_playerStats);
    $totalPoint = 0;
    foreach ($statCombTable as $statCombName => $statCombRefValue) {
      $statPoint = $this->applyStatCombinationFpPolicy(
        $_playerStats,
        $this->statCombinationResolver($statCombName, $_playerStats),
        $statCombRefValue
      );
      $totalPoint += $statPoint;
    }
    // dd($statPoint);
    // 메소드 이름으로 계산타입에 따른 분기처리
    // 메소드를 아래와 같이 실행 시 대소문자 구분하지 않고 메소드가 실행됨.
    return $this->{'get' . $this->fantasyCalculatorType}($totalPoint, $_playerStats);
  }


  protected function getFantasyFreeGame(array $_datas)
  {
    /**
     * $_datas = [
     *  'position' => 'attacker',
     * 'team_map' => $teamScheduleMap,
     *  'season_id' => 'xxxxxx', 
     * ];
     */

    $gradeMap = $this->getConfig()['gradeMap'];
    $momPercentMap = $this->getConfig()['momPerCentPoints'];
    $result = [
      'player' => null,
      'power_grade' => null,
      'isMom' => false,
    ];



    // 1-1. 선수 표출
    $seasonId = $_datas['season_id'];
    $position = $_datas['position'];
    $randPoint = mt_rand(1, 100);


    $player = null;
    foreach (array_reverse($gradeMap) as $g => $r) {
      if ($randPoint > $r) {
        // 등급 내 동일확률로 선수 랜덤 표출
        $result['power_grade'] = $g;
        $player = FreePlayerPool::whereHas('plateCard', function ($query) {
          $query->isOnSale();
        })->where([
          ['season_id', $seasonId],
          ['power_grade', $g],
          ['position', $position],
        ])->whereIn('team_id', $_datas['team_map']['teams'])->inRandomOrder()->first()?->toArray();
        break;
      }
    }

    if (is_null($player)) {
      $playerTemp = PlateCard::isOnSale()
        ->whereIn('team_id', $_datas['team_map']['teams'])
        ->where('position', $position)
        ->with(['freePlayerPool' => function ($query) {
          $query->withTrashed();
        }])->inRandomOrder()
        ->first()->toArray();

      $player = [];
      $player['season_id'] = $playerTemp['season_id'];
      $player['player_id'] = $playerTemp['player_id'];
      $player['position'] = $playerTemp['position'];
      if (isset($player['free_player_pool'])) {
        $player['avg_pw'] = $playerTemp[0]['free_player_pool']['avg_pw'];
        $player['nrank'] = $playerTemp[0]['free_player_pool']['nrank'];
        $player['power_grade'] = $playerTemp[0]['free_player_pool']['power_grade'];
      } else {
        $player['avg_pw'] = 9999990;
        $player['nrank'] = 9999990;
        $player['power_grade'] = $playerTemp['grade'] === OriginGrade::SS ? OriginGrade::S : $playerTemp['grade'];
      }
    }

    $result['player'] = $player;

    // 2. MOM
    $momFieldPlayerPercentPoint = $momPercentMap['field'];
    $momGoalKeeperPercentPoint = $momPercentMap['keeper'];

    if ($position === PlayerPosition::GOALKEEPER) {
      $targetMomPercentPoint = $momFieldPlayerPercentPoint;
    } else {
      $targetMomPercentPoint = $momGoalKeeperPercentPoint;
    }

    $randValue = mt_rand(0, 100000);
    $randCutPoint = $targetMomPercentPoint[$result['power_grade']];
    if ($randValue < $randCutPoint) {
      $result['isMom'] = true;
    }

    // 3. LEVEL & 등급
    /**
     * @var FantasyCalculator $draftCalculator
     */
    $draftCalculator = app(FantasyCalculatorType::FANTASY_DRAFT, [0]);
    $randomPoint = mt_rand() / mt_getrandmax();
    $draftCates = array_keys($draftCalculator->getDraftCategoryMetaTable($position));

    if ($position === PlayerPosition::GOALKEEPER) {
      $model = FreeKeeperLevelGradePool::class;
    } else {
      $model = FreeFieldLevelGradePool::class;
    }

    $levelGrade = $model::whereNotNull('rate')
      ->where('rate', '>', $randomPoint)->orderBy('rate')->first();

    if ($levelGrade === null) {
      $levelGrade = $model::whereNull('rate')->orderByRaw('RAND()')->first();
    }

    $levelGrade = $levelGrade->toArray()['lv_grade'];

    preg_match('/(\d{4,4})_(\S*)/', $levelGrade, $matches);
    $levels = $matches[1];
    $result['card_grade'] = $matches[2];


    $levelMap = [];
    foreach ($draftCates as $idx => $value) {
      $levelMap[$value] = $levels[$idx];
    }
    $result['levels'] = $levelMap;


    // 4. 3강 달성 여부
    $draftCateRate = $this->getConfig()['threeStrengthPercentMap'];
    $threeStrength = [];

    $randomPoint = (mt_rand() / mt_getrandmax()) * 100;
    foreach ($levelMap as $cate => $level) {
      if ($level >= 3) {
        logger($cate);
        $cutRate = $draftCateRate[$cate][$position];
        logger('cutRate' . '=' . $cutRate);
        logger('randomPoint' . '=' . $randomPoint);
        if ($randomPoint <= $cutRate) {
          $threeStrength[] = $cate;
        }
      }
    }
    $result['three_strength'] = $threeStrength;
    return $result;
  }






  // 임시 메소드
  private function getFantasyBurnSumLvl($_draftLevel, $_maxLevel): ?string
  {
    foreach (config('fantasyburnpoint.sum_level')[$_draftLevel] as $range => $cutline) {
      if ($_maxLevel >= $cutline) {
        return $range;
      }
    }
    return null;
  }

  protected function getFantasyBurn(array $userPlateCard): array
  {
    // 공통 로직 부분
    $levels = [];
    foreach (config('fantasyingamepoint.FANTASYINGAMEPOINT_REFERENCE_TABLE_V0.LevelCate') as $category) {
      $levels[] = $userPlateCard[$category];
    }
    $levelRange = $this->getFantasyBurnSumLvl($userPlateCard['draft_level'], max($levels));

    $minMax = RefBurnCard::where([
      'price_grade' => $userPlateCard['draft_price_grade'],
      'level_range' => $levelRange,
    ])
      ->select([
        $userPlateCard['card_grade'] . '_min as min',
        $userPlateCard['card_grade'] . '_max as max'
      ])
      ->first()
      ->toArray();

    // 공통 로직 부분 끝

    return $minMax;
  }
  // -->>
  // fantasy calculate 계산 분기처리 메소드 정의 시작
  protected function getFantasyPoint(float $_totalPoint): float
  {
    return __setDecimal($_totalPoint, self::FP_RESULT_PRECISION);
  }

  protected function getAdditionalGradePoint($_userPlateCardAttrs, $_fantasyPoint)
  {
    $userCardGrade = $_userPlateCardAttrs['card_grade'];
    // %(percent)
    $conf = ($this->getConfig());
    $weightRate = $conf['GradeWeightRate'];
    return $_fantasyPoint * ($weightRate[$userCardGrade] / 100);
  }


  public function getAdditionalSpecialStatPoint($_userPlateCardAttrs, $_isMom)
  {
    $conf = ($this->getConfig());
    $additionalPoint = 0.0;
    $momType = $_isMom ? 'mom_yes' : 'mom_no';
    $momPointMap = $conf['MomTable']; // 스탯별 추가 포인트
    $levelCate = $conf['LevelCate'];
    foreach ($levelCate as $cateName) {
      if ($level = $_userPlateCardAttrs[$cateName]) {
        $additionalPoint += $momPointMap[$momType][$level];
      }
    }
    return $additionalPoint;
  }

  private function getAdditonalLevelProjection(array $_userPlateCard)
  {
    $additonalPoint = 0;
    $addPointConfig = config('fantasyingamepoint.FANTASYINGAMEPOINT_REFERENCE_TABLE_V0.Projection');

    // 카테고리 별 레벨 포인트
    foreach (FantasyPointCategoryType::getStatValues() as $category) {
      $categoryColumn = $category . '_level';
      $specialColumn = $category;
      if ($category === FantasyPointCategoryType::OFFENSIVE) {
        $categoryColumn = 'attacking_level';
        $specialColumn = 'attacking';
      }

      if ($_userPlateCard[$categoryColumn] > 0) {
        $additonalPoint += $addPointConfig[$category]['category'][$_userPlateCard[$categoryColumn]];
        logger($categoryColumn);
        logger($additonalPoint);
      }

      // 3강 계산
      if (array_search($specialColumn, $_userPlateCard['special_skills']) !== false) {
        $additonalPoint += $addPointConfig[$category]['special_skill'];
        logger($category);
        logger($additonalPoint);
      }
    }

    return $additonalPoint;
  }

  private function getAdditonalLevelPoint(string $_scheduleId, array $_userPlateCard,  array $_originStats, array $_fpPoints, bool $_isProjection = false)
  {
    $additionalPoint = 0;

    $addPointConfig = config('fantasyingamepoint.FANTASYINGAMEPOINT_REFERENCE_TABLE_V0.AdditionalPoint');

    // 카테고리 별 가중치 계산
    foreach ($_fpPoints as $category => $point) {
      if (strpos($category, '_point')) {
        $statCategory = Str::replace('_point', '', $category);
        if (in_array($statCategory, FantasyPointCategoryType::getStatValues())) {
          $categoryLevel = $_userPlateCard[$addPointConfig[$statCategory]['category']['column'] . '_level'];
          foreach ($addPointConfig[$statCategory]['category']['range'] as $standardP => $range) {
            if ($point >= $standardP && $categoryLevel > 0) {
              $additionalPoint += $range[$categoryLevel];
              break;
            }
          }
        }
      }
    }

    $categoryPoint = $additionalPoint;


    // 3강 계산
    if (!empty($_userPlateCard['special_skills'])) {
      foreach ($_userPlateCard['special_skills'] as $category) {
        switch ($category) {
          case StatCategory::ATTACKING:
            $category = FantasyPointCategoryType::OFFENSIVE;
            break;
          case StatCategory::DUELS:
            $category = FantasyPointCategoryType::DUEL;
            break;
          default;
        }

        $specialSkillArr = $addPointConfig[$category]['specialSkill'];
        $draftPassConfig = config('fantasydraft.FANTASYDRAFT_REFERENCE_TABLE_V0.Policy');
        foreach ($specialSkillArr['range'] as $standard => $point) {
          $realStat = BigDecimal::of($_originStats[Str::snake($specialSkillArr['stat'][0])]);
          if ($category === FantasyPointCategoryType::PASSING) {
            // .. 분기처리 말고.. 다른방법이 좋을 듯함 우선은 이대로.
            $firstStat = Str::snake($specialSkillArr['stat'][0]);
            $secondStat = Str::snake($specialSkillArr['stat'][1]);
            $minValue = $draftPassConfig[$firstStat . '/' . $secondStat]['minValueRef']['sub_position'][$_originStats['summary_position']];
            if ($_originStats[$secondStat] >= $minValue) {
              $realStat = BigDecimal::of(0);
              if ($_originStats[$firstStat] > 0 && $_originStats[$secondStat] > 0) {
                $realStat = BigDecimal::of($_originStats[Str::snake($specialSkillArr['stat'][0])])->dividedBy(BigDecimal::of($_originStats[Str::snake($specialSkillArr['stat'][1])]), 2, RoundingMode::HALF_UP)->multipliedBy(100);
              }
            }
          }
          if ($realStat->compareTo($standard) >= 0) {
            $additionalPoint += $point;
            break;
          }
        }
      }
    }

    $totalPoint = $additionalPoint;

    TempUserFPAdd::updateOrCreateEx(
      [
        'schedule_id' => $_scheduleId,
        'user_plate_card_id' => $_userPlateCard['id'],
      ],
      [
        'schedule_id' => $_scheduleId,
        'special_skills' => $_userPlateCard['special_skills'],
        'category_point' => $categoryPoint,
        'special_skill_point' => $totalPoint - $categoryPoint,
        'total_point' => $totalPoint
      ]
    );
    return $additionalPoint;
  }


  protected function getProjectionPoint(array $datas)
  {
    return $this->getFantasyIngamePoint($datas, false, false);
  }

  protected function getFantasyIngamePoint(array $datas, bool $_withMeta, bool $_isProjection = false): float|array
  {
    /**
     * $datas = [
     * 'user_card_attrs' => [...]
     * 'fantasy_point' => 123.00,
     * 'is_mom' => 1,
     * 'schedule_id' => 'xxxxxxxxxxx',
     * 'origin_stats' => $_datas['specifiedAttrs']['player'][$playerId],
     * 'fp_stats' => $_datas['specifiedAttrs']['playerFp'][$playerId],
     * ]
     */
    $inGamePoint = 0;
    $additionalStatPoint = 0;
    $userPlateCardAttrs = $datas['user_card_attrs'];
    $fantasyPoint = $datas['fantasy_point'];
    $isMom = $datas['is_mom'];
    $additionalGradePoint = $this->getAdditionalGradePoint($userPlateCardAttrs, $fantasyPoint);
    $additionalStatPoint = $this->getAdditionalSpecialStatPoint($userPlateCardAttrs, $isMom);
    $additionalLevelPoint = 0;
    if ($_isProjection || (!empty($datas['origin_stats']) && !empty($datas['fp_stats']))) {
      if ($_isProjection) {
        $additionalLevelPoint = $this->getAdditonalLevelProjection($datas['user_card_attrs']);
      } else {
        $additionalLevelPoint = $this->getAdditonalLevelPoint($datas['schedule_id'], $datas['user_card_attrs'], $datas['origin_stats'], $datas['fp_stats'], $_isProjection);
      }
    }
    $inGamePoint = __setDecimal($fantasyPoint + $additionalGradePoint + $additionalStatPoint + $additionalLevelPoint, self::FIP_RESULT_PRECISION);

    if ($_withMeta) {
      return [
        'ingame_point' => $inGamePoint,
        'level_weight' => $additionalStatPoint,
      ];
    }
    return $inGamePoint;
  }


  protected function getFantasyPointReward() {}



  protected function getFantasyPowerRanking(float $_totalPoint): float
  {
    // 준비중
    return __setDecimal($_totalPoint, self::POWERRANKING_RESULT_PRECISION);
  }

  protected function getFantasyCardGrade($_gradeAttrs): array
  // ['season_name' => $commonRowOrigin['season_name'],
  // 'fantasy_point' => $fantasyPoint,
  // 'rating_set_sum' => $ratingSet,]
  {

    if ($this->isBenchPlayer($_gradeAttrs)) {
      return [
        'point_c' => null,
        'rating_c' => null,
        'card_c' => -100,
      ];
    }
    $pointCQuantile = (RefPointcQuantile::where('playing_season_name', $_gradeAttrs['season_name'])
      ->where('position', $this->getPositionSummary($_gradeAttrs))->first()->toArray());
    $pointC = ($_gradeAttrs['fantasy_point'] - $pointCQuantile['quantile_middle']) / ($pointCQuantile['quantile_top'] - $pointCQuantile['quantile_bottom']) + $pointCQuantile['base_offset'];

    /**
     * @var FantasyCalculator $ratingCCalculator 
     */
    $ratingCCalculator = app(FantasyCalculatorType::FANTASY_RATING_C, [0]);
    $ratingC = $ratingCCalculator->calculate(array_merge($_gradeAttrs, $_gradeAttrs['rating_set_sum']));
    $cardC = ($pointC * 0.95 + $ratingC * 0.05) * 10;
    return [
      'point_c' => __setDecimal($pointC, 2),
      'rating_c' => __setDecimal($ratingC, 2),
      'card_c' => __setDecimal($cardC, 2),
    ];
  }


  private function applyStatCombinationDraftPolicy(array $_playerStats, array $_resolvedStatComb)
  {
    $statCombName = $_resolvedStatComb['statCombName'];

    $policy = $this->getPolicy();
    if (isset($policy[$statCombName])) {
      $statCombPolicy = $policy[$statCombName];
      $type = $statCombPolicy['type'];
      switch ($type) {
        case FantasyPolicyType::QUANTILE_MIN_VALUE:
          if (!empty($_playerStats) && $this->quantileMinValueChecker($statCombPolicy, $statCombName, $_playerStats)) return 0;
          break;

        default:
          # code...
          break;
      }
    }
    return $_resolvedStatComb['statCombPoint'];
  }

  protected function getFantasyDraft($_draftAttrs, $_withMeta): array
  /**
   * $_draftAttrs = [
   *  'opta_stats' => [...]
   *  'selections' => [...]
   * ]
   */
  {
    $specialSkills = config($this->getFantasyTableConfigPath() . '.SpecialSkills');
    $userSpecialSkills = [];
    $draftResult = [
      'success' => [],
      'failure' => [],
      'answer' => [],
      // 'meta' => [
      //   'total_selection' => 0,
      //   'total_success' => 0,
      //   'total_cost' => 0,
      // ],
    ];

    $totalSelection = 0;
    $totalSuccess = 0;
    $totalCost = 0;
    $selectionCost = 0;
    $answerCost = 0;

    $draftTable = $this->getDraftCategoryMetaTable();
    foreach ($this->getCombsWithCategoryTable() as $cate => $nameCombSet) {
      foreach ($nameCombSet as $name => $comb) {
        $statCombPoint =
          $this->applyStatCombinationDraftPolicy(
            $_draftAttrs['opta_stats'],
            $this->statCombinationResolver($comb, $_draftAttrs['opta_stats']),
          );
        // logger('->');
        // logger($this->statCombinationResolver($comb, $_draftAttrs['opta_stats'])['statCombPoint']);
        // logger($statCombPoint);
        // logger('<-');
        $draftLevelMap = $draftTable[$cate][$name]['levelMap']['value'];
        krsort($draftLevelMap);

        if ($_draftAttrs['selections'][$name] > 0) { // 사용자가 선택한 항목에 대해서만
          // 정답 맞춘 선택 뽑기 ->
          $selectionCost += $draftTable[$cate][$name]['cost'];
        }
        $selectionLevel = $_draftAttrs['selections'][$name];
        $totalSelection += $selectionLevel;
        $totalCost += $draftTable[$cate][$name]['cost'];
        foreach ($draftLevelMap as $level => $statPoint) {
          if ($statPoint <= $statCombPoint) {
            if ($selectionLevel == $level) {
              if (in_array($name, $specialSkills) && $level === 3) {
                $userSpecialSkills[] = $cate;
              }
              $answerCost += $draftTable[$cate][$name]['cost'];
              $draftResult['success'][$cate][$name] = $selectionLevel;
              $totalSuccess += $selectionLevel;
            }
            break;
          }
        }
        if (!isset($draftResult['success'][$cate][$name])) {
          $draftResult['failure'][$cate][$name] = $selectionLevel;
        }
        $draftResult['stat'][$cate][$name] = $statCombPoint;
        // -> 원래 정답 수집
        foreach ($draftLevelMap as $level => $statPoint) {
          if ($statPoint <= $statCombPoint) {
            $draftResult['answer'][$cate][$name] = $level;
            break;
          }
        }
        if (!isset($draftResult['answer'][$cate][$name])) {
          $draftResult['answer'][$cate][$name] = null;
        }
      }
    }

    if ($_withMeta) {
      $draftResult['meta']['total_selection'] = $totalSelection;
      $draftResult['meta']['total_success'] = $totalSuccess;
      $draftResult['meta']['total_cost'] = $totalCost;
      $draftResult['meta']['selection_cost'] = $selectionCost;
      $draftResult['meta']['answer_cost'] = $answerCost;
      $draftResult['meta']['user_special_skills'] = $userSpecialSkills;
      $draftResult['meta']['origin_stats']['accurate_pass'] = $_draftAttrs['opta_stats']['accurate_pass'] ?? 0;
      $draftResult['meta']['origin_stats']['total_pass'] = $_draftAttrs['opta_stats']['total_pass'] ?? 0;
    }
    return $draftResult;
  }



  protected function getFantasyRatingC($_totalRatingC)
  {
    return __setDecimal($_totalRatingC, self::RATING_RESULT_PRECISION, 'floor');
  }

  // protected function getFantasyPlateCardPrice(float $_totalPoint): float
  // {
  //   // 준비중
  //   $price = 0;
  //   return $price;
  // }

  protected function getFantasyScheduleGrade(float $_totalPoint): string
  {
    // 준비중
    $grade = 'SS'; // enum값으로
    return $grade;
  }

  protected function getFantasyRating(float $_totalPoint, $_playerStats): float
  {
    $_totalPoint += 6;
    if ($_totalPoint < 2) {
      $_totalPoint = 2;
    } else if ($_totalPoint > 10) {
      $_totalPoint = 10;
    }
    return __setDecimal($_totalPoint, self::RATING_RESULT_PRECISION, 'floor');
  }

  protected function getFantasyMomentum(array $_totalStats): float
  {
    $teamPoint = 0;
    $weightMap = $this->getConfig()['CombTable'];
    foreach ($weightMap as $colName => $weight) {
      $teamPoint += $_totalStats[$colName] * $weight;
    }
    return $teamPoint;
  }

  protected function getFantasyProjection(array $cardIdSet): ?float
  {
    // 프로젝션 계산 메소드는 고도화 안한다.
    try {
      $userPlateCardId = $cardIdSet['user_plate_card_id'] ?? null;
      $plateCardId = $cardIdSet['plate_card_id'] ?? null;
      $rawData = $cardIdSet['raw_data'] ?? null;
      if (!($userPlateCardId !== null xor  $plateCardId !== null xor $rawData == !null)) {
        logger('user plate card id, plate card id, raw data 셋 중 하나의 값만 필요합니다.');
        return null;
      }

      if ($userPlateCardId) {
        $userPlateCardInst = UserPlateCard::whereId($userPlateCardId)
          ->whereNot('card_grade', CardGrade::NONE)
          ->where('status', PlateCardStatus::COMPLETE)
          ->withWhereHas('plateCard', function ($query) {
            $query->isOnSale(true);
          })->first();

        // dd($userPlateCardInst->toArray());
        // 없을 때
        if (!$userPlateCardInst) {
          logger('이 선수의 경기 데이터가 없습니다.');
          return -777;
        } else {
          $userPlateCardArr = $userPlateCardInst->toArray();
          $plateCardId = $userPlateCardArr['plate_card']['id'];
          unset($userPlateCardArr['plate_card']);
        }
      } else if ($rawData) {
        $userPlateCardArr = $cardIdSet['raw_data'];
        $plateCardId = $userPlateCardArr['plate_card_id'];
      } else {
        $userPlateCardArr = [
          'attacking_level' => 0,
          'goalkeeping_level' => 0,
          'passing_level' => 0,
          'defensive_level' => 0,
          'duel_level' => 0,
          'is_mom' => 0,
          'card_grade' => CardGrade::NORMAL,
        ];
      }

      // 플레이트 카드
      $plateCardAttr = PlateCard::isOnSale()->whereId($plateCardId)->first();

      // 없을 때
      if ($plateCardAttr === null) {
        return 0; // ex) 서비스하지 않는 곳으로 이적
      }

      // 프로젝션 계산 시작

      $plateCardAttr = $plateCardAttr->toArray();

      $sc = Schedule::where([
        ['league_id', $plateCardAttr['league_id']],
        ['status', ScheduleStatus::FIXTURE],
      ])->where(
        function ($query) use ($plateCardAttr) {
          $query->where('away_team_id', $plateCardAttr['team_id'])
            ->orWhere('home_team_id', $plateCardAttr['team_id']);
        }
      )->oldest('started_at')->first()?->toArray();

      // 시즌종료 대응
      if (is_null($sc)) {
        return 0;
      }

      if ($sc['away_team_id'] == $plateCardAttr['team_id']) {
        $playerTeamSideCondition = ScheduleWinnerStatus::AWAY;
        $vsTeamId = $sc['home_team_id'];
        $vsTeamSideCondition = ScheduleWinnerStatus::HOME;
      } else if ($sc['home_team_id'] == $plateCardAttr['team_id']) {
        $playerTeamSideCondition = ScheduleWinnerStatus::HOME;
        $vsTeamId = $sc['away_team_id'];
        $vsTeamSideCondition = ScheduleWinnerStatus::AWAY;
      }


      // Base projection teamside value
      $teamSideProjectionValue = RefPlayerBaseProjection::where(
        [
          ['player_id', $plateCardAttr['player_id']],
          ['league_id', $plateCardAttr['league_id']],
          ['team_side', $playerTeamSideCondition],
        ]
      )->value('base_value');

      // ref player base projection에 없다
      if ($teamSideProjectionValue === null) {
        $teamSideProjectionValue = RefTeamDefaultProjection::where([
          ['league_id', $plateCardAttr['league_id']],
          ['team_side', $playerTeamSideCondition],
          ['team_id', $plateCardAttr['team_id']],
        ])->value($plateCardAttr['position']);
      }

      $teamWeight = RefTeamProjectionWeight::where([
        ['league_id', $plateCardAttr['league_id']],
        ['season_id', $plateCardAttr['season_id']],
        ['vs_team_id', $vsTeamId],
        ['team_side', $vsTeamSideCondition],
      ])->value($plateCardAttr['position']);


      $bProjection =  $teamSideProjectionValue * $teamWeight;


      // TODO : origin_stats, fp_stats ingamePoint 계산할 때 필요하므로 추후에 채울 것.
      $datas = [
        'user_card_attrs' => $userPlateCardArr,
        'fantasy_point' => $bProjection,
        'is_mom' => $userPlateCardArr['is_mom'],
        'origin_stats' => [],
        'fp_stats' => [],
      ];

      // return $this->getFantasyIngamePoint($datas, false);
      return $this->getProjectionPoint($datas);
    } catch (\Exception $e) {
      logger(sprintf('projection 계산 에러 %s / %s ', $datas['user_plate_card_id'], $datas['plate_card_id']));
      return -999;
    }
  }

  /* FANTASY OVERALL START  */
  protected function getFreeCardFantasyOverall(int $_memoryId)
  {
    $mine = false;
    $memoryInfo = FreeGameLineupMemory::where('id', $_memoryId)->first();

    if (is_null($memoryInfo)) {
      return [];
    }
    try {
      // baseOverall
      $baseOverall = RefPlayerOverallHistory::where([
        ['player_id', $memoryInfo->player_id],
        ['is_current', true]
      ])->first();

      if (!is_null($baseOverall)) {
        Schema::connection('simulation')->disableForeignKeyConstraints();

        $config = config('fantasyoverall.additional');

        // 1. 등급 및 mom 가산점
        $allPoint = $this->getGradeMomPoint($config, $memoryInfo->card_grade, $memoryInfo->is_mom);

        $updateArr = [
          'memory_id' => $_memoryId,
          'player_id' => $memoryInfo->player_id,
        ];

        // 3. 카테고리별 레벨
        $updateArr = $this->getCategoryLevelPoint($config, $allPoint, $memoryInfo, $baseOverall, $updateArr, $mine);

        // 7.sub_position 찾기
        // formaion_used 찾기
        $updateArr['sub_position'] = $baseOverall->sub_position;
        $updateArr['second_position'] = $baseOverall->second_position;
        $updateArr['third_position'] = $baseOverall->third_position;


        // 6.final_overall 계산하기
        $updateArr = $this->getFinalOverall($updateArr);
        return $updateArr;
      }
      return [];
    } catch (Exception $e) {
      logger($_memoryId);
      logger($e);
      throw ($e);
    } finally {
      Schema::connection('simulation')->enableForeignKeyConstraints();
    }
  }

  protected function getFantasyOverall(int $_userPlateCardId, $_withMeta, $_isFree)
  {
    if ($_isFree) {
      return $this->getFreeCardFantasyOverall($_userPlateCardId);
    }

    $userCardInfo = UserPlateCard::withoutGlobalScope('excludeBurned')
      ->with([
        'draftSelection.schedule:id,home_team_id,away_team_id,home_formation_used,away_formation_used',
        'refPlayerOverall',
        'plateCardWithTrashed'
      ])
      ->where('id', $_userPlateCardId)
      ->first();

    try {
      // baseOverall
      $baseOverall = $userCardInfo->refPlayerOverall;
      if (is_null($baseOverall)) {
        $baseOverall = $userCardInfo->plateCardWithTrashed->currentRefPlayerOverall;
      }

      if (!is_null($baseOverall)) {
        Schema::connection('simulation')->disableForeignKeyConstraints();
        $config = config('fantasyoverall.additional');

        // 1. 등급 및 mom 가산점
        $allPoint = $this->getGradeMomPoint($config, $userCardInfo->card_grade, $userCardInfo->is_mom);

        $updateArr = [
          'user_id' => $userCardInfo->user_id,
          'user_plate_card_id' => $userCardInfo->id,
          'season_id' => $userCardInfo->draft_season_id,
          //'player_id' => $userCardInfo->draftSelection->player_id,
        ];

        // 3. 카테고리별 레벨
        $updateArr = $this->getCategoryLevelPoint($config, $allPoint, $userCardInfo, $baseOverall, $updateArr);

        // 7.sub_position 찾기
        // formaion_used 찾기
        $updateArr = $this->getFindPosition($userCardInfo, $baseOverall, $updateArr);

        // 6.final_overall 계산하기
        $updateArr = $this->getFinalOverall($updateArr);

        SimulationOverall::create($updateArr);
        // SimulationOverall::updateOrCreateEx(
        //   ['user_plate_card_id' => $_userPlateCardId],
        //   $updateArr
        // );
      }
    } catch (Exception $e) {
      logger($_userPlateCardId);
      logger($e);
      throw ($e);
    } finally {
      Schema::connection('simulation')->enableForeignKeyConstraints();
    }
  }

  // 1. 등급 및 mom 가산점
  private function getGradeMomPoint($config, $grade, $isMom)
  {
    // 1. 등급가산점
    $gradePoint = $config['grade'][$grade];

    $allPoint = $gradePoint;

    // 2. mom 가산점
    $momPoint = $config['mom'][$isMom];
    $allPoint += $momPoint;

    return $allPoint;
  }

  private function getCategoryLevelPoint($config, $allPoint, $_userPlateCard, $baseOverall, &$updateArr, $mine = true)
  {
    $isWelcome = false;
    // 3. 카테고리별 레벨
    foreach (SimulationCategoryType::getValues() as $category) {
      $column = $category . '_level';
      if (!is_null($_userPlateCard->$column)) {
        ${$category . 'Point'} = $config['category'][$_userPlateCard->$column];

        // 4. 특수스탯 적중
        if ($mine) {
          $isWelcome = $_userPlateCard->is_free;
          if (!$isWelcome) {
            $draftSkill = DraftComplete::where('user_plate_card_id', $_userPlateCard->id)->first();

            foreach (config('fantasydraft.FANTASYDRAFT_REFERENCE_TABLE_V0.Categories')[$category] as $stat => $info) {
              if ($draftSkill->$stat > 0) {
                $overallColumn = $config['special_skill']['stat'][$stat][0];
                if (in_array($overallColumn, SimulationCategoryType::getValues())) {
                  $overallColumn .= 'Stat';
                }
                if (!isset(${$overallColumn . 'Point'})) ${$overallColumn . 'Point'}  = 0;
                ${$overallColumn . 'Point'} += $draftSkill->$stat;

                if (count($config['special_skill']['stat'][$stat]) > 1) {
                  $overallColumn2 = $config['special_skill']['stat'][$stat][1];
                  if (!isset(${$overallColumn2 . 'Point'})) ${$overallColumn2 . 'Point'}  = 0;
                  // duels_won 은 컬럼2개 값으로 보므로
                  // 2,3점 항목을 맞췄어도 각각 1,2점의 가산점을 부여받음.
                  ${$overallColumn2 . 'Point'} += $draftSkill->$stat - 1;
                  ${$overallColumn . 'Point'} -= 1;
                }
              }
            }
          }
        }
      }
    }

    $columns = config('fantasyoverall.column');
    $categoryCntArr = array_count_values($columns);
    foreach ($columns as $stat => $category) {
      $base = $baseOverall->$stat;
      // if (is_null($baseOverall)) {
      //   $base = 45;
      // }

      if (!isset($overall[$category]['total'])) $overall[$category]['total'] = 0;
      if (!isset($updateArr[$category . '_overall'])) $updateArr[$category . '_overall']  = 0;
      if ($category !== SimulationCategoryType::PHYSICAL) {
        if (!isset(${$category . 'Point'})) ${$category . 'Point'} = 0;
        $tempStat = $stat;
        if (in_array($stat, SimulationCategoryType::getValues())) {
          $tempStat .= 'Stat';
        }
        if (!isset(${$tempStat . 'Point'})) ${$tempStat . 'Point'} = 0;

        $additonalPoint = $allPoint + ${$category . 'Point'} + ${$tempStat . 'Point'};

        $point = $base + $additonalPoint;
        if ($mine && $isWelcome) {
          $point = $this->applyPenalty($point);
        }
        $updateArr[$stat] = ['overall' => $point, 'add' => $additonalPoint];

        $overall[$category]['total'] += $point;
        $updateArr[$category . '_overall']  =  BigDecimal::of($overall[$category]['total'])->dividedBy(BigDecimal::of($categoryCntArr[$category]), 0, RoundingMode::HALF_UP)->toInt();
      } else {
        if ($mine && $isWelcome) {
          $base = $this->applyPenalty($base);
        }
        $physicalPoint = $allPoint;
        $updateArr[$stat] = ['overall' => $base + $physicalPoint, 'add' => $physicalPoint];
        $overall[$category]['total'] += $base + $physicalPoint;
        $updateArr[$category . '_overall'] =  BigDecimal::of($overall[$category]['total'])->dividedBy(BigDecimal::of($categoryCntArr[$category]), 0, RoundingMode::HALF_UP)->toInt();
      }
    }

    return $updateArr;
  }

  // 7.sub_position 찾기
  private function getFindPosition($_userPlateCard, $_baseOverall, &$updateArr)
  {
    // formaion_used 찾기
    $isFree = $_userPlateCard->is_free;
    if (!$isFree) {
      $updateArr['player_id'] = $_userPlateCard->draftSelection->player_id;

      $stat = $_userPlateCard->draftSelection->schedule->optaPlayerDailyStat->where('player_id', $updateArr['player_id'])->first()?->toArray();

      if (!is_null($stat) && !is_null($stat['formation_used'])) {
        $formationUsed = $stat['formation_used'];
      } else {
        if ($_userPlateCard->draftSelection->schedule->home_team_id === $_userPlateCard->draft_team_id) {
          $formationUsed = $_userPlateCard->draftSelection->schedule->home_formation_used;
        } else if ($_userPlateCard->draftSelection->schedule->away_team_id === $_userPlateCard->draft_team_id) {
          $formationUsed = $_userPlateCard->draftSelection->schedule->away_formation_used;
        }
      }

      //place_index 찾기
      if (!is_null($stat) && $stat['formation_place'] > 0 && !$isFree) {
        $updateArr['sub_position'] = config('formation-by-sub-position.formation_used')[$formationUsed][$stat['formation_place']];

        if ($_baseOverall->sub_position === $updateArr['sub_position']) {
          $updateArr['second_position'] = $_baseOverall->second_position;
          $updateArr['third_position'] = $_baseOverall->third_position;
        } else {
          $updateArr['second_position'] = $_baseOverall->sub_position;
          if ($_baseOverall->second_position === $updateArr['sub_position']) {
            $updateArr['third_position'] = $_baseOverall->third_position;
          } else if ($_baseOverall->third_position === $updateArr['sub_position']) {
            $updateArr['third_position'] = $_baseOverall->second_position;
          }
        }
      }
    } else {
      $updateArr['player_id'] = $_userPlateCard->plateCardWithTrashed->player_id;
    }

    if (!isset($updateArr['sub_position'])) {
      $updateArr['sub_position'] = $_baseOverall->sub_position;
      $updateArr['second_position'] = $_baseOverall->second_position;
      $updateArr['third_position'] = $_baseOverall->third_position;
    }
    return $updateArr;
  }

  // 6.final_overall 계산하기
  private function getFinalOverall(&$updateArr)
  {
    foreach (config('fantasyoverall.final') as $position => $stats) {
      $overall = 0;
      $minus = 0;
      if (isset($updateArr['second_position']) && $position === $updateArr['second_position']) $minus = config('fantasyoverall.sub_position')['second_position'];
      if (isset($updateArr['third_position']) && $position === $updateArr['third_position']) $minus = config('fantasyoverall.sub_position')['third_position'];
      foreach ($stats as $stat => $coefficient) {
        $overall = BigDecimal::of($overall)->plus(BigDecimal::of($updateArr[$stat]['overall'] + $minus)->multipliedBy(BigDecimal::of($coefficient), 1, RoundingMode::HALF_UP));
      }
      $overall = $overall->toScale(0, RoundingMode::HALF_UP);
      $finalOverall[$position] = $overall;
    }
    $updateArr['final_overall'] = $finalOverall;

    return $updateArr;
  }

  private function applyPenalty($point)
  {
    $penalties = config('fantasyoverall.overall_penalty');
    foreach ($penalties as $standard => $penalty) {
      if ($point >= $standard) {
        return $point + $penalty;
      }
    }
    return $point;
  }
  /* FANTASY OVERALL END  */
}
