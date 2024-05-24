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

/**
 * 流量阀门.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Throttle extends AbstractAnnotation implements ThrottleInterface
{
    public function __construct(
        public int $limit = 60,
        public int $timer = 60,
        public mixed $key = null,
        public mixed $callback = null
    ) {
    }
}
