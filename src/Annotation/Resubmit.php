<?php

namespace Ella123\HyperfThrottle\Annotation;

use Attribute;
use Ella123\HyperfThrottle\Exception\ResubmitException;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Resubmit extends AbstractAnnotation implements ThrottleInterface
{
    public function __construct(
        public int   $limit = 1,
        public int   $timer = 60,
        public mixed $key = null,
        public mixed $callback = [ResubmitException::class, 'buildException']
    )
    {
    }
}