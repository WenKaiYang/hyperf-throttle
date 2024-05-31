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
use Hyperf\Utils\ApplicationContext;
use RuntimeException;

class SmsLimitHandler
{
    /**
     * @throws SmsLimitException
     */
    public function exceptionCallback(string $message): void
    {
        throw new SmsLimitException(message: $message);
    }

    /**
     * 生成 Key.
     */
    public function generateKey(): string
    {
        $request = Context::get(RequestInterface::class)
            ?: ApplicationContext::getContainer()->get(RequestInterface::class);
        if (!$request) {
            throw new RuntimeException('No request context');
        }
        return md5(string: (string)$request->input('phone')
            ?: (string)$request->input('mobile')
                ?: (string)$request->input('tell')
                    ?: (string)json_encode($request->url()));
    }
}
