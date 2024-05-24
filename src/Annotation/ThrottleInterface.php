<?php

namespace Ella123\HyperfThrottle\Annotation;

interface ThrottleInterface
{
    /**
     * @param int $limit 限制频次
     * @param int $timer 时间周期（秒）
     * @param null|string|array $key 标识Key
     * @param null|string|array $callback 超频回调
     */
    public function __construct(
        int               $limit = 60,
        int               $timer = 60,
        null|string|array $key = null,
        null|string|array $callback = null
    );
}