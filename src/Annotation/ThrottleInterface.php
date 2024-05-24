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

interface ThrottleInterface
{
    /**
     * @param int $limit 限制频次
     * @param int $timer 时间周期（单位：s）
     * @param null|array|string $key 标识Key(支持自定义回调)
     * @param null|array|string $callback 超频回调(支持自定义回调)
     */
    public function __construct(
        int   $limit = 60,
        int   $timer = 60,
        mixed $key = null,
        mixed $callback = null
    );
}
