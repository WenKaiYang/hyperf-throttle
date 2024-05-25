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
use Ella123\HyperfThrottle\Handler\SmsLimitHandler;
use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * 短信每天限制.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class SmsDayLimit extends AbstractAnnotation implements ThrottleInterface
{
    /**
     * SmsDayLimit(limit:15,timer:86400) 15条/天.
     */
    public function __construct(
        public int $limit = 15,
        public int $timer = 86400,
        public mixed $key = [SmsLimitHandler::class, 'generateKey'],
        public mixed $callback = [SmsLimitHandler::class, 'exceptionSmsDayCallback'],
    ) {
    }
}
