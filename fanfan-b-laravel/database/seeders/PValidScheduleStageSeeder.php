<?php

namespace Database\Seeders;

use App\Models\preset\PValidScheduleStage;
use Illuminate\Database\Seeder;
use Schema;

class PValidScheduleStageSeeder extends Seeder
{
  // /**
  //  * Run the database seeds.
  //  *
  //  * @return void
  //  */
  public function run()
  {
    if (PValidScheduleStage::all()->isNotEmpty()) {
      return;
    }

    $seedData = [
      ['league_id' => '2kwbbcootiqqgmrzs6o5inle5', 'country' => 'England', 'stage_format_id' => 'e2q01r9o9jwiq1fls93d1sslx', 'stage_name' => 'Regular Season', 'week' => '1~38', 'match_count' => null, 'match_count' => null,],
      ['league_id' => '1r097lpxe0xn03ihb7wi98kao', 'country' => 'Italy', 'stage_format_id' => 'e2q01r9o9jwiq1fls93d1sslx', 'stage_name' => 'Regular Season', 'week' => '1~38', 'match_count' => null, 'match_count' => null,],
      ['league_id' => '34pl8szyvrbwcmfkuocjm3r6t', 'country' => 'Spain', 'stage_format_id' => 'e2q01r9o9jwiq1fls93d1sslx', 'stage_name' => 'Regular Season', 'week' => '1~38', 'match_count' => null, 'match_count' => null,],
      ['league_id' => 'dm5ka0os1e3dxcp3vh05kmp33', 'country' => 'France', 'stage_format_id' => 'e2q01r9o9jwiq1fls93d1sslx', 'stage_name' => 'Regular Season', 'week' => '1~38', 'match_count' => null, 'match_count' => null,],
      ['league_id' => 'scf9p4y91yjvqvg5jndxzhxj', 'country' => 'Brazil', 'stage_format_id' => 'e2q01r9o9jwiq1fls93d1sslx', 'stage_name' => 'Regular Season', 'week' => '1~38', 'match_count' => null, 'match_count' => null,],
      ['league_id' => '8yi6ejjd1zudcqtbn07haahg6', 'country' => 'Portugal', 'stage_format_id' => 'e2q01r9o9jwiq1fls93d1sslx', 'stage_name' => 'Regular Season', 'week' => '1~34', 'match_count' => null, 'match_count' => null,],
      ['league_id' => '8o5tv5viv4hy1qg9jp94k7ayb', 'country' => 'Japan', 'stage_format_id' => 'e2q01r9o9jwiq1fls93d1sslx', 'stage_name' => 'Regular Season', 'week' => '1~34', 'match_count' => null, 'match_count' => null,],

      ['league_id' => '4zwgbb66rif2spcoeeol2motx', 'country' => 'Belgium', 'stage_format_id' => 'e2q01r9o9jwiq1fls93d1sslx', 'stage_name' => 'Regular Season', 'week' => '1~34', 'match_count' => null, 'match_count' => null,],
      ['league_id' => '4zwgbb66rif2spcoeeol2motx', 'country' => 'Belgium', 'stage_format_id' => 'c7zluzt2rr4oxoc7xslnnfzkl', 'stage_name' => 'Championship Round', 'week' => '1~6', 'match_count' => null, 'match_count' => null,],
      ['league_id' => '4zwgbb66rif2spcoeeol2motx', 'country' => 'Belgium', 'stage_format_id' => '4jxhq624zvhatdsps42lmac5w', 'stage_name' => 'Conference League Play-off Group', 'week' => '1~6', 'match_count' => null, 'match_count' => null,],
      ['league_id' => '4zwgbb66rif2spcoeeol2motx', 'country' => 'Belgium', 'stage_format_id' => '1kjuqze1cbbvh4chobalzp6vo', 'stage_name' => 'Conference League Play-offs - Final', 'week' => null, 'match_count' => null, 'match_count' => 1,],

      ['league_id' => '5c96g1zm7vo5ons9c42uy2w3r', 'country' => 'Austria', 'stage_format_id' => 'ez2c68hjfw70wvau9svn16g9h', 'stage_name' => 'Regular Season', 'week' => '1~22', 'match_count' => null, 'match_count' => null,],
      ['league_id' => '5c96g1zm7vo5ons9c42uy2w3r', 'country' => 'Austria', 'stage_format_id' => 'c7zluzt2rr4oxoc7xslnnfzkl', 'stage_name' => 'Championship Round', 'week' => '23~32', 'match_count' => null, 'match_count' => null,],
      ['league_id' => '5c96g1zm7vo5ons9c42uy2w3r', 'country' => 'Austria', 'stage_format_id' => 'ez2c68hjfw70wvau9svn16g9h', 'stage_name' => 'Relegation Round', 'week' => '23~32', 'match_count' => null, 'match_count' => null,],
      ['league_id' => '5c96g1zm7vo5ons9c42uy2w3r', 'country' => 'Austria', 'stage_format_id' => 'bt983ymnchxlqgk51ybjux2qc', 'stage_name' => 'Conference League Play-offs - 1st Round', 'week' => null, 'match_count' => null, 'match_count' => 1,],
      ['league_id' => '5c96g1zm7vo5ons9c42uy2w3r', 'country' => 'Austria', 'stage_format_id' => '1kjuqze1cbbvh4chobalzp6vo', 'stage_name' => 'Conference League Play-offs - Final', 'week' => null, 'match_count' => null, 'match_count' => 2,],

      ['league_id' => 'akmkihra9ruad09ljapsm84b3', 'country' => 'Netherlands', 'stage_format_id' => 'e2q01r9o9jwiq1fls93d1sslx', 'stage_name' => 'Regular Season', 'week' => '1~34', 'match_count' => null, 'match_count' => null,],
      ['league_id' => 'akmkihra9ruad09ljapsm84b3', 'country' => 'Netherlands', 'stage_format_id' => 'bbmsupci1pvpl0vdz67707oph', 'stage_name' => 'Europa League Play-offs - Semi-finals', 'week' => null, 'match_count' => null, 'match_count' => 4,],
      ['league_id' => 'akmkihra9ruad09ljapsm84b3', 'country' => 'Netherlands', 'stage_format_id' => '97oc8cwhmyiclavibe87c6tw5', 'stage_name' => 'Europa League Play-offs - Final', 'week' => null, 'match_count' => null, 'match_count' => 2,],

      ['league_id' => 'avs3xposm3t9x1x2vzsoxzcbu', 'country' => 'Korea', 'stage_format_id' => 'e2q01r9o9jwiq1fls93d1sslx', 'stage_name' => 'Regular Season', 'week' => '1~33', 'match_count' => null, 'match_count' => null,],
      ['league_id' => 'avs3xposm3t9x1x2vzsoxzcbu', 'country' => 'Korea', 'stage_format_id' => 'c7zluzt2rr4oxoc7xslnnfzkl', 'stage_name' => 'Championship Round', 'week' => '34~38', 'match_count' => null, 'match_count' => null,],
      ['league_id' => 'avs3xposm3t9x1x2vzsoxzcbu', 'country' => 'Korea', 'stage_format_id' => 'ez2c68hjfw70wvau9svn16g9h', 'stage_name' => 'Relegation Round', 'week' => '34~38', 'match_count' => null, 'match_count' => null,],

      ['league_id' => '287tckirbfj9nb8ar2k9r60vn', 'country' => 'USA', 'stage_format_id' => 'e2q01r9o9jwiq1fls93d1sslx', 'stage_name' => 'Regular Season', 'week' => '1~34', 'match_count' => null, 'match_count' => 6,],
      ['league_id' => '287tckirbfj9nb8ar2k9r60vn', 'country' => 'USA', 'stage_format_id' => 'c2n2yhd8c8gu5rrwja5wtzfh0', 'stage_name' => 'MLS Cup - Round 1', 'week' => null, 'match_count' => null, 'match_count' => 4,],
      ['league_id' => '287tckirbfj9nb8ar2k9r60vn', 'country' => 'USA', 'stage_format_id' => 'dv9jby3jql818r8kducyebbh1', 'stage_name' => 'MLS Cup - Conference Semi-finals', 'week' => null, 'match_count' => null, 'match_count' => 2,],
      ['league_id' => '287tckirbfj9nb8ar2k9r60vn', 'country' => 'USA', 'stage_format_id' => 'dv5hsowu8690i31rc76lycjpx', 'stage_name' => 'MLS Cup - Conference Finals', 'week' => null, 'match_count' => null, 'match_count' => 1,],
      ['league_id' => '287tckirbfj9nb8ar2k9r60vn', 'country' => 'USA', 'stage_format_id' => 'dvcj5e2iiwivkst8jnlyxwq1h', 'stage_name' => 'MLS Cup - Final', 'week' => null, 'match_count' => null, 'match_count' => null,],

      ['league_id' => 'xwnjb1az11zffwty3m6vn8y6', 'country' => 'Australia', 'stage_format_id' => 'e2q01r9o9jwiq1fls93d1sslx', 'stage_name' => 'Regular Season', 'week' => '1~26', 'match_count' => null, 'match_count' => null,],
      ['league_id' => 'xwnjb1az11zffwty3m6vn8y6', 'country' => 'Australia', 'stage_format_id' => '2hrcobqge36fgp0q3wmc26f9x', 'stage_name' => 'Semi-finals', 'week' => null, 'match_count' => null, 'match_count' => 4,],
      ['league_id' => 'xwnjb1az11zffwty3m6vn8y6', 'country' => 'Australia', 'stage_format_id' => '73dwoyw1fl85mb1a68xtezqvp', 'stage_name' => 'Elimination Finals', 'week' => null, 'match_count' => null, 'match_count' => 2,],
      ['league_id' => 'xwnjb1az11zffwty3m6vn8y6', 'country' => 'Australia', 'stage_format_id' => 'b091ztjgcplicsiws3fj38mvp', 'stage_name' => 'Grand Final', 'week' => null, 'match_count' => null, 'match_count' => 1,],
    ];

    foreach ($seedData as $row) {
      Schema::connection('preset')->disableForeignKeyConstraints();
      PValidScheduleStage::updateOrCreateEx(
        [
          'league_id' => $row['league_id'],
          'stage_format_id' => $row['stage_format_id'],
        ],
        $row
      );
      Schema::connection('preset')->enableForeignKeyConstraints();
    }
  }
}
