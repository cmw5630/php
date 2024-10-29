<?php

namespace App\Services\User;

use App\Libraries\Traits\LogTrait;
use Illuminate\Contracts\Auth\Authenticatable;

interface UserPointServiceInterface
{
  public function plusUserPointWithLog(
    int $_amount,
    string $_pointType,
    string $_pointRefType = 'etc',
    string $_description = '',
    ?int $_userId = null
  ): void;

  public function minusUserPointWithLog(
    int $_amount,
    string $_pointType,
    string $_pointRefType = 'etc',
    string $_description = '',
    ?int $_userId = null
  ): void;
}

class UserPointService implements UserPointServiceInterface
{
  use LogTrait;

  protected ?Authenticatable $user;

  public function __construct(?Authenticatable $_user)
  {
    $this->user = $_user;
  }
}
