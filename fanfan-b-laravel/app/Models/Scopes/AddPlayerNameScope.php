<?php

namespace App\Models\Scopes;

use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AddPlayerNameScope implements Scope
{
  /**
   * Apply the scope to a given Eloquent query builder.
   *
   * @param  \Illuminate\Database\Eloquent\Builder  $builder
   * @param  \Illuminate\Database\Eloquent\Model  $model
   * @return void
   */
  public function apply(Builder $builder, Model $model)
  {
    $builder->when(empty($builder->getQuery()->columns), fn ($q) => $q->select('*'))
      ->addSelect(DB::raw('concat_ws(\' \', first_name, last_name) as player_name,
         concat_ws(\' \', short_first_name, short_last_name) as short_player_name,
         concat_ws(\' \', first_name_eng, last_name_eng) as player_name_eng'));
  }
}