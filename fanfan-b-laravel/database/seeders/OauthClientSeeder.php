<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\Passport;

class OauthClientSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    if (Passport::client()->count() > 0) {
      return;
    }

    $seedData = [
      [
        'name' => 'FSOCCER Password Grant Client',
        'secret' => 'yZbPeMmd0VfnhrFZY4KDGYiMBtLymfyFzgmHNan2',
        'provider' => 'users',
        'redirect' => 'http://localhost',
        'personal_access_client' => 0,
        'password_client' => 1,
        'revoked' => 0,
      ],
    ];
    Passport::client()->insert($seedData);
  }
}
