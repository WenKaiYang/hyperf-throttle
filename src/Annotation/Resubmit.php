<?php

namespace Ella123\HyperfThrottle\Annotation;

use Attribute;
use Hyperf\Di\Annotation\AbstractAnnotation;
use Hyperf\Di\Annotation\AnnotationInterface;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Resubmit extends AbstractAnnotation implements AnnotationInterface
{
    public function __construct(
        public int   $limit = 1,
        public int   $timer = 60,
        public mixed $key = null,
        public mixed $callback = null
    )
    {
    }
}