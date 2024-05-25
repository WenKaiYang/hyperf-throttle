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
use Exception;
use Hyperf\Context\Context;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use RedisException;

use function Ella123\HyperfUtils\realIp;
use function Ella123\HyperfUtils\redis;
use function Hyperf\Support\make;

class ThrottleHandler
{
    protected string $keyPrefix = 'throttle:';

    protected string $keySuffix = ':timer';

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
     * @throws Exception
     */
    public function resolveTooManyAttempts(string $key, int $frequency, int $limit, mixed $callback): mixed
    {
        // 距离允许下一次请求还有多少秒
        $retry = $this->getTimeUntilNextRetry(key: $key);

        $this->setHeaders($frequency, $limit, $retry);

        if (is_array($callback)) {
            return call_user_func($callback);
        }

        throw $this->buildException(exception: $callback);
    }

    /**
     * 超过访问次数限制时，构建异常信息.
     */
    public function buildException(?string $exception = null): Exception
    {
        return $exception && class_exists($exception)
            ? make($exception)
            : new ThrottleException();
    }

    /**
     * 设置返回头数据.
     *
     * @param int $limit 在指定时间内允许的最大请求次数
     * @param null|int $retry 距离下次重试请求需要等待的时间（s）
     */
    public function setHeaders(int $frequency, int $limit, ?int $retry = null): static
    {
        // 设置返回头数据
        $headers = $this->getHeaders(
            limit: $limit,
            remain: $this->calculateRemain(frequency: $frequency, limit: $limit, retry: $retry),  // 计算剩余访问次数
            retry: $retry
        );

        $this->addHeaders($headers);

        return $this;
    }

    /**
     * 获取返回头数据.
     *
     * @param int $limit 在指定时间内允许的最大请求次数
     * @param int $remain 在指定时间段内剩下的请求次数
     * @param null|int $retry 距离下次重试请求需要等待的时间（s）
     * @return int[]
     */
    public function getHeaders(int $limit, int $remain, ?int $retry = null): array
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
    public function addHeaders(array $headers = []): static
    {
        $response = Context::get(ResponseInterface::class);

        foreach ($headers as $key => $header) {
            $response = $response->withHeader($key, $header);
        }

        Context::set(ResponseInterface::class, $response);

        return $this;
    }

    /**
     * 判断访问次数是否已经达到了临界值
     *
     * @param int $limit 在指定时间内允许的最大请求次数
     */
    public function tooManyAttempts(int $frequency, int $limit): bool
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
    public function frequency(string $key, int $timer): int
    {
        if (! redis()->get($key)) {
            redis()->setex($key, $timer, 0);
        }
        return (int) redis()->incr($key);
    }

    public function getPrefix(): string
    {
        return $this->keyPrefix;
    }

    public function getKeyTimer(string $key): string
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
    public function getTimeUntilNextRetry(string $key): int
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
    public function calculateRemain(int $frequency, int $limit, ?int $retry = null): int
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
    public function getKey(mixed $key): string
    {
        return $this->keyPrefix . $this->getSignature($key);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getSignature(mixed $key): string
    {
        if (is_array($key)) {
            return (string) call_user_func($key);
        }
        return sha1($key . '_' . realIp());
    }

    public function getLimit(int $limit)
    {
        return max(1, $limit);
    }

    public function getTimer($timer)
    {
        return max(1, $timer);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     * @throws ContainerExceptionInterface
     */
    public function execute(
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
        $frequency = $this->frequency($key, $this->getTimer($timer));
        // 检查频次
        if ($this->setHeaders($frequency, $limit)
            ->tooManyAttempts($frequency, $limit)) {
            $this->resolveTooManyAttempts($key, $frequency, $limit, $callback);
        }
    }
}
