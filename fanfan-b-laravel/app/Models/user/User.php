<?php

namespace App\Models\user;

use App\Enums\UserStatus;
use App\Models\admin\UserRestriction;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Libraries\Classes\User as Authenticatable;
use App\Models\simulation\SimulationApplicant;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Str;

class User extends Authenticatable implements MustVerifyEmail
{
  use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

  /**
   * The attributes that are mass assignable.
   *
   * @var array<int, string>
   */
  protected $fillable = [
    'name',
    'email',
    'password',
  ];

  /**
   * The attributes that should be hidden for serialization.
   *
   * @var array<int, string>
   */
  protected $hidden = [
    'password',
    'temp_password',
    'temp_password_expired_at',
    'updated_at',
  ];

  /**
   * The attributes that should be cast.
   *
   * @var array<string, string>
   */
  protected $casts = [
    'email_verified_at' => 'datetime',
    'name_change' => 'boolean',
    'created_at' => 'datetime'
  ];

  public function getAuthPassword()
  {
    // 임시 비밀번호 사용 시
    if (!empty($this->temp_password)) {
      return $this->temp_password;
    }
    return $this->password;
  }

  protected static function booted()
  {
    parent::booted();
    static::addGlobalScope('excludeWithdraw', function (Builder $builder) {
      $builder->where('status', '!=', UserStatus::OUT);
    });
  }

  protected function scopeMaskingName()
  {
    if (!is_null($this->attributes['name'])) {
      $this->attributes['name'] = Str::substr($this->attributes['name'], 0, 3) . '****';
    }
  }

  public function linkedSocialAccounts()
  {
    return $this->hasOne(LinkedSocialAccount::class);
  }

  public function userLoginLogs()
  {
    return $this->hasMany(UserLoginLog::class);
  }

  public function latestUserLoginLogs()
  {
    return $this->hasOne(UserLoginLog::class)->latest();
  }

  public function userMeta()
  {
    return $this->hasOne(UserMeta::class);
  }

  public function userReferral()
  {
    return $this->hasOne(UserReferral::class, 'user_id', 'id');
  }

  public function restriction()
  {
    return $this->hasOne(UserRestriction::class)->latest();
  }

  public function applicant()
  {
    return $this->hasOne(SimulationApplicant::class, 'user_id');
  }
}
