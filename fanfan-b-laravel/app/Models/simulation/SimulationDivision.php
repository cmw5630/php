<?php

namespace App\Models\simulation;

use App\Libraries\Traits\StaticTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class SimulationDivision extends Model
{
  use SoftDeletes, StaticTrait;

  protected $table = 'divisions';

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

  public function tier()
  {
    return $this->belongsTo(SimulationTier::class);
  }

  public function divisionStat()
  {
    return $this->hasOne(SimulationDivisionStat::class);
  }
}
