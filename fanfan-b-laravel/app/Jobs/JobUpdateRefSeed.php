<?php

namespace App\Jobs;

use App\Models\game\PlateCard;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Str;

class JobUpdateRefSeed implements ShouldQueue
{
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

  public $timeout = 500;

  private $plateGradePriceTable;
  private $conditionMap;
  private $sheets;

  /**
   * Create a new job instance.
   *
   * @return void
   */
  public function __construct($_plateGradePriceTable, $_conditionMap, $_sheets)
  {
    //
    $this->onConnection('redis')->afterCommit();
    $this->plateGradePriceTable = $_plateGradePriceTable;
    $this->conditionMap = $_conditionMap;
    $this->sheets = $_sheets;
  }

  /**
   * Execute the job.
   *
   * @return void
   */
  public function handle()
  {
    try {
      DB::beginTransaction();
      foreach ($this->sheets as $model => $datas) {
        $columnNames = [];
        if ($datas === null) continue;
        foreach ($datas as $idx => $oneData) {
          $row = [];
          if ($idx === 0) {
            $columnNames = $oneData;
            continue;
          }
          foreach ($columnNames as $idx => $col) {
            $col = Str::snake($col);
            $col = Str::replace(' ', '', $col);
            if ($col === null || $col === '') {
              continue;
            } else if ($col === 'price_init_season_id') {
              $row['season_id'] = $oneData[$idx];
              $row['price_init_season_id'] = $oneData[$idx];
            } else {
              $row[$col] = $oneData[$idx];
            }
          }

          $condition = [];
          foreach ($this->conditionMap[$model] as $conditionCol) {
            if ($conditionCol === 'price_init_season_id') {
              $condition['season_id'] = $row[$conditionCol];
            } else {
              $condition[$conditionCol] = $row[$conditionCol];
            }
          }
          // plate card price 초기화 >>
          if ($model === PlateCard::class) {
            $originInstance = $model::where($condition);
            if (!$originInstance->clone()->exists()) { // 존재하지 않는 카드
              logger('excel input missing:' . json_encode($row));
              continue;
            }

            $instance = $originInstance->isPriceSet(false)->first(); // price 초기화 안된 카드만
            if ($instance) {
              // price 계산
              foreach ($this->plateGradePriceTable as $values) {
                if ($values['grade'] === Str::lower($row['grade'])) {
                  $instance->price = $values['price'];
                }
              }

              foreach ($row as $col => $value) {
                if ($col === 'plate_c') {
                  $value = eval(Str::replace('=', '', $value) . ';');
                }
                $instance->{$col} = $value;
                if ($col === 'grade') {
                  $instance->init_grade = Str::lower($value);
                }
              }
              $instance->save();
            }
          }
          // << price card 초기화
          // ref tables 초기화 >>
          else {
            $model::updateOrCreateEx(
              $condition,
              $row,
            );
          }
          // << ref tables 초기화
        }
      }
      DB::commit();
    } catch (\Exception $e) {
      logger($e);
      logger('카드 가격 초기화 실패');
      DB::rollBack();
    }
  }
}
