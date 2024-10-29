<?php

namespace Database\Seeders;

use App\Models\admin\Admin;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminPermissionSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $arrPermission = [
      'super', // 슈퍼어드민
      'admin_access', // 어드민
      // 'dashboard_read', // 대시보드 읽기
      // 'member_read', // 회원 읽기
      // 'member_cud', // 회원 생성,수정,삭제
      // 'member_download_access', // 회원 엑셀 다운로드
      // 'kbl_read', // KBL 읽기
      // 'kbl_cud', // KBL 생성,수정,삭제
      // 'salary_read', // 선수관리 읽기
      // 'salary_cud', // 선수관리 생성,수정,삭제
      // 'system_read', // 시스템관리 읽기
      // 'system_cud', // 시스템관리 생성,수정,삭제
      // 'system_account_access', // 시스템관리 어드민 계정발급
    ];
    foreach ($arrPermission as $permission) {
      $permissionCreate = new Permission(['guard_name' => 'admin']);
      $permissionCreate->create([
        'name' => $permission,
        'guard_name' => 'admin'
      ]);
    }
  }
}
