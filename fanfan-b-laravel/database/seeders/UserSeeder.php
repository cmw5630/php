<?php

namespace Database\Seeders;

use App\Models\user\User;
use App\Models\user\UserMeta;
use App\Models\user\UserReferral;
use App\Services\User\UserService;
use DB;
use Illuminate\Database\Seeder;
use Throwable;

class UserSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    if (User::all()->isNotEmpty()) {
      return;
    }

    DB::beginTransaction();
    try {
      $user = new User();
      $user->name = 'test';
      $user->email = 'test@b2ggames.com';
      $user->password = bcrypt('1234');
      $user->save();

      $userMeta = new UserMeta();
      $userMeta->user_id = $user->id;
      $userMeta->save();

      $userRefferal = new UserReferral();
      $userRefferal->user_id = $user->id;
      $userRefferal->user_referral_code = $this->makeReferralCode();
      $userRefferal->save();
      DB::commit();
    } catch (Throwable $th) {
      DB::rollback();
      logger($th);
    }
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
}
