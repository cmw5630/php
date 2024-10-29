<?php

return [

  /*
  |--------------------------------------------------------------------------
  | Authentication Language Lines
  |--------------------------------------------------------------------------
  |
  | The following language lines are used during authentication for various
  | messages that we need to display to the user. You are free to modify
  | these language lines according to your application's requirements.
  |
  */
  'login' => [
    'failed' => 'Incorrect email or password. Please try again.',
    'failed_limit' => 'Your account has been temporarily locked due to multiple failed.'
                      .' Please try again later or contact us',
    'password_incorrect' => 'Password does not match, please confirm.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'restricted' => 'Your account has been suspended.',
  ],

  'token' => [
    'valid_failed' => 'The token is not valid.'
  ],

  'reset_password' => [
    'expired' => 'Your one-time password has been expired. Please reset your password.',
  ]
];
