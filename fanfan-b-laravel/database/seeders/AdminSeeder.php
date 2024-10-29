<?php

namespace Database\Seeders;

use App\Models\admin\Admin;
use Illuminate\Database\Seeder;
use Schema;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
  public function run()
  {
    Schema::disableForeignKeyConstraints();
    $admin = Admin::create([
      'login_id' => 'admin',
      'password' => bcrypt('1234'),
      'nickname' => '최고관리자',
    ]);

    $role = Role::find(1);
    $admin->assignRole($role);
    Schema::enableForeignKeyConstraints();
  }
}
