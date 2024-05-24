<?php

namespace Ella123\HyperfThrottle\Annotation;

use Attribute;
use Ella123\HyperfThrottle\Handler\ResubmitHandler;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Resubmit extends AbstractAnnotation implements ThrottleInterface
{
    public function __construct(
        public int               $limit = 1,
        public int               $timer = 60,
        public null|string|array $key = [ResubmitHandler::class, 'generateKey'],
        public null|string|array $callback = [ResubmitHandler::class, 'exceptionCallback']
    )
    {
    }
}