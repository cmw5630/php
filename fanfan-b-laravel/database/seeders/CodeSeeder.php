<?php

namespace Database\Seeders;

use App\Models\Code;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CodeSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    Code::truncate();
    // if (Code::all()->isNotEmpty()) {
    //   return;
    // }

    $seedData = [
      [
        'category' => 'B01',
        'code' => null,
        'name' => 'banners',
        'description' => '배너 위치',
        'order_no' => null,
      ],
      [
        'category' => 'B01',
        'code' => '10',
        'name' => 'pc_main_984x412',
        'description' => null,
        'order_no' => 1,
      ],
      [
        'category' => 'B01',
        'code' => '11',
        'name' => 'pc_main_312x412',
        'description' => null,
        'order_no' => 2,
      ],
      [
        'category' => 'B01',
        'code' => '20',
        'name' => 'pc_stadium_312x477',
        'description' => null,
        'order_no' => 3,
      ],
      [
        'category' => 'B01',
        'code' => '21',
        'name' => 'pc_stadium_984x180',
        'description' => null,
        'order_no' => 4,
      ],
      [
        'category' => 'B01',
        'code' => '22',
        'name' => 'pc_stadium_312x180',
        'description' => null,
        'order_no' => 5
      ],
      [
        'category' => 'R01',
        'code' => null,
        'name' => 'restriction_reason',
        'description' => '제한 사유',
        'order_no' => null,
      ],
      [
        'category' => 'R01',
        'code' => '01',
        'name' => 'swear_word',
        'description' => '욕설',
        'order_no' => 1,
      ],
      [
        'category' => 'R01',
        'code' => '02',
        'name' => 'slander',
        'description' => '비방',
        'order_no' => 2,
      ],
      [
        'category' => 'R01',
        'code' => '03',
        'name' => 'false_information',
        'description' => '허위사실 유포',
        'order_no' => 3,
      ],
      [
        'category' => 'R01',
        'code' => '04',
        'name' => 'bad_name',
        'description' => '불건전한 닉네임',
        'order_no' => 4,
      ],
      [
        'category' => 'R01',
        'code' => '05',
        'name' => 'abusing',
        'description' => '어뷰징',
        'order_no' => 5,
      ],
      [
        'category' => 'R01',
        'code' => '00',
        'name' => 'etc',
        'description' => '기타',
        'order_no' => 6,
      ],
      [
        'category' => 'R02',
        'code' => null,
        'name' => 'restriction_period',
        'description' => '제한 기간',
        'order_no' => null,
      ],
      [
        'category' => 'R02',
        'code' => '7d',
        'name' => '7d',
        'description' => '7일',
        'order_no' => 1,
      ],
      [
        'category' => 'R02',
        'code' => '15d',
        'name' => '15d',
        'description' => '15일',
        'order_no' => 2,
      ],
      [
        'category' => 'R02',
        'code' => '30d',
        'name' => '30d',
        'description' => '30일',
        'order_no' => 3,
      ],
      [
        'category' => 'R02',
        'code' => '1y',
        'name' => '1y',
        'description' => '1y',
        'order_no' => 4,
      ],
      [
        'category' => 'R02',
        'code' => '00',
        'name' => 'forever',
        'description' => 'forever',
        'order_no' => 5,
      ],
      [
        'category' => 'W01',
        'code' => null,
        'name' => 'withdraw_reason',
        'description' => '탈퇴 사유',
        'order_no' => null,
      ],
      [
        'category' => 'W01',
        'code' => '01',
        'name' => 'The game is not fun',
        'description' => '게임이 재미 없어요.',
        'order_no' => 1,
      ],
      [
        'category' => 'W01',
        'code' => '02',
        'name' => 'The service is not good',
        'description' => '서비스가 구려요.',
        'order_no' => 2,
      ],
      [
        'category' => 'W01',
        'code' => '03',
        'name' => 'To prevent leakage of personal information',
        'description' => '개인정보가 소중해요.',
        'order_no' => 3,
      ],
      [
        'category' => 'W01',
        'code' => '04',
        'name' => 'Trying to change account',
        'description' => '새로 가입할래요.',
        'order_no' => 4,
      ],
      [
        'category' => 'W01',
        'code' => '05',
        'name' => 'Do not use an account',
        'description' => '그냥 탈퇴하고싶어요.',
        'order_no' => 5,
      ],
    ];

    Code::insert($seedData);
  }
}
