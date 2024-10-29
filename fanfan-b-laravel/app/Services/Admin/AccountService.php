<?php

namespace App\Services\Admin;

use App\Enums\Admin\AccountLevel;
use App\Models\admin\Admin;
use Spatie\Permission\Models\Role;

interface AccountServiceInterface
{
  public function main(): array;

  public function store(array $_data);

  public function destroy(int $_userId);

  public function edit(array $_data);
}

class AccountService implements AccountServiceInterface
{
  protected int $limit;

  public function __construct()
  {
    $this->limit = 20;
  }

  public function main(): array
  {
    $user = tap(Admin::withHas('roles')
      ->select('id', 'login_id', 'nickname', 'name', 'created_at')
      ->orderByDesc('id')
      ->paginate($this->limit))
      ->map(function ($item) {
        $level = null;
        switch ($item->roles[0]->id) {
          case AccountLevel::LEVEL_SUPER:
            $level = 1;
            break;
          case AccountLevel::LEVEL_1:
            $level = 2;
            break;
          case AccountLevel::LEVEL_2:
            $level = 3;
            break;
          case AccountLevel::LEVEL_3:
            $level = 4;
            break;
        }
        $item->level = $level;
        unset($item->roles);

        return $item;
      })
      ->toArray();

    return __setPaginateData($user, []);
  }

  public function store(array $_data)
  {
    $role = Role::find($_data['role']);

    $insertData = [
      'login_id' => $_data['login_id'],
      'name' => $_data['name'],
      'password' => bcrypt($_data['password']),
    ];
    $user = Admin::create($insertData);
    $user->assignRole($role); // spatie 롤 추가 메소드

    // nickname 추가
    $user->nickname = '관리자' . $user->id;
    $user->save();

    return true;
  }

  public function destroy(int $_userId)
  {
    $user = Admin::findOrFail($_userId);
    $user->delete();

    return true;
  }

  public function edit(array $_data)
  {
    $role = Role::findOrFail($_data['role']);

    $user = Admin::findOrFail($_data['user_id']);
    $user->removeRole($user->roles()->first()->id); // spatie 롤 삭제 메소드
    $user->assignRole($role); // spatie 롤 추가 메소드

    if (isset($_data['password'])) {
      $user->password = bcrypt($_data['password']);
      $user->save();
    }

    return $role->id;
  }
}
