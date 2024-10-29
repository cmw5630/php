<?php

namespace App\Libraries\Classes;

use App\Exceptions\Custom\Parser\OTPNetworkException;
use GuzzleHttp\Exception\TransferException;
use Http;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Str;
use Throwable;

class SendAction extends Singleton
{
  const RETRY_COUNT = 7;
  const SLEEP_MILLISECONDS = 1500;
  protected $client = null;

  public function send(string $method, array $urls, array $header = [], array $params = [], $keySets = []): object|array
  {
    if ($this->client === null) {
      $this->client = Http::retry(self::RETRY_COUNT, self::SLEEP_MILLISECONDS);
    }

    if ($header) {
      $this->client = $this->client->withHeaders($header);

      foreach ($header as $key => $value) {
        if (Str::lower($key) === 'content-type' && Str::lower($value) === 'application/json') {
          $this->client->withBody(json_encode($params), Str::lower($value));
        }
      }
    }

    $responses = [];
    $query = [];

    // if (count($urls) === 1) {
    //   $url = Arr::first($urls);
    //   if (Str::lower($method) === 'get' && Str::contains($url, '?')) {
    //     $parsePath = parse_url($url);
    //     parse_str($parsePath['query'], $query);
    //     $params = array_merge($params, $query);
    //   }
    //   rescue(function () use ($method, $url, $params) {
    //     $responses[0] = $this->client->{Str::lower($method)}($url, $params);
    //   }, function ($e) use ($url) {
    //     if ($e->response->serverError()) {
    //       logger('서버에러:' . $url);
    //     } else if ($e->response->clientError()) {
    //       logger('클라이언트에러:' . $url);
    //     }
    //   });
    // } else {

    $responses = $this->client->pool(fn (Pool $pool) => array_map(function ($url) use (
      $pool,
      $method,
      $header,
      $params
    ) {
      if (Str::lower($method) === 'get' && Str::contains($url, '?')) {
        $parsePath = parse_url($url);
        parse_str($parsePath['query'], $query);
        $params = array_merge($params, $query);
      }
      if (Str::contains($url, 'b2ggames')) {
        $pool->withoutVerifying()->withHeaders($header)->{Str::lower($method)}($url, $params)
          ->then(function (Response|TransferException|Throwable $response) {
            if ($response instanceof TransferException) {
              // LogEx::error('http-client-error', $response->getMessage());
              report(new OTPNetworkException(null, [], $response));
            } else if ($response instanceof Throwable) {
              report(new OTPNetworkException(null, [], $response)); // 임시 에러(에러타입 정의 필요)
            }
          });
      } else {
        $pool->withHeaders($header)->{Str::lower($method)}(
          $url,
          $params
        )->then(function (Response|TransferException|Throwable $response) {
          if ($response instanceof TransferException) {
            // LogEx::error('http-client-error', $response->getMessage());
            report(new OTPNetworkException(null, [], $response));
          } else if ($response instanceof Throwable) {
            report(new OTPNetworkException(null, [], $response)); // 임시 에러(에러타입 정의 필요)
          }
        });
      }
    }, $urls));
    // }

    foreach ($responses as $key => $val) {
      if ($val instanceof Response) {
        // __isJsonData 사용 시 메모리 초과오류로 인해 제거.
        $result[!empty($keySets) ? $keySets[$key] : $key] = $val->json();
      }
    }

    return $result ?? [];
  }

  public function clear(): void
  {
    $this->client = null;
  }
}
