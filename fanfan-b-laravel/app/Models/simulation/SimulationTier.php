<?php

namespace App\Models\simulation;

use App\Libraries\Traits\StaticTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;
use Str;

class SimulationTier extends Model
{
  use SoftDeletes, StaticTrait;

  protected $table = 'tiers';

  protected $connection = 'simulation';

  public $incrementing = false;

  protected $keyType = 'string';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public static function boot()
  {
    parent::boot();

    static::creating(function ($model) {
      $model->id = self::getUuid($model);
    });
  }
}
