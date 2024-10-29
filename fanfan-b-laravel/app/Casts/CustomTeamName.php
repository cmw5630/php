<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Str;

class CustomTeamName implements CastsAttributes
{
  protected $precision;

  public function __construct($_precision = 1)
  {
    $this->precision = $_precision;
  }

  /**
   * Cast the given value.
   *
   * @param  \Illuminate\Database\Eloquent\Model  $model
   * @param  string  $key
   * @param  mixed  $value
   * @param  array  $attributes
   * @return array
   */

  private function getNameMap()
  {
    return [
      'Nottm Forest' => "Nott'm Forest",
      'Sheff Utd' => 'Sheffield Utd',
    ];
  }
  public function get($model, $key, $value, $attributes)
  {
    if (in_array($value, array_keys($this->getNameMap()))) {
      return $this->getNameMap()[$value];
    }
    return $value;
  }

  /**
   * Prepare the given value for storage.
   *
   * @param  \Illuminate\Database\Eloquent\Model  $model
   * @param  string  $key
   * @param  array  $value
   * @param  array  $attributes
   * @return string
   */
  public function set($model, $key, $value, $attributes)
  {
    return $value;
  }
}
