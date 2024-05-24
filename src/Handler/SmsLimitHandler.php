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

namespace Ella123\HyperfThrottle\Handler;

use Ella123\HyperfThrottle\Exception\SmsLimitException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use function Ella123\HyperfUtils\request;

class SmsLimitHandler
{
    /**
     * 异常回调.
     */
    public static function exceptionCallback(): SmsLimitException
    {
        return new SmsLimitException();
    }

    /**
     * 生成 Key.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function generateKey(): string
    {
        return md5(json_encode(request()->post() + request()->query()));
    }
}
