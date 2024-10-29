<?php

namespace App\Models;

use App\Models\admin\Admin;
use Illuminate\Database\Eloquent\SoftDeletes;
use Model;

class Banner extends Model
{
  use SoftDeletes;

  protected $guarded = [];

  protected $hidden = [
    'created_at',
    'updated_at',
    'deleted_at',
  ];

  public function admin()
  {
    return $this->belongsTo(Admin::class);
  }
}
