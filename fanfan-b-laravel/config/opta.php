<?php
return [
  'basic' => [
    'host' => 'https://api.performfeeds.com',
    'outletKey' => env('OPTA_OUTLETKEY', '7tzd7uu2opcq1jlnz0yw60qai'),
    'params' => [
      '_fmt' => 'json',
      '_rt' => 'b',
    ]
  ],
  'endPoints' => [
    'MA0' => '/{id}?',
    'OT2' => '/authorized?',
    'OT3' => '/?ctst={id}',
    'TM1' => '/?tmcl={id}&detailed=yes',
    'MA1_detailed' => '/?tmcl={id}&live=yes&lineups=yes&_pgSz=100', // &_pgNm= 은 optaRequest 메소드의 $_saltParams 파라미터로 설정
    'TM3' => '/?tmcl={id}&_pgSz=1000&_pgNm=1&detailed=yes',
    'MA2' => '/{id}?detailed=yes',
    'SDC' => '/{id}?detailed=yes',
    'TM4' => '/?{id}&detailed=yes',
    'TM2' => '/?tmcl={id}&type=total',
    'TM7' => '/?ctst={id}',
    'PE2' => '/?prsn={id}',
    'PE7' => '/?tmcl={id}',
    'PE4' => '/?tmcl={id}',
    'MA6' => '/?fx={id}&type=fallback',
    'MA8' => '/{id}',
    'PE8' => '?fx={id}',
  ],
];
