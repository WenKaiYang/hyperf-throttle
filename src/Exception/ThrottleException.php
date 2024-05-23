<?php

namespace Ella123\HyperfThrottle\Exception;

class ThrottleException extends \Exception
{
    protected $code = 429;

    protected $message = 'Too Many Attempts.';
}