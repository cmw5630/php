<?php

namespace App\Models\admin;

use DateTimeInterface;
use App\Libraries\Classes\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use LoganSong\LaravelMultiDatabase\traits\ModelExtensionTrait;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Admin extends Authenticatable implements JWTSubject
{
  use SoftDeletes, HasRoles, ModelExtensionTrait;

  protected $guard = 'admin';

  protected $connection = 'admin';

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'login_id',
    'password',
    'nickname',
    'name',
    'access_token',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = [
    'password',
    'access_token',
    'remember_token',
    'updated_at',
  ];

  public function getJWTIdentifier()
  {
    return $this->getKey();
  }

  public function getJWTCustomClaims()
  {
    return [];
  }

  public function getRoleName()
  {
    return $this->getRoleNames()[0];
  }

  protected function serializeDate(DateTimeInterface $date)
  {
    return $date->format('Y-m-d H:i:s');
  }
}
