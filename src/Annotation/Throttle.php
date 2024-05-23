<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Ella123\HyperfThrottle\Annotation;

use Attribute;
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
