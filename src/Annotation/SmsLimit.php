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
 * 发送短信限制
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class SmsLimit extends AbstractAnnotation implements ThrottleInterface
{
    /**
     * SmsLimit(limit:1,timer:60) 1条/分钟
     * SmsLimit(limit:5,timer:3600) 5条/小时
     * SmsLimit(limit:15,timer:86400) 15条/天
     * @param int $limit
     * @param int $timer
     * @param mixed|null $key
     * @param mixed|null $callback
     */
    public function __construct(
        public int   $limit = 1,
        public int   $timer = 60,
        public mixed $key = [SmsLimitHandler::class, 'generateKey'],
        public mixed $callback = [SmsLimitHandler::class, 'exceptionCallback'],
    )
    {
    }
}
