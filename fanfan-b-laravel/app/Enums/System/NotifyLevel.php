<?php

namespace App\Enums\System;

use BenSampo\Enum\Enum;

final class NotifyLevel extends Enum
{
  const DEBUG = 'debug';
  const INFO = 'info';
  const WARN = 'warn';
  const CRITICAL = 'critical';
}
