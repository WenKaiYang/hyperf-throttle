<?php

namespace Ella123\HyperfThrottle\Exception;

use Exception;

class ResubmitException extends Exception
{
    protected $code = 429;

    protected $message = 'Repeated submission.';
}