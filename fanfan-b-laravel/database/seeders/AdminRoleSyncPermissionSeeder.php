<?php

namespace Database\Seeders;

use App\Models\admin\Admin;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminRoleSyncPermissionSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    // 등급표기
    // level_super = 슈퍼어드민
    // level_1 = 1레벨
    // level_1 = 2레벨
    // level_3 = 3레벨
    $roleData = Role::all();
    foreach ($roleData as $role) {
      switch ($role->name) {
        case 'level_super':
          $perData = Permission::all();
          $role->syncPermissions($perData);
          break;
        // case 'level_1':
        //   $perData = Permission::whereIn('name',
        //     ['admin_access', 'dashboard_read', 'member_read', 'kbl_read'])->get();
        //   $role->syncPermissions($perData);
        //   break;
        // case 'level_2':
        //   $perData = Permission::whereIn('name', [
        //     'admin_access',
        //     'dashboard_read',
        //     'member_read',
        //     'kbl_read',
        //     'member_download_access'
        //   ])->get();
        //   $role->syncPermissions($perData);
        //   break;
        // case 'level_3':
        //   $perData = Permission::whereNotIn('name', ['super', 'system_account_access'])->get();
        //   $role->syncPermissions($perData);
        //   break;
      }
    }
  }
}
