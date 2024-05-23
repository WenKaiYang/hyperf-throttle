<?php

namespace Ella123\HyperfThrottle\Annotation;

use \Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Throttle extends AbstractAnnotation
{
    public function __construct(
        public ?int $maxAttempts = null,
        public ?int $decaySeconds = null,
        public ?string $prefix = null,
        public ?string $key = null,
        public mixed $generateKeyCallable = null,
        public mixed $tooManyAttemptsCallback = null
    ) {
    }
}