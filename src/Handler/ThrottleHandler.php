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

use Carbon\Carbon;
use Ella123\HyperfThrottle\Exception\ThrottleException;
use Hyperf\Context\Context;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use RedisException;

use function Ella123\HyperfUtils\realIp;
use function Ella123\HyperfUtils\redis;

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
        // 获取Key
        $key = $this->getKey($key);
        // 限制频次
        $limit = $this->getLimit(limit: $limit);
        // 当前频次
        $frequency = $this->frequency($key, max(1, $timer));
        // 检查频次
        if ($this->tooManyAttempts($frequency, $limit)) {
            $this->resolveTooManyAttempts($key, $frequency, $limit, $callback);
        }

        $this->setHeaders($frequency, $limit);
    }

    /**
     * 清空所有的限流器.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface|RedisException
     */
    public function clear(): bool
    {
        $iterator = null;
        $key = $this->getPrefix() . '*';

        while ($iterator !== 0) {
            $keys = redis()->scan($iterator, $key, 10000);
            if ($keys) {
                redis()->del(...$keys);
            }
        }

        return true;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     * @throws ThrottleException
     */
    protected function resolveTooManyAttempts(string $key, int $frequency, int $limit, mixed $callback): mixed
    {
        if (is_array($callback)) {
            return call_user_func($callback);
        }

        throw $this->buildException($key, $frequency, $limit);
    }

    /**
     * 超过访问次数限制时，构建异常信息.
     *
     * @param int $frequency 计数器的 key
     * @param int $limit 在指定时间内允许的最大请求次数
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    protected function buildException(string $key, int $frequency, int $limit): ThrottleException
    {
        // 距离允许下一次请求还有多少秒
        $retry = $this->getTimeUntilNextRetry(key: $key);

        $this->setHeaders($frequency, $limit, $retry);

        // 429 Too Many Requests
        return new ThrottleException();
    }

    /**
     * 设置返回头数据.
     *
     * @param int $limit 在指定时间内允许的最大请求次数
     * @param null|int $retry 距离下次重试请求需要等待的时间（s）
     */
    protected function setHeaders(int $frequency, int $limit, ?int $retry = null): void
    {
        // 设置返回头数据
        $headers = $this->getHeaders(
            limit: $limit,
            remain: $this->calculateRemain(frequency: $frequency, limit: $limit, retry: $retry),  // 计算剩余访问次数
            retry: $retry
        );

        $this->addHeaders($headers);
    }

    /**
     * 获取返回头数据.
     *
     * @param int $limit 在指定时间内允许的最大请求次数
     * @param int $remain 在指定时间段内剩下的请求次数
     * @param null|int $retry 距离下次重试请求需要等待的时间（s）
     * @return int[]
     */
    protected function getHeaders(int $limit, int $remain, ?int $retry = null): array
    {
        $headers = [
            'X-RateLimit-Limit' => $limit,  // 在指定时间内允许的最大请求次数
            'X-RateLimit-Remaining' => $remain,  // 在指定时间段内剩下的请求次数
        ];

        if (! is_null($retry)) {  // 只有当用户访问频次超过了最大频次之后才会返回以下两个返回头字段
            $headers['X-RateLimit-Retry'] = $retry;  // 距离下次重试请求需要等待的时间（s）
            $headers['X-RateLimit-Reset'] = Carbon::now()->addSeconds($retry)->getTimestamp();  // 距离下次重试请求需要等待的时间戳（s）
        }

        return $headers;
    }

    /**
     * 添加返回头信息.
     */
    protected function addHeaders(array $headers = []): void
    {
        $response = Context::get(ResponseInterface::class);

        foreach ($headers as $key => $header) {
            $response = $response->withHeader($key, $header);
        }

        Context::set(ResponseInterface::class, $response);
    }

    /**
     * 判断访问次数是否已经达到了临界值
     *
     * @param int $limit 在指定时间内允许的最大请求次数
     */
    private function tooManyAttempts(int $frequency, int $limit): bool
    {
        return $frequency > $limit;
    }

    /**
     * 在指定时间内自增指定键的计数器.
     *
     * @param string $key 计数器的 key
     * @param int $timer 指定时间（s）
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    private function frequency(string $key, int $timer): int
    {
        if (! redis()->get($key)) {
            redis()->setex($key, $timer, 0);
        }
        return (int) redis()->incr($key);
    }

    private function getPrefix(): string
    {
        return $this->keyPrefix;
    }

    private function getKeyTimer(string $key): string
    {
        return $key . $this->keySuffix;
    }

    /**
     * 计算距离允许下一次请求还有多少秒.
     *
     * @param string $key 计数器的 key
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    private function getTimeUntilNextRetry(string $key): int
    {
        $timer = (int) redis()->get($this->getKeyTimer($key));
        return max(0, $timer - Carbon::now()->getTimestamp());
    }

    /**
     * 计算剩余访问次数.
     *
     * @param int $limit 在指定时间内允许的最大请求次数
     * @param null|int $retry 距离下次重试请求需要等待的时间（s）
     */
    private function calculateRemain(int $frequency, int $limit, ?int $retry = null): int
    {
        if (is_null($retry)) {
            return max(0, $limit - $frequency);
        }

        return 0;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getKey(mixed $key): string
    {
        return $this->keyPrefix . $this->getSignature($key);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getSignature(mixed $key): string
    {
        if (is_array($key)) {
            return (string) call_user_func($key);
        }
        return sha1($key . '_' . realIp());
    }

    private function getLimit(int $limit)
    {
        return max(1, $limit);
    }
}
