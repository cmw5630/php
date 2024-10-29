<?php

namespace App\Console\Commands;

use App\Console\Commands\BetRadarParsers\CompetitorProfileParser;
use App\Console\Commands\BetRadarParsers\LeagueParser;
use App\Console\Commands\BetRadarParsers\LeagueSeasonParser;
use App\Console\Commands\BetRadarParsers\SeasonScheduleParser;
use App\Console\Commands\DataControll\DeprecatedFPQuantileUpdator;
use App\Console\Commands\DataControll\GameJoinUpdator;
use App\Console\Commands\DataControll\GaRoundUpdator;
use App\Console\Commands\DataControll\Live\LiveGameChecker;
use App\Console\Commands\DataControll\Live\LiveMA2MatchStatsParser as LiveLiveMA2MatchStatsParser;
use App\Console\Commands\DataControll\PlateCardPrice;
use App\Console\Commands\DataControll\PlateCardPriceChangeUpdator;
use App\Console\Commands\DataControll\PlateCardUpdator;
use App\Console\Commands\DataControll\PlateCRefsUpdator;
use App\Console\Commands\DataControll\PlayerCurrentMetaRef;
use App\Console\Commands\DataControll\PlayerStrengthRefUpdator;
use App\Console\Commands\DataControll\PlayerUpdator;
use App\Console\Commands\DataControll\PointCQuantileUpdator;
use App\Console\Commands\OptaParsers\LiveMA2MatchStatsParser;
use App\Console\Commands\OptaParsers\OT2TournamentCalendarParser;
use App\Console\Commands\OptaParsers\MA1FixtureAndResultsParser;
use App\Console\Commands\OptaParsers\MA2MatchStatsParser;
use App\Console\Commands\OptaParsers\MA6CommentaryParser;
use App\Console\Commands\OptaParsers\MA8MatchPreviews;
use App\Console\Commands\OptaParsers\PE4SeasonRankingParser;
use App\Console\Commands\OptaParsers\TM1ContestantsParser;
use App\Console\Commands\OptaParsers\TM3SquadsPlayersParser;
use App\Console\Commands\OptaParsers\TM4SeasonStats;
use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\FantasyMeta\FantasySyncGroupType;
use App\Enums\Opta\Card\OriginGrade;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\Opta\Season\SeasonNameType;
use App\Enums\Opta\Season\SeasonWhenType;
use App\Enums\Simulation\SimulationTeamSide;
use App\Enums\SimulationCalculator\SimulationCalculatorType;
use App\Enums\System\SocketChannelType;
use App\Events\LiveStatsPublishEvent;
use App\Events\ScheduleSocketEvent;
use App\Events\SimulationSocketEvent;
use App\Libraries\Classes\FantasyCalculator;
use App\Libraries\Classes\SimulationCalculator;
use App\Libraries\Traits\GameTrait;
use App\Libraries\Traits\SimulationTrait;
use App\Models\data\BrSeason;
use App\Models\data\Commentary;
use App\Models\data\League;
use App\Models\data\OptaPlayerDailyStat;
use App\Models\data\Schedule;
use App\Models\data\Season;
use App\Models\game\Game;
use App\Models\game\GameLineup;
use App\Models\game\GamePossibleSchedule;
use App\Models\game\GameSchedule;
use App\Models\game\PlateCard;
use App\Models\game\PlayerDailyStat;
use App\Models\meta\FantasyMeta;
use App\Models\meta\RefPlateCQuantile;
use App\Models\meta\RefPowerRankingQuantile;
use App\Models\sequenceMeta;
use App\Models\simulation\RefSimulationScenario;
use App\Models\simulation\RefSimulationSequence;
use App\Models\simulation\SimulationSchedule;
use App\Models\simulation\SimulationSequenceMeta;
use App\Models\simulation\SimulationStep;
use App\Models\user\UserPlateCard;
use Carbon\Carbon;
use Exception;
use Fiber;
use Illuminate\Console\Command;
use LogEx;
use Str;

use function PHPUnit\Framework\isNull;

class PullOptaDatas extends Command
{
  use SimulationTrait, GameTrait;
  /**
   * The name and signature of the console command.
   *
   * @var string
   */

  protected $signature = "pullopta {feedNicks?*} {--list} {--mode=} {--act=} {--force}";

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Command description';

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    $param = [
      'mode' => $this->options()['mode'] ?? 'all', // all/daily/live
    ];

    $act = false;
    if (!empty($this->options()['act'])) {
      if ($this->options()['act'] === 'true') {
        $act = true;
      } else if ($this->options()['act'] === 'false') {
        $act = false;
      } else {
        dd("--act= 의 값은 true 또는 false만 가능");
      }
    }

    $feedNickMap = [];
    $parsersInfo = FantasyMeta::where(['sync_group' => 'all',])
      ->orderBy('sync_order')
      ->get(['sync_feed_nick', 'class_name'])
      ->flatMap(function ($parserItem) use (&$feedNickMap) {
        $feedNickMap[$parserItem['class_name']] =  $parserItem['sync_feed_nick'];
      });

    if ($this->option()['list']) {
      dd($feedNickMap);
    }

    if (!empty($this->arguments()['feedNicks'])) {
      $feedNicks = $this->argument()['feedNicks'];

      foreach ($feedNicks as $idx => $nick) {
        if (!in_array($nick, array_values($feedNickMap))) {
          dd($nick . ' ' .  'is wrong FeedNick');
        }
      }

      $parsersInfo = FantasyMeta::where(['sync_group' => 'all',])
        ->whereIn('sync_feed_nick', $feedNicks)
        ->orderBy('sync_order')
        ->get(['class_name'])
        ->toArray();
      foreach ($parsersInfo as $idx => $info) {
        // $classPath = $info['class_path'];
        $class = $info['class_name'];
        (new $class)->setParams($param)->start($act);
      }
    } else {


      dd(SimulationSchedule::where('id', 'sc04ff356c02b741628361aa065220f808')->whereDoesntHave('sequenceMeta', function ($query) {
        $query->where('is_checked', false);
      })->exists());


      $data = [
        'type' => SocketChannelType::SEQUENCE,
        'schedule_id' => 'sc5a3baa2e46214abbaf29b72eecde4090',  // $this->getScheduleId($_response),
        'round' => 1,
        'target_queue' => 'sim_seq',
        'sequences' => [],
        'server_time' => null,
      ];

      SimulationSchedule::getPlaying()
        // ->with(
        //   [
        //     'lineupMeta' => function ($query) {
        //       $query->with('lineup');
        //     },
        //   ],
        // )
        ->with(['sequenceMeta' => function ($query) {
          $query->with('step')->where(['is_checked' => false,])->limit(3);
        }])->get()->map(function ($item) use (&$data) {
          $item->sequenceMeta->map(function ($seq) use (&$data) {
            $serverNow = Carbon::now()->toDateTimeString();
            $data['server_time'] = $serverNow;
            $gameStartTime = Carbon::now()->subMinutes(3);
            $sequenceStartOffset =  Carbon::parse($seq['time_sum'])->diffInSeconds($seq['time_taken']);
            $sequenceStartTime = Carbon::parse($gameStartTime)->addSeconds($sequenceStartOffset);
            if (Carbon::parse($serverNow)->diffInSeconds($sequenceStartTime) >= 40) {
              $data['sequences'][] = $seq->toArray();
              $seq->is_checked = true;
              $seq->save();
            }
          });
          broadcast(new SimulationSocketEvent($data));
        });


      dd('xkjlsd');

      (new GaRoundUpdator())->update();

      dd(array_merge([null], ['a', 'b'], []));
      dd('xkjldsd');


      dd(Game::withWhereHas('gameSchedule', function ($query) {
        $query->withoutGlobalScopes()->with('gamePossibleSchedule'); // 모든 경기가 취소나 연기되어되어 deleted된 경우에도 game 종료 처리를 위해 로직을 타도록 한다.
      })->get()->toArray());


      dd(GameSchedule::where('schedule_id', 'cyz7q1kcid5cpsudgkel2fpqs')->get('game_id')->toArray());
      (new LiveGameChecker())->start();


      dd(GameLineup::isThereGameActivated(42331, null));


      dd(UserPlateCard::whereId(42331)->first()->toArray()['lock_status']);



      (new MA1FixtureAndResultsParser)->setParams(['mode' => 'daily'])->start(true);
      dd('xkjdlsj');


      (new PlayerStrengthRefUpdator)->start();
      dd('xkdj');

      OptaPlayerDailyStat::gameParticipantPlayer()->groupBy(['season_id', 'player_id'])->get();



      $schedulePubSet = [
        'type' => SocketChannelType::SCHEDULE,
        'schedule_id' => 'ct7zwx60e5vsp1lukg1efip04',
        'target_queue' => 'live_one',
        'round' => 11,
        'data_updated' => [
          'status' => 'Played',
          'match_length_sec' => 20,
          'period_id' => 14,
          'score_home' => 17778,
          'score_away' => 17779,
          'winner' => 'away',
          'match_length_min' => 111,
        ]
      ];
      broadcast(new ScheduleSocketEvent($schedulePubSet));
      dd("xyz");

      (new SeasonScheduleParser)->setParams(['mode' => 'all'])->start(false);

      dd(BrSeason::withWhereHas('optaSeason', function ($query) {
        return $query->currentSeasons();
      })->get()->pluck('season_id')->toArray());



      dd(BrSeason::get()->pluck('id')->toArray());
      dd('xx')(new LeagueSeasonParser)->setParams(['mode' => 'all'])->start(true);
      dd("yy");
      (new LeagueParser)->start(true);
      dd('xx');



      /**
       * @var FantasyCalculator $fpCalculator
       */
      $draftCalculator = app(FantasyCalculatorType::FANTASY_DRAFT, [0]);

      dd($draftCalculator->getDraftPolicy(OriginGrade::SS));
      (new LeagueParser)->start();
      (new SeasonScheduleParser())->start();

      //
      (new MA8MatchPreviews([
        'ccehccfptwefxqppskept0wlw',
      ]))->start();
      dd("");


      (new LiveLiveMA2MatchStatsParser())->start();


      (new MA6CommentaryParser())->setParams(['mode' => 'live'])->start(true);
      // (new PlateCRefsUpdator)->start();
      // broadcast(new LiveStatsPublishEvent(['schedule_id' => 'abc', 'my' => 'hi', 'babo' => 'yes']));
      // (new PlateCardPriceChangeUpdator)->start();
      // (new GameJoinUpdator('3mvuf4c9wv4yg0242s5sdk5jo'))->update();
      // (new PlayerCurrentMetaRef(null))->update();

      // (new OT2TournamentCalendarParser())->setParams($param)->start(true);

      // (new TM1ContestantsParser())->setParams($param)->start(true);

      // (new TM3SquadsPlayersParser())->setParams($param)->start(true);

      // (new MA1FixtureAndResultsParser())->setParams($param)->start(false);

      // (new PlayerUpdator())->setParams($param)->start();

      // (new MA2MatchStatsParser())->setParams($param)->start(false); // every day

      // (new PlateCardUpdator)->setParams($param)->start();

      // (new PointCQuantileUpdator())->setParams($param)->start();

      // (new TM4SeasonStats())->setParams($param)->start(true);

      // (new PE4SeasonRankingParser())->setParams($param)->start(true);

      // (new MA6Commentary())->setParams($param)->start(true);
    }
    return 0;
  }
}
