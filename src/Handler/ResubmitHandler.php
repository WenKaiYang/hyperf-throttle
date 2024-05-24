<?php

namespace Ella123\HyperfThrottle\Handler;


use Ella123\HyperfThrottle\Exception\ResubmitException;

class ResubmitHandler
{
    public function buildException(): ResubmitException
    {
        // 429 Repeated submission
        return new ResubmitException();
    }
}