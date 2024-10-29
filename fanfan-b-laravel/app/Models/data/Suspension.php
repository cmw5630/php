<?php

namespace App\Models\data;

use Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// Todo : 추후 nft DB 사용 제거
class Suspension extends Model
{
  use SoftDeletes;

  protected $connection = 'data';

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];
}
