<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AdminRoleSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $arrRole = ['level_super'];
    foreach ($arrRole as $role) {
      $roleCreate = new Role(['guard_name' => 'admin']);
      $roleCreate->create([
        'name' => $role,
        'guard_name' => 'admin'
      ]);
    }
  }
}
