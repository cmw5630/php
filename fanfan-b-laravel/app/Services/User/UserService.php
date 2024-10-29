<?php

namespace App\Services\User;

use App\Enums\FantasyCalculator\FantasyCalculatorType;
use App\Enums\Opta\Card\CardGrade;
use App\Enums\Opta\Card\DraftCardStatus;
use App\Enums\Opta\Card\OriginGrade;
use App\Enums\Opta\Player\PlayerPosition;
use App\Enums\Opta\Player\PlayerSubPosition;
use App\Enums\Opta\Schedule\ScheduleStatus;
use App\Enums\UserChangeType;
use App\Enums\UserStatus;
use App\Libraries\Traits\LogTrait;
use App\Libraries\Traits\SimulationTrait;
use App\Models\data\League;
use App\Models\data\Schedule;
use App\Models\game\DraftComplete;
use App\Models\game\DraftSelection;
use App\Models\game\PlateCard;
use App\Models\log\UserChangeLog;
use App\Models\log\UserWithdrawLog;
use App\Models\meta\RefPlayerOverallHistory;
use App\Models\user\User;
use App\Models\user\UserMeta;
use App\Models\user\UserPlateCard;
use App\Models\user\UserReferral;
use Arr;
use DB;
use Exception;
use Hash;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Storage;
use Schema;

interface UserServiceInterface
{
  public function checkPassword(string $_data);
  //  public function checkPassword(array $_data);
  public function getReferralUsers(int $referralId);
  public function setUserMetaInfo(int $_joinId, array $_data);
  public function signup(array $_data);
  public function withdraw(array $_data);
}
class UserService implements UserServiceInterface
{
  use LogTrait, SimulationTrait;

  protected ?Authenticatable $user;

  public function __construct(?Authenticatable $_user)
  {
    $this->user = $_user;
  }

  public function checkPassword(string $_password)
  {
    $password = $this->user->password;
    return Hash::check($_password, $password);
  }

  private function makeReferralCode($_length = 8)
  {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $_length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    if (UserReferral::where('user_referral_code', $randomString)->exists()) {
      return $this->makeReferralCode();
      logger($randomString);
    } else {
      return $randomString;
    }
  }

  public function getReferralUsers(int $referralId)
  {
    $referralId2 = UserReferral::whereHas('joinUser', function ($query) use ($referralId) {
      $query->where([
        ['id', $referralId],
        ['status', UserStatus::NORMAL]
      ]);
    })
      ->where('user_id', $referralId)->value('referral_id');
    if (!is_null($referralId2)) {
      return array_merge($this->getReferralUsers($referralId2), [$referralId]);
    } else {
      return [$referralId];
    }
  }

  public function setUserMetaInfo(int $_joinId, array $_data)
  {
    $userMeta = new UserMeta();
    $userMeta->user_id = $_joinId;
    $userMeta->favorite_team_id = $_data['favorite_team'];
    $userMeta->optional_agree = $_data['optional_agree'];
    $userMeta->save();

    $useReferral = new UserReferral();
    if (isset($_data['user_referral_code'])) {
      $referralCode = $_data['user_referral_code'];
      $referralUser = UserReferral::where('user_referral_code', $referralCode);
      if ($referralUser->exists()) {
        $useReferral->referral_id = $referralUser->value('id');
      }
    }

    $useReferral->user_id = $_joinId;
    $useReferral->user_referral_code = $this->makeReferralCode();
    $useReferral->save();

    $this->temporaryGiving($_joinId);
  }

  public function signup(array $_data)
  {
    $user = new User();
    $user->email = $_data['email'];
    $user->password = bcrypt($_data['password']);
    $user->name = $_data['name'];
    $user->country = $_data['country'];
    $user->nation = $_data['nation'];
    $user->save();
    // TODO : 메일 인증

    return $user->id;
  }

  // Todo : 추후 삭제 [회원가입 시 골드, 인게임카드 지급]
  public function temporaryGiving($_userId)
  {
    $user = User::find($_userId);
    $user->gold = 50000000;
    $user->save();

    $userMetaTeam = UserMeta::where('user_id', $_userId)->value('favorite_team_id');
    Schema::connection('simulation')->disableForeignKeyConstraints();

    $cards = $this->getPreferredTeamCards($userMetaTeam);

    foreach ($cards as $info) {

      $userPlateCard = new UserPlateCard();
      $userPlateCard->user_id = $_userId;
      $userPlateCard->plate_card_id = $info['id'];
      $userPlateCard->order_league_id = $info['league_id'];
      $userPlateCard->order_team_id = $info['team_id'];
      $userPlateCard->player_name = $info['first_name'] . '_' . $info['last_name'];

      $userPlateCard->ref_player_overall_history_id = $info['current_ref_player_overall']['id'];
      $userPlateCard->order_overall = $info['current_ref_player_overall']['final_overall'];

      $userPlateCard->draft_season_id = $info['season_id'];
      $userPlateCard->draft_season_name = $info['season']['name'];
      $userPlateCard->draft_team_id = $info['team_id'];
      $userPlateCard->draft_team_names = [
        'team_code' => $info['team_code'],
        'team_name' => $info['team_name'],
        'team_short_name' => $info['team_short_name']
      ];

      $userPlateCard->draft_schedule_round = 1;
      $userPlateCard->ingame_fantasy_point = 0;
      $userPlateCard->draft_level = 0;
      $userPlateCard->is_mom = false;
      $userPlateCard->is_free = true;
      if ($info['position'] != PlayerPosition::GOALKEEPER) {
        $userPlateCard->attacking_level = 0;
      } else {
        $userPlateCard->goalkeeping_level = 0;
      }

      $userPlateCard->passing_level = 0;
      $userPlateCard->defensive_level = 0;
      $userPlateCard->duel_level = 0;
      $userPlateCard->special_skills = [];
      $userPlateCard->card_grade = CardGrade::NORMAL;
      $userPlateCard->position = $info['position'];
      $userPlateCard->status = DraftCardStatus::COMPLETE;
      $userPlateCard->draft_completed_at = now();
      $userPlateCard->is_open = true;
      $userPlateCard->save();

      $userPlateCardId = $userPlateCard->id;

      /*$draftSelection = new DraftSelection();
      $draftSelection->user_id = $_userId;
      $draftSelection->user_plate_card_id = $userPlateCardId;

      $lastSchedule = Schedule::where([
        ['season_id', $info['plateCard']->season_id],
        ['status', ScheduleStatus::PLAYED]
      ])
        ->where(function ($query) use ($info) {
          $query->where('home_team_id', $info['plateCard']->team_id)
            ->orWhere('away_team_id', $info['plateCard']->team_id);
        })->latest()
        ->first();

      $draftSelection->schedule_id = $lastSchedule->id;
      $draftSelection->schedule_started_at = $lastSchedule->started_at;
      $draftSelection->player_id = $info['plateCard']->player_id;
      $draftSelection->summary_position = $info['plateCard']->position;
      $draftSelection->save();

      $draftComplete = new DraftComplete();
      $draftComplete->user_id = $_userId;
      $draftComplete->user_plate_card_id = $userPlateCardId;
      $draftComplete->summary_position = $info['plateCard']->position;
      $draftComplete->save();*/

      // $this->setIngameCardOverall($userPlateCardId);
      /**
       * @var FantasyCalculator $foCalculator
       */
      $foCalculator = app(FantasyCalculatorType::FANTASY_OVERALL, [0]);
      $foCalculator->calculate($userPlateCardId);
    };
    Schema::connection('simulation')->enableForeignKeyConstraints();
  }

  public function withdraw($_data)
  {
    $userId = $this->user->id;
    // $userId = 10;
    DB::beginTransaction();
    try {
      $parentId = '';
      $childIds = [];
      $userReferralId = UserReferral::where('user_id', $userId)->value('id');

      UserReferral::where(function ($query) use ($userId, $userReferralId) {
        $query->where('referral_id', $userReferralId)
          ->orWhere('user_id', $userId);
      })->get()
        ->map(function ($info) use ($userId, &$parentId, &$childIds) {
          if ($info->user_id === $userId) {
            $parentId = $info->referral_id;
          } else {
            if ($info->user_id !== $info->referral_id) {
              array_push($childIds, $info->user_id);
            }
          }
        });

      UserReferral::whereIn('user_id', $childIds)->update(['referral_id' => $parentId]);

      foreach ($childIds as $id) {
        $this->recordLog(UserChangeLog::class, [
          'user_id' => $this->user->id,
          'old_referral_id' => $id,
          'new_referral_id' => $parentId,
          'change_type' => UserChangeType::CHANGE,
          'description' => $userId . ' -> ' . $parentId
        ]);
      }
      // UserMeta::where('user_id', $userId)->delete();
      // UserReferral::where('user_id', $userId)->delete();

      $user = User::where('id', $userId);
      $this->recordLog(UserChangeLog::class, [
        'user_id' => $this->user->id,
        'join_at' => $user->value('created_at'),
        'change_type' => UserChangeType::OUT,
        'description' => $user->value('created_at')
      ]);

      $user->update(['status' => UserStatus::OUT]);
      // $user->delete();

      // log
      foreach ($_data['reason'] as $reason) {
        UserWithdrawLog::updateOrCreate([
          'reason' => $reason

        ], ['count' => DB::raw('count+1')]);
      }

      DB::commit();
    } catch (Exception $th) {
      logger($th);
      DB::rollBack();
    }
  }

  public function getUser()
  {
    return $this->user;
  }

  public function uploadPhoto($_userMeta, ?UploadedFile $_file)
  {
    try {
      $oldPhoto = $_userMeta->photo_path;
      $storage = Storage::disk();
      $path = $storage->putFile('/user_photo', $_file);

      $_userMeta->photo_path = $path;
      $_userMeta->save();
      // 기존 사진 있으면 삭제
      if (!is_null($oldPhoto)) {
        $this->deletePhotoFile($oldPhoto);
      }
    } catch (Exception $th) {
      if (isset($path)) {
        $this->deletePhotoFile($path);
      }
      throw $th;
    }
  }

  // 사진 파일만 지움
  public function deletePhotoFile($path)
  {
    try {
      $storage = Storage::disk();
      $storage->delete($path);
    } catch (Exception $th) {
      return false;
    }
    return true;
  }

  public function getPreferredTeamCards($_teamId)
  {
    // 선발
    $gameStartedSubPositions = array_values(config('formation-by-sub-position.formation_used')['442']);

    // 공통 쿼리
    $targetPlateQuery = PlateCard::with([
      'season:id,name',
      'currentRefPlayerOverall:id,final_overall,player_id,sub_position,second_position,third_position'
    ])
      ->where('league_id', League::defaultLeague()->id)
      ->isOnSale()
      ->whereNotIn('grade', [OriginGrade::SS, OriginGrade::S])
      ->select([
        'id',
        'player_id',
        'league_id',
        'team_id',
        'season_id',
        'team_code',
        'team_name',
        'team_short_name',
        'position',
        'first_name',
        'last_name'
      ]);

    // 선호/선발팀 복사
    $preferTeamSubPositions = $startedTeamSubPositions = Arr::shuffle($gameStartedSubPositions);

    $preferPick = [];
    $limitCount = 5;
    foreach ($preferTeamSubPositions as $idx => $position) {
      $pick = $targetPlateQuery->clone()
        ->whereNotIn('id', Arr::pluck($preferPick, 'id'))
        ->whereHas(
        'currentRefPlayerOverall',
        function ($overallQuery) use ($position) {
          $overallQuery->where(function ($query) use ($position) {
            $query->where('sub_position', $position)
              ->orWhere('second_position', $position)
              ->orWhere('third_position', $position);
          });
        }
      )
        ->where('team_id', $_teamId)
        ->inRandomOrder()
        ->limit(1)
        ->get()
        ->toArray();

      if (count($pick) < 1) {
        continue;
      }
      $preferPick = array_merge($preferPick, $pick);

      // 선발팀 array 에서 지우기
      unset($startedTeamSubPositions[$idx]);

      $limitCount--;
      if ($limitCount === 0) {
        break;
      }
    }

    // logger('선호 팀');
    // logger($preferTeamSubPositions);
    // logger(count($preferPick));
    $result = $preferPick;

    // 선발 팀 5장
    $countBySubPositions = array_count_values($startedTeamSubPositions);

    $startedPick = [];
    foreach ($countBySubPositions as $position => $limit) {
      $tryCount = 0;
      while (true) {
        $tryCount++;
        $pick = $targetPlateQuery->clone()
          // 이미 뽑은 카드는 제외
          ->whereNotIn('id', Arr::pluck($result, 'id'))
          ->whereHas(
            'currentRefPlayerOverall',
            function ($overallQuery) use ($position) {
              $overallQuery->where('sub_position', $position);
            }
          )
          ->inRandomOrder()
          ->limit($limit)
          ->get()
          ->toArray();

        // 팀당 7개 제한
        $countByTeam = array_count_values(Arr::pluck(
          $merged = array_merge($startedPick, $pick),
          'team_id'
        ));

        $breakWhile = true;
        foreach ($countByTeam as $count) {
          if ($count > 7) {
            $breakWhile = false;
            // foreach break
            break;
          }
        }

        if ($breakWhile) {
          // while break
          break;
        }
        if ($tryCount > 5) {
          throw new Exception('team_id :' . $_teamId . ': Try Limit!!');
        }
      }

      $startedPick = $merged;
    }

    // logger('선발 팀');
    // logger(array_column(array_column($startedPick, 'current_ref_player_overall'), 'sub_position'));
    // logger(count($startedPick));

    $result = array_merge($result, $startedPick);

    // 교체 팀 5장 + 후보 팀 카드 9 장 뽑기
    $options = [[1, 2], [2, 1]];
    [$lmLimit, $rmLimit] = $options[rand(0, 1)];
    [$lbLimit, $rbLimit] = $options[rand(0, 1)];

    $substitutionSubPositions = [
      PlayerSubPosition::ST => 2,
      PlayerSubPosition::LM => $lmLimit,
      PlayerSubPosition::RM => $rmLimit,
      PlayerSubPosition::CM => 3,
      PlayerSubPosition::LB => $lbLimit,
      PlayerSubPosition::RB => $rbLimit,
      PlayerSubPosition::CB => 3,
    ];

    $substitutionPick = [];
    foreach ($substitutionSubPositions as $position => $limit) {
      $tryCount = 0;
      while (true) {
        $tryCount++;
        $pick = $targetPlateQuery->clone()
          ->whereNotIn('id', Arr::pluck($result, 'id'))
          ->whereHas(
            'currentRefPlayerOverall',
            function ($overallQuery) use ($position) {
              $overallQuery->where('sub_position', $position);
            }
          )
          ->inRandomOrder()
          ->limit($limit)
          ->get()
          ->toArray();

        // 팀당 7개 제한
        $countByTeam = array_count_values(Arr::pluck(
          $merged = array_merge($substitutionPick, $pick),
          'team_id'
        ));

        $breakWhile = true;
        foreach ($countByTeam as $count) {
          if ($count > 7) {
            $breakWhile = false;
            // foreach break
            break;
          }
        }

        if ($breakWhile) {
          // while break
          break;
        }
        if ($tryCount > 5) {
          throw new Exception('team_id :' . $_teamId . ': Try Limit!!');
        }
      }

      $substitutionPick = $merged;
    }

    // logger('교체/후보 팀');
    // logger(array_column(array_column($substitutionPick, 'current_ref_player_overall'), 'sub_position'));
    // logger(count($substitutionPick));

    $result = array_merge($result, $substitutionPick);

    if (count($result) !== 25) {
      throw new Exception('team_id :' . $_teamId . ': Insufficient cards!');
    }

    return $result;
  }

  public function getWelcomePackCards($userId)
  {
    $userMetaTeam = UserMeta::where('user_id', $userId)->value('favorite_team_id');
    $positionOrder = ['Attacker' => 4, 'Midfielder' => 3, 'Defender' => 2, 'Goalkeeper' => 1];
    return  UserPlateCard::whereHas('simulationOverall', function ($overallQuery) use ($userId) {
      $overallQuery->where('user_id', $userId);
    })->select('id', 'user_id', 'is_mom', 'special_skills', 'draft_level', 'card_grade', 'plate_card_id', 'draft_team_names', 'position')
      ->where('draft_team_id', $userMetaTeam)
      ->with([
        'plateCard:id,team_id,player_id,headshot_path,' .   implode(',', config('commonFields.player')),
        'simulationOverall:user_plate_card_id,final_overall,sub_position',
      ])
      ->get()
      ->map(function ($info) use ($positionOrder) {
        $info->sub_position = $info->simulationOverall->sub_position;
        $info->final_overall = $info->simulationOverall->final_overall[$info->sub_position];
        $info->position_order = $positionOrder[$info->position];
        unset($info->simulationOverall);
        return $info;
      })
      ->sortByDesc(function ($item) {
        return [
          $item['final_overall'],
          $item['position_order']
        ];
      })
      ->take(5);
  }
}
