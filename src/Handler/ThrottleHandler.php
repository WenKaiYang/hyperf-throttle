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

use Closure;
use Ella123\HyperfThrottle\Exception\ThrottleException;
use Hyperf\Context\Context;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Stringable\Str;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use RedisException;

use function Ella123\HyperfUtils\redis;
use function Hyperf\Support\make;

class ThrottleHandler
{
    protected string $keyPrefix = 'throttle:';

    protected string $keySuffix = ':timer';

    /**
     * 处理访问节流
     *
     * @param int $limit 在指定时间内允许的最大请求次数
     * @param int $timer 单位时间（s）
     * @param mixed $key 生成计数 key 的方法
     * @param mixed $callback 当触发到最大请求次数时回调方法
     * @throws ThrottleException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    public function handle(
        int $limit = 60,
        int $timer = 60,
        mixed $key = null,
        mixed $callback = null
    ): void {
        // 计数器
        $key = $this->getKey($key);
        // 限制频率
        $limit = max(1, $limit);
        // 检测限制
        redis()->setex($key . ':' . Str::uuid(), $timer, 1);
        // 累计数量
        $count = count(redis()->keys($key . ':*'));
        // 设置响应头
        $this->setHeaders(limit: $limit, count: $count);
        // 异常回调
        $count >= $limit && $this->callbackException($callback);
    }

    /**
     * @throws ThrottleException
     */
    protected function callbackException(mixed $callback = null): mixed
    {
        if ($callback instanceof Closure) {
            return call_user_func($callback);
        }
        // 429 Too Many Requests
        throw new ThrottleException();
    }

    /**
     * 设置返回头数据.
     *
     * @param int $limit 在指定时间内允许的最大请求次数
     */
    protected function setHeaders(int $limit, int $count = 0): void
    {
        // 设置返回头数据
        $this->addHeaders($this->getHeaders(
            limit: $limit,
            remain: max(0, $limit - $count)
        ));
    }

    /**
     * 获取返回头数据.
     *
     * @param int $limit 在指定时间内允许的最大请求次数
     * @return int[]
     */
    protected function getHeaders(int $limit, int $remain): array
    {
        return [
            'X-RateLimit-Limit' => $limit,  // 在指定时间内允许的最大请求次数
            'X-RateLimit-Remain' => $remain,  // 在指定时间段内剩下的请求次数
        ];
    }

    /**
     * 添加返回头信息.
     */
    protected function addHeaders(array $headers = []): void
    {
        /** @var ResponseInterface $response */
        $response = Context::get(ResponseInterface::class);

        foreach ($headers as $key => $header) {
            $response = $response->withHeader($key, $header);
        }

        Context::set(ResponseInterface::class, $response);
    }

    private function getKey(mixed $key): string
    {
        return $this->keyPrefix . $this->getSignature($key);
    }

    private function getSignature(mixed $key): string
    {
        if ($key instanceof Closure) {
            return (string) call_user_func($key);
        }
        return sha1($key . '_' . $this->getRealIp());
    }

    private function getRealIp(): string
    {
        /** @var RequestInterface $request */
        $request = make(RequestInterface::class);

        return $request->getHeaderLine('X-Forwarded-For')
            ?: $request->getHeaderLine('X-Real-IP')
                ?: ($request->getServerParams()['remote_addr'] ?? '')
                    ?: '127.0.0.1';
    }
}
