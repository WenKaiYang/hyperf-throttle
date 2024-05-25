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
 * 短信小时限制.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class SmsHourLimit extends AbstractAnnotation implements ThrottleInterface
{
    /**
     * SmsHourLimit(limit:5,timer:3600) 5条/小时.
     */
    public function __construct(
        public int   $limit = 5,
        public int   $timer = 3600,
        public mixed $key = [SmsLimitHandler::class, 'generateKey'],
        public mixed $callback = [SmsLimitHandler::class, 'exceptionSmsHourCallback'],
    )
    {
    }
}
