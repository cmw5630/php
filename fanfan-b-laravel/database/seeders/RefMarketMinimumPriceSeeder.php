<?php

namespace Database\Seeders;

use App\Enums\Opta\Card\CardGrade;
use App\Models\meta\RefMarketExpireReduction;
use App\Models\meta\RefMarketMinimumPrice;
use DB;
use Illuminate\Database\Seeder;
use Throwable;

class RefMarketMinimumPriceSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $basicPrice = [
      CardGrade::GOAT =>        60000000,
      CardGrade::FANTASY =>     45000000,
      CardGrade::ELITE =>       35000000,
      CardGrade::AMAZING =>     25000000,
      CardGrade::DECENT =>      15000000,
      CardGrade::NORMAL =>       5000000,
    ];

    // 각 레벨 순서는 단일 / 조합
    $weightTable = [
      1 => [1, 1],
      2 => [2, 2],
      3 => [4, 4],
      4 => [5, 5],
      5 => [6.2, 6],
      6 => [7.9, 7.3],
      7 => [10, 8.7],
      8 => [12.6, 10.2],
      9 => [15.8, 12.2],
    ];

    DB::beginTransaction();
    try {
      foreach ($weightTable as $level => $weight) {
        foreach ($basicPrice as $grade => $price) {
          for ($i = 0; $i < 2; $i++) {
            if (isset($weight[$i])) {
              $sum = round((double) bcmul($price, $weight[$i]), -3);
              $draftType = ['single', 'combined'];
              RefMarketMinimumPrice::updateOrCreateEx([
                'card_grade' => $grade,
                'draft_level' => $level,
                'draft_type' => $draftType[$i],
              ], [
                'min_gold' => $sum,
              ]);
            }
          }
        }
      }

      // 저감 비율

      // 각 레벨 순서는 단일 / 조합
      $reductionTable = [
        [1, [24, 48, 72]],
        [0.7, [24, 48]],
        [0.49, [24]],
        [0.34, [24]],
        [0.24, [24]],
        [0.17, [24]],
      ];

      for ($i = 0; $i < 6; $i++) {
        RefMarketExpireReduction::updateOrCreateEx([
          'expired_count' => $i,
        ], [
          'reduction_rate' => $reductionTable[$i][0],
          'period_options' => $reductionTable[$i][1],
        ]);
      }
      DB::commit();
    } catch (Throwable $th) {
      DB::rollback();
      dd($th);
    }
  }
}
