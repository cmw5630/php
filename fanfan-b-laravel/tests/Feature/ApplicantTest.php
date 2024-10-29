<?php

namespace Tests\Feature;

use App\Libraries\Classes\Exception;
use App\Models\simulation\SimulationApplicant;
use App\Models\simulation\SimulationDivision;
use App\Models\simulation\SimulationSeason;
use App\Models\simulation\SimulationUserLeague;
use App\Models\user\User;
use App\Services\Simulation\SimulationService;
use Carbon\CarbonInterface;
use DB;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ApplicantTest extends TestCase
{
  protected $simulationService = null;
  protected int $skip = 0;

  /**
   * A basic feature test example.
   *
   * @return void
   */
  public function test_example()
  {
    $limit = 10;
    $totalCount = 1;

    while ($totalCount > 0) {
      $users = $this->pickUser($this->skip, min($limit, $totalCount));
      // $users = User::doesntHave('applicant')
      // ->whereIn('id',[6,
      //   7,
      //   8,
      //   12,
      //   13,
      //   15,
      //   33,
      //   39,
      //   47,
      //   62,
      //   63,
      //   100,
      //   130])
      //   ->orderBy('id')
      //   ->get();
      foreach ($users as $user) {
        $this->simulationService = new SimulationService($user);
        DB::beginTransaction();
        try {
          $applicant = new SimulationApplicant;
          $applicant->user_id = $user->id;
          $applicant->server = 'asia';
          $name = strtoupper(substr(preg_replace("/[^a-zA-Z]/", "",
            $user->name), 0, 3));
          if (empty($name)) {
            $name = $this->generateRandomLetters();
          }

          $applicant->club_code_name = $name;

          $applicant->save();

          $registerDefaultUserLineup = $this->simulationService->registerDefaultUserLineup($applicant);

          if (!$registerDefaultUserLineup) {
            throw new Exception($user->id. ': Default lineup registration failed', Response::HTTP_BAD_REQUEST);
          }

          $divisionId = SimulationDivision::whereHas('tier', function ($query) {
            $query->where('level', 6);
          })
            ->value('id');

          if (is_null($divisionId)) {
            throw new Exception('No Divisions!', Response::HTTP_BAD_REQUEST);
          }

          $currentSeason = SimulationSeason::currentSeasons()->where('server', $applicant->server)
            ->first();
          $group = new SimulationUserLeague();
          $group->applicant_id = $applicant->id;
          $group->season_id = $currentSeason->id;
          $group->division_id = $divisionId;
          $group->save();

          DB::commit();
          $totalCount--;
          logger('완료');
        } catch (Exception $th) {
          DB::rollBack();
          info($th->getMessageEx());
        }
      }
      if ($totalCount > 0) {
        logger($totalCount . ' 남음');
      }
      $this->skip += $limit;
    }

    $this->assertTrue(true);
  }
  public function pickUser($skip, $limit)
  {
    return User::doesntHave('applicant')
      ->skip($skip)
      ->limit($limit)
      ->orderByDesc('id')
      ->get();
  }

  function generateRandomLetters($length = 3) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomLetters = '';

    for ($i = 0; $i < $length; $i++) {
      $index = rand(0, strlen($alphabet) - 1);
      $randomLetters .= $alphabet[$index];
    }

    return $randomLetters;
  }
}
