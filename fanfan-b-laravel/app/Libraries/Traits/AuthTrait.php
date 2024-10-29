<?php

namespace App\Libraries\Traits;

use App\Models\user\User;
use Auth;
use Hash;

trait AuthTrait
{
  protected function passwordRule(): array
  {
    return ['string', function ($key, $value, $fail) {
      if (!preg_match($this->passwordPattern(), $value)) {
        $fail(__('validation.password.invalid', ['min' => self::MIN, 'max' => self::MAX]));
      }
    }];
  }

  protected function newPasswordRule()
  {
    return ['string', function ($key, $value, $fail) {
      if (!preg_match($this->passwordPattern(), $value)) {
        $fail(__('validation.password.invalid', ['min' => self::MIN, 'max' => self::MAX]));
        return;
      }

      $user = Auth::user();

      if (Hash::check($value, $user->password)) {
        $fail(__('validation.password.same_as_old'));
      }
    }];
  }

  public function passwordPattern(): string
  {
    $result = '';
    $regexs = [
      self::PATTERN_ALPHABET_UPPER,
      self::PATTERN_ALPHABET_LOWER,
      self::PATTERN_NUMBER,
      self::PATTERN_SPECIAL,
    ];

    $total = [];
    foreach ($regexs as $regex) {
      $backSlash = str_contains($regex, '\\');
      $result .= '(?=.*'. ($backSlash ? '' : '[').$regex.($backSlash ? '' : ']').')';
      $total[] = $regex;
    }
    $result .= '['.implode('', $total).']';

    return '/^' . $result
      . sprintf('{%d,%d}', self::MIN, self::MAX) . '$/';
  }

  protected function articleConvert(string $sentence)
  {
    return preg_replace('/(^| )a ([aeiouAEIOU])/', '$1an $2', $sentence);
  }
}
