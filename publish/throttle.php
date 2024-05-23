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
return [
    'limit' => 60,  // 单位时间内的允许频次
    'timer' => 60,  // 单位时间（单位：s）
    'key' => null,  // 具体的计数器的 key
    'callback' => null,  // 当触发最大频次的回调方法
];
