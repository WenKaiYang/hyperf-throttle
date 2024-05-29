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
use Hyperf\Context\Context;
use Hyperf\HttpServer\Contract\RequestInterface;
use RuntimeException;

class SmsLimitHandler
{
    /**
     * @throws SmsLimitException
     */
    public static function exceptionSmsMinuteCallback(string $message = 'SMS minute limit.'): void
    {
        throw new SmsLimitException(message: $message);
    }

    /**
     * @throws SmsLimitException
     */
    public static function exceptionSmsHourCallback(string $message = 'SMS hour limit.'): void
    {
        throw new SmsLimitException(message: $message);
    }

    /**
     * @throws SmsLimitException
     */
    public static function exceptionSmsDayCallback(string $message = 'SMS day limit.'): void
    {
        throw new SmsLimitException(message: $message);
    }

    /**
     * 生成 Key.
     */
    public static function generateKey(): string
    {
        $request = Context::get(RequestInterface::class);
        if (! $request) {
            throw new RuntimeException('No request context');
        }
        return md5(string: $request->input('phone')
            ?: $request->input('mobile')
                ?: $request->input('tell')
                    ?: (string) json_encode($request->all()));
    }
}
