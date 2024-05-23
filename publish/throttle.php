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
use Ella123\HyperfThrottle\Storage\RedisStorage;

return [
    'storage' => RedisStorage::class,
    'maxAttempts' => 60,  // 在指定时间内允许的最大请求次数
    'decaySeconds' => 60,  // 单位时间（单位：s）
    'key' => '',  // 具体的计数器的 key
    'generateKeyCallable' => [],  // 生成计数器 key 的方法
    'tooManyAttemptsCallback' => [],  // 当触发到最大请求次数时的回调方法
];
