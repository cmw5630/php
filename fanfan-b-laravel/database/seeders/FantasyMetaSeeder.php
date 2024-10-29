<?php

namespace Database\Seeders;

use App\Console\Commands\BetRadarParsers\LeagueParser;
use App\Console\Commands\BetRadarParsers\LeagueSeasonParser;
use App\Console\Commands\BetRadarParsers\SeasonCompetitorParser;
use App\Console\Commands\BetRadarParsers\SeasonScheduleParser;
use App\Console\Commands\DataControll\CardCQuantileUpdator;
use App\Console\Commands\DataControll\FpCategoryAverageRefUpdator;
use App\Console\Commands\DataControll\GameLineupWarningUpdator;
use App\Console\Commands\DataControll\HeadToHeadPerPointUpdator;
use App\Console\Commands\DataControll\PlateCardPriceChangeUpdator;
use App\Console\Commands\DataControll\PlateCardUpdator;
use App\Console\Commands\DataControll\PlateCRefsUpdator;
use App\Console\Commands\DataControll\PlayerBaseProjectionUpdator;
use App\Console\Commands\DataControll\PlayerOverallUpdator;
use App\Console\Commands\DataControll\PlayerStrengthRefUpdator;
use App\Console\Commands\DataControll\PlayerUpdator;
use App\Console\Commands\DataControll\PointCQuantileUpdator;
use App\Console\Commands\DataControll\TeamMainFormationUpdator;
use App\Console\Commands\OptaParsers\MA1FixtureAndResultsParser;
use App\Console\Commands\OptaParsers\MA2MatchStatsParser;
use App\Console\Commands\OptaParsers\MA8MatchPreviews;
use App\Console\Commands\OptaParsers\OT2TournamentCalendarParser;
use App\Console\Commands\OptaParsers\OT3VenuesParser;
use App\Console\Commands\OptaParsers\PE2PlayerCareer;
use App\Console\Commands\OptaParsers\PE4SeasonRankingParser;
use App\Console\Commands\OptaParsers\PE7Injuries;
use App\Console\Commands\OptaParsers\PE8SuspensionParser;
use App\Console\Commands\OptaParsers\ServiceDataChecker;
use App\Console\Commands\OptaParsers\TM1ContestantsParser;
use App\Console\Commands\OptaParsers\TM2TeamStandings;
use App\Console\Commands\OptaParsers\TM3SquadsPlayersParser;
use App\Console\Commands\OptaParsers\TM4SeasonStats;
use App\Console\Commands\OptaParsers\TM7TransferParser;
use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\YesNo;
use App\Models\meta\FantasyMeta;
use Illuminate\Database\Seeder;

class FantasyMetaSeeder extends Seeder
{

  private function makeSyncGroups($_syncgroupName)
  {
    return [
      [
        'sync_order' => 1,
        'is_trigger' => YesNo::YES,
        'description' => 'leagues_seasons_parsing',
        'sync_feed_nick' => 'OT2',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => OT2TournamentCalendarParser::class,
      ],
      [
        'sync_order' => 2,
        'is_trigger' => YesNo::NO,
        'description' => 'teams_parsing',
        'sync_feed_nick' => 'TM1',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => TM1ContestantsParser::class,
      ],
      [
        'sync_order' => 3,
        'is_trigger' => YesNo::NO,
        'description' => 'schedules_parsing',
        'sync_feed_nick' => 'MA1_detailed',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => MA1FixtureAndResultsParser::class,
      ],
      [
        'sync_order' => 4,
        'is_trigger' => YesNo::NO,
        'description' => 'squads_parsing',
        'sync_feed_nick' => 'TM3',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => TM3SquadsPlayersParser::class,
      ],
      [
        'sync_order' => 5,
        'is_trigger' => YesNo::NO,
        'description' => 'players_updating',
        'sync_feed_nick' => 'PYU',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => PlayerUpdator::class,
      ],
      [
        'sync_order' => 6,
        'is_trigger' => YesNo::NO,
        'description' => 'service_data_checker',
        'sync_feed_nick' => 'SDC',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => ServiceDataChecker::class,
      ],
      [
        'sync_order' => 7,
        'is_trigger' => YesNo::NO,
        'description' => 'opta_player_daily_stats_parsing',
        'sync_feed_nick' => 'MA2',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => MA2MatchStatsParser::class,
      ],
      [
        'sync_order' => 8,
        'is_trigger' => YesNo::NO,
        'description' => 'plate_c_ref_and_quantile_ref_updating',
        'sync_feed_nick' => 'PLTCQU',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => PlateCRefsUpdator::class,
      ],
      [
        'sync_order' => 9,
        'is_trigger' => YesNo::NO,
        'description' => 'plate_cards_updating',
        'sync_feed_nick' => 'PCU',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => PlateCardUpdator::class,
      ],
      [
        'sync_order' => 10,
        'is_trigger' => YesNo::NO,
        'description' => 'game_lineup_waring_updator',
        'sync_feed_nick' => 'GLWU',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => GameLineupWarningUpdator::class,
      ],
      [
        'sync_order' => 11,
        'is_trigger' => YesNo::NO,
        'description' => 'pointc_quantile_updating',
        'sync_feed_nick' => 'PCQU',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => PointCQuantileUpdator::class,
      ],
      [
        'sync_order' => 12,
        'is_trigger' => YesNo::NO,
        'description' => 'cardc_quantile_updating',
        'sync_feed_nick' => 'CCQU',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => CardCQuantileUpdator::class,
      ],
      [
        'sync_order' => 13,
        'is_trigger' => YesNo::NO,
        'description' => 'team_standings',
        'sync_feed_nick' => 'TM2',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => TM2TeamStandings::class,
      ],
      [
        'sync_order' => 14,
        'is_trigger' => YesNo::NO,
        'description' => 'season_stats_parsing',
        'sync_feed_nick' => 'TM4',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => TM4SeasonStats::class,
      ],
      [
        'sync_order' => 15,
        'is_trigger' => YesNo::NO,
        'description' => 'season_rankings_parsing',
        'sync_feed_nick' => 'PE4',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => PE4SeasonRankingParser::class,
      ],
      [
        'sync_order' => 16,
        'is_trigger' => YesNo::NO,
        'description' => 'ref_avg_fps updator',
        'sync_feed_nick' => 'FPARU',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => FpCategoryAverageRefUpdator::class,
      ],
      [
        'sync_order' => 17,
        'is_trigger' => YesNo::NO,
        'description' => 'ref_player_base_projection_updator',
        'sync_feed_nick' => 'PBPU',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => PlayerBaseProjectionUpdator::class,
      ],
      [
        'sync_order' => 18,
        'is_trigger' => YesNo::NO,
        'description' => 'match_preview',
        'sync_feed_nick' => 'MA8',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => MA8MatchPreviews::class,
      ],
      [
        'sync_order' => 19,
        'is_trigger' => YesNo::NO,
        'description' => 'suspension',
        'sync_feed_nick' => 'PE8',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => PE8SuspensionParser::class,
      ],
      [
        'sync_order' => 20,
        'is_trigger' => YesNo::NO,
        'description' => 'betradar league',
        'sync_feed_nick' => 'BR_LG',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => LeagueParser::class,
      ],
      [
        'sync_order' => 21,
        'is_trigger' => YesNo::NO,
        'description' => 'betradar league_season',
        'sync_feed_nick' => 'BR_LS',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => LeagueSeasonParser::class,
      ],
      [
        'sync_order' => 22,
        'is_trigger' => YesNo::NO,
        'description' => 'betradar season team',
        'sync_feed_nick' => 'BR_SC',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => SeasonCompetitorParser::class,
      ],
      [
        'sync_order' => 23,
        'is_trigger' => YesNo::NO,
        'description' => 'betradar season schedule',
        'sync_feed_nick' => 'BR_SS',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => SeasonScheduleParser::class,
      ],
      [
        'sync_order' => 24,
        'is_trigger' => YesNo::NO,
        'description' => 'head to head per point updator',
        'sync_feed_nick' => 'HTHPPU',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => HeadToHeadPerPointUpdator::class,
      ],
      [
        'sync_order' => 25,
        'is_trigger' => YesNo::NO,
        'description' => 'injuries',
        'sync_feed_nick' => 'PE7',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => PE7Injuries::class,
      ],
      // [
      //   'sync_order' => 25,
      //   'is_trigger' => YesNo::NO,
      //   'description' => 'playercareer',
      //   'sync_feed_nick' => 'PE2',
      //   'sync_group' => $_syncgroupName,
      //   'extra_info' => null,
      //   'class_name' => PE2PlayerCareer::class,
      // ],
      [
        'sync_order' => 26,
        'is_trigger' => YesNo::NO,
        'description' => 'venue',
        'sync_feed_nick' => 'OT3',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => OT3VenuesParser::class,
      ],
      [
        'sync_order' => 27,
        'is_trigger' => YesNo::NO,
        'description' => 'team main formation updator',
        'sync_feed_nick' => 'TMFU',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => TeamMainFormationUpdator::class,
      ],
      [
        'sync_order' => 28,
        'is_trigger' => YesNo::NO,
        'description' => 'transfer',
        'sync_feed_nick' => 'TM7',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => TM7TransferParser::class,
      ],
      [
        'sync_order' => 29,
        'is_trigger' => YesNo::NO,
        'description' => 'player strength updator',
        'sync_feed_nick' => 'PSRU',
        'sync_group' => $_syncgroupName,
        'extra_info' => null,
        'class_name' => PlayerStrengthRefUpdator::class,
      ],
      // [
      //   'sync_order' => 30,
      //   'is_trigger' => YesNo::NO,
      //   'description' => 'platecard price change updator',
      //   'sync_feed_nick' => 'PCPCU',
      //   'sync_group' => $_syncgroupName,
      //   'extra_info' => null,
      //   'class_name' => PlateCardPriceChangeUpdator::class,
      // ],
      // [
      //   'sync_order' => 31,
      //   'is_trigger' => YesNo::NO,
      //   'description' => 'player overall updator',
      //   'sync_feed_nick' => 'POU',
      //   'sync_group' => $_syncgroupName,
      //   'extra_info' => null,
      //   'class_name' => PlayerOverallUpdator::class,
      // ],
    ];
  }

  private function makeExtraSyncgroups()
  {
    return [
      // elastic
      [
        'sync_order' => 1,
        'is_trigger' => YesNo::YES,
        'description' => 'team_standings',
        'sync_feed_nick' => 'TM2',
        'sync_group' => FantasySyncGroupType::ELASTIC,
        'extra_info' => null,
        'class_name' => TM2TeamStandings::class,
      ],
      [
        'sync_order' => 2,
        'is_trigger' => YesNo::NO,
        'description' => 'season_stats_parsing',
        'sync_feed_nick' => 'TM4',
        'sync_group' => FantasySyncGroupType::ELASTIC,
        'extra_info' => null,
        'class_name' => TM4SeasonStats::class,
      ],
      [
        'sync_order' => 3,
        'is_trigger' => YesNo::NO,
        'description' => 'season_ranking_parsing',
        'sync_feed_nick' => 'PE4',
        'sync_group' => FantasySyncGroupType::ELASTIC,
        'extra_info' => null,
        'class_name' => PE4SeasonRankingParser::class,
      ],
      // etc
      [
        'sync_order' => 1,
        'is_trigger' => YesNo::YES,
        'description' => 'playercareer',
        'sync_feed_nick' => 'PE2',
        'sync_group' => FantasySyncGroupType::ETC,
        'extra_info' => null,
        'class_name' => PE2PlayerCareer::class,
      ],
      // player
      [
        'sync_order' => 1,
        'is_trigger' => YesNo::YES,
        'description' => 'leagues_seasons_parsing',
        'sync_feed_nick' => 'OT2',
        'sync_group' => FantasySyncGroupType::CONDITIONALLY,
        'extra_info' => null,
        'class_name' => OT2TournamentCalendarParser::class,
      ],
      [
        'sync_order' => 2,
        'is_trigger' => YesNo::NO,
        'description' => 'teams_parsing',
        'sync_feed_nick' => 'TM1',
        'sync_group' => FantasySyncGroupType::CONDITIONALLY,
        'extra_info' => null,
        'class_name' => TM1ContestantsParser::class,
      ],
      [
        'sync_order' => 3,
        'is_trigger' => YesNo::NO,
        'description' => 'schedules_parsing',
        'sync_feed_nick' => 'MA1_detailed',
        'sync_group' => FantasySyncGroupType::CONDITIONALLY,
        'extra_info' => null,
        'class_name' => MA1FixtureAndResultsParser::class,
      ],
      [
        'sync_order' => 4,
        'is_trigger' => YesNo::NO,
        'description' => 'squads_parsing',
        'sync_feed_nick' => 'TM3',
        'sync_group' => FantasySyncGroupType::CONDITIONALLY,
        'extra_info' => null,
        'class_name' => TM3SquadsPlayersParser::class,
      ],
      [
        'sync_order' => 5,
        'is_trigger' => YesNo::NO,
        'description' => 'players_updating',
        'sync_feed_nick' => 'PYU',
        'sync_group' => FantasySyncGroupType::CONDITIONALLY,
        'extra_info' => null,
        'class_name' => PlayerUpdator::class,
      ],
      [
        'sync_order' => 6,
        'is_trigger' => YesNo::NO,
        'description' => 'service_data_checker',
        'sync_feed_nick' => 'SDC',
        'sync_group' => FantasySyncGroupType::CONDITIONALLY,
        'extra_info' => null,
        'class_name' => ServiceDataChecker::class,
      ],
      [
        'sync_order' => 7,
        'is_trigger' => YesNo::NO,
        'description' => 'opta_player_daily_stats_parsing',
        'sync_feed_nick' => 'MA2',
        'sync_group' => FantasySyncGroupType::CONDITIONALLY,
        'extra_info' => null,
        'class_name' => MA2MatchStatsParser::class,
      ],
      [
        'sync_order' => 8,
        'is_trigger' => YesNo::NO,
        'description' => 'plate_c_ref_and_quantile_ref_updating',
        'sync_feed_nick' => 'PLTCQU',
        'sync_group' => FantasySyncGroupType::CONDITIONALLY,
        'extra_info' => null,
        'class_name' => PlateCRefsUpdator::class,
      ],
      [
        'sync_order' => 9,
        'is_trigger' => YesNo::NO,
        'description' => 'plate_cards_updating',
        'sync_feed_nick' => 'PCU',
        'sync_group' =>  FantasySyncGroupType::CONDITIONALLY,
        'extra_info' => null,
        'class_name' => PlateCardUpdator::class,
      ],
      [
        'sync_order' => 10,
        'is_trigger' => YesNo::NO,
        'description' => 'game_lineup_waring_updator',
        'sync_feed_nick' => 'GLWU',
        'sync_group' => FantasySyncGroupType::CONDITIONALLY,
        'extra_info' => null,
        'class_name' => GameLineupWarningUpdator::class,
      ],
    ];
  }

  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    // if (FantasyMeta::all()->isNotEmpty()) {
    //   return;
    // }

    $seedData = [];

    foreach ([FantasySyncGroupType::DAILY, FantasySyncGroupType::ALL] as $syncGroupName) {
      $seedData = array_merge($seedData, $this->makeSyncGroups($syncGroupName));
    }
    $seedData = array_merge($seedData, $this->makeExtraSyncgroups());

    foreach ($seedData as $row) {
      FantasyMeta::updateOrCreateEx([
        'sync_group' => $row['sync_group'],
        'sync_feed_nick' => $row['sync_feed_nick']
      ], $row);
    }
  }
}
