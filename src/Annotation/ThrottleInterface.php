<?php

namespace Ella123\HyperfThrottle\Annotation;

interface ThrottleInterface
{
    public function __construct(
        int   $limit = 60,
        int   $timer = 60,
        mixed $key = null,
        mixed $callback = null
    );
}