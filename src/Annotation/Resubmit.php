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
use Ella123\HyperfThrottle\Exception\ResubmitException;
use Ella123\HyperfThrottle\Handler\ResubmitHandler;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * 重复提交限制.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class Resubmit extends AbstractAnnotation implements ThrottleInterface
{
    public function __construct(
        public int $limit = 1,
        public int $timer = 60,
        public mixed $key = [ResubmitHandler::class, 'generateKey'],
        public mixed $callback = ResubmitException::class
    ) {
    }
}
