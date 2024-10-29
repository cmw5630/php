<?php

namespace App\Interfaces;

interface UserConstantInterface
{
  const PATTERN_NUMBER = '\d';
  const PATTERN_ALPHABET_UPPER = 'A-Z';
  const PATTERN_ALPHABET_LOWER = 'a-z';
  const PATTERN_SPECIAL = '!@#$%^&*';
  const MIN = 8;
  const MAX = 16;
  const SIGNIN_FAILED_LIMIT = 5;
}
