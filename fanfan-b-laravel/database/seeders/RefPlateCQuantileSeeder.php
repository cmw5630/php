<?php

namespace Database\Seeders;

use App\Models\meta\RefPlateCQuantile;
use Illuminate\Database\Seeder;
use Schema;

class RefPlateCQuantileSeeder extends Seeder
{
  // /**
  //  * Run the database seeds.
  //  *
  //  * @return void
  //  */
  // 주의: 선수의 plate_c가 있어야 만들 수 있음. plate_c는 MA2 정보가 있어야 만들 수 있음.
  // 따라서 최초에는 Seed 파일이 있어야한다.
  // 새로운 시즌이 시작되기 전 새 시즌에 적용할 수 있도록 업데이트 되어야함 - 이 작업은 서비스가 중지되지 않으면 매일 daily(PLTCQU(sync_feed_nick))로 돌아가므로 자동으로 만들어진다.
  public function run()
  {
    if (RefPlateCQuantile::all()->isNotEmpty()) {
      return;
    }
    $seedData = [
      ['league_id' => '4zwgbb66rif2spcoeeol2motx', 'source_season_id' => '1qtpbdbeudho5i7fu5z2lp2j8', 'price_init_season_id' =>  '5wj8l9y4s3484k6puwup793pw', 'quantile_ss' =>  '999', 'quantile_s' => 20.23, 'quantile_a' =>  15.33, 'quantile_b' =>  10.8, 'quantile_c' =>  6.32, 'quantile_d' =>  1.2,],
      ['league_id' => '5c96g1zm7vo5ons9c42uy2w3r', 'source_season_id' => '29p4usgcyyx23x1gjpiksz310', 'price_init_season_id' =>  'b0r2dzuwepenhesbl5yrnhr10', 'quantile_ss' =>  '999', 'quantile_s' => 18.91, 'quantile_a' =>  15.03, 'quantile_b' =>  11.34, 'quantile_c' =>  7.3, 'quantile_d' =>  1.88],
      ['league_id' => '1r097lpxe0xn03ihb7wi98kao', 'source_season_id' => '5ari28rriavjv6rdzsmqlomqc', 'price_init_season_id' =>  '917vbkpe5rs8mnwskj4jz7ys', 'quantile_ss' =>  '999', 'quantile_s' => 18.93, 'quantile_a' =>  13.75, 'quantile_b' =>  9.03, 'quantile_c' =>  3.34, 'quantile_d' =>  -1.58],
      ['league_id' => 'xwnjb1az11zffwty3m6vn8y6', 'source_season_id' => '7ow2q4agyqskzj6ycukwmhtlg', 'price_init_season_id' =>  '300ig4lfofmkh3u971h34pbf8', 'quantile_ss' =>  '999', 'quantile_s' => 19.14, 'quantile_a' =>  15.04, 'quantile_b' =>  11.02, 'quantile_c' =>  6.76, 'quantile_d' =>  1.76],
      ['league_id' => '8yi6ejjd1zudcqtbn07haahg6', 'source_season_id' => '87typal84j1zls3ushwjsox78', 'price_init_season_id' =>  '6fdb784d9srg4anqx5ylvagpg', 'quantile_ss' =>  '999', 'quantile_s' => 19.56, 'quantile_a' =>  14.04, 'quantile_b' =>  9.7, 'quantile_c' =>  5.05, 'quantile_d' =>  0.64],
      ['league_id' => '2kwbbcootiqqgmrzs6o5inle5', 'source_season_id' => '8l3o9v8n8tva0bb2cds2dhatw', 'price_init_season_id' =>  '80foo89mm28qjvyhjzlpwj28k', 'quantile_ss' =>  '999', 'quantile_s' => 19.84, 'quantile_a' =>  15.1, 'quantile_b' =>  10.61, 'quantile_c' =>  5.01, 'quantile_d' =>  -1.32],
      ['league_id' => '581t4mywybx21wcpmpykhyzr3', 'source_season_id' => 'aefcj9288gsf8elnhzleg9dsk', 'price_init_season_id' =>  '5jc60w3y7cps00lavutor9zbo', 'quantile_ss' =>  '999', 'quantile_s' => 18.2, 'quantile_a' =>  13.39, 'quantile_b' =>  8.99, 'quantile_c' =>  3.79, 'quantile_d' =>  -0.7],
      ['league_id' => 'dm5ka0os1e3dxcp3vh05kmp33', 'source_season_id' => 'buq1p8cf83xr4f0fygagjxn2s', 'price_init_season_id' =>  'b5rz3ukb6kvo9m5neiptb0avo', 'quantile_ss' =>  '999', 'quantile_s' => 19.45, 'quantile_a' =>  14.35, 'quantile_b' =>  9.69, 'quantile_c' =>  4.77, 'quantile_d' =>  -0.59],
      ['league_id' => '34pl8szyvrbwcmfkuocjm3r6t', 'source_season_id' => 'c553vrpm9l1rpcdsia72i4no', 'price_init_season_id' =>  'e4idaotcivcpu4rqyvrwbciz8', 'quantile_ss' =>  '999', 'quantile_s' => 17.53, 'quantile_a' =>  13.65, 'quantile_b' =>  9.16, 'quantile_c' =>  4.1, 'quantile_d' =>  -1.13],
      ['league_id' => 'scf9p4y91yjvqvg5jndxzhxj', 'source_season_id' => 'css9eoc46vca8gkmv5z7603ys', 'price_init_season_id' =>  'czjx4rda7swlzql5d1cq90r8', 'quantile_ss' =>  '999', 'quantile_s' => 18.74, 'quantile_a' =>  13.04, 'quantile_b' =>  8.46, 'quantile_c' =>  3.8, 'quantile_d' =>  -0.01],
      ['league_id' => 'akmkihra9ruad09ljapsm84b3', 'source_season_id' => 'dp0vwa5cfgx2e733gg98gfhg4', 'price_init_season_id' =>  'd1k1pqdg2yvw8e8my74yvrdw4', 'quantile_ss' =>  '999', 'quantile_s' => 20.87, 'quantile_a' =>  15.32, 'quantile_b' =>  10.88, 'quantile_c' =>  5.18, 'quantile_d' =>  -0.64],
      ['league_id' => 'avs3xposm3t9x1x2vzsoxzcbu', 'source_season_id' => 'e2ew613e8b7f17kok5rz1mac', 'price_init_season_id' =>  '4ebkg0znzhb9yxq6yr7ke2rkk', 'quantile_ss' =>  '999', 'quantile_s' => 18.06, 'quantile_a' =>  13.82, 'quantile_b' =>  10.19, 'quantile_c' =>  6.47, 'quantile_d' =>  2.25],
      ['league_id' => '4oogyu6o156iphvdvphwpck10', 'source_season_id' => '1sc9sn2keddyalfj2z0wy77ys', 'price_init_season_id' =>  '8t8ofk94zy6ksx1spnfecvpck', 'quantile_ss' =>  '999', 'quantile_s' => 20.28, 'quantile_a' =>  13.12, 'quantile_b' =>  8.8, 'quantile_c' =>  4.13, 'quantile_d' =>  -0.31],
    ];

    Schema::connection('api')->disableForeignKeyConstraints();
    foreach ($seedData as $idx => $row) {
      RefPlateCQuantile::updateOrCreateEx([
        'league_id' => $row['league_id'],
        'source_season_id' => $row['source_season_id'],
        'price_init_season_id' => $row['price_init_season_id'],
      ], $row);
    }
    Schema::connection('api')->enableForeignKeyConstraints();
  }
}
