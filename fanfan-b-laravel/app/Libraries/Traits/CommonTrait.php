<?php

namespace App\Libraries\Traits;

use App\Models\Banner;

trait CommonTrait
{
  protected function getBanners(array $location, string $platform = 'pc')
  {
    return Banner::where([
      'platform' => $platform,
    ])
      ->whereBetween('location', $location)
      ->where([
        ['started_at', '<=', now()],
        ['ended_at', '>=', now()],
        ['platform', $platform],
      ])
      ->orderBy('order_no')
      ->latest()
      ->get()
      ->groupBy('location');
  }

  protected function forbidTextRule($_target, $_include = [])
  {
    return [
      function ($key, $value, $fail) use ($_target, $_include) {
        $forbidConfig = config('forbidtext');
        $forbidText = $forbidConfig['text'];
        foreach ($_include as $key) {
          $forbidText = array_merge($forbidText, $forbidConfig[$key]);
        }
        if (in_array($value, $forbidText)) {
          $fail(sprintf('해당 %s 사용할 수 없습니다.', $_target));
          return;
        };
      }
    ];
  }

  private function getRedisCachingKey(string $_prefix, $_filter = [], $_suffix = null)
  {
    if ($_suffix) {
      $cachingKey = $_prefix . '_' . $_suffix;
    } else {
      $cachingKey = $_prefix . '_' . now()->format('Ymd');
      foreach ($_filter as $key => $value) {
        if ($value === null) {
          $value = $key;
        }
        $cachingKey = $cachingKey . '_' . $value;
      }
    }
    return $cachingKey;
  }
}