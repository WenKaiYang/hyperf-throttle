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

use Hyperf\Context\Context;
use Hyperf\HttpServer\Contract\RequestInterface;
use RuntimeException;

class ResubmitHandler
{
    /**
     * 生成 Key.
     */
    public static function generateKey(): string
    {
        $request = Context::get(RequestInterface::class);
        if (! $request) {
            throw new RuntimeException('No request context');
        }
        return md5(json_encode($request->post() + $request->query()));
    }
}
