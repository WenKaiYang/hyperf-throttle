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
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Redis\RedisProxy;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use RedisException;

class ThrottleHandler
{
    protected string $keyPrefix = 'throttle:';

    protected string $keySuffix = ':timer';


    public function __construct(
        protected RequestInterface $request,
        protected RedisProxy       $redis
    )
    {
    }

    /**
     * 清空所有的限流器.
     *
     * @throws RedisException
     */
    public function clear(): bool
    {
        $iterator = null;
        $key = $this->getPrefix() . '*';

        while ($iterator !== 0) {
            $keys = $this->redis->scan($iterator, $key, 10000);
            if ($keys) {
                $this->redis->del(...$keys);
            }
        }

        return true;
    }

    public function getPrefix(): string
    {
        return $this->keyPrefix;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     * @throws ContainerExceptionInterface
     */
    public function execute(
        string $place,
        int    $limit = 60,
        int    $timer = 60,
        mixed  $key = null,
        mixed  $callback = null
    ): void
    {
        // 获取Key
        $key = $this->getKey($place, $key);
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

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getKey(string $place, mixed $key): string
    {
        return $this->keyPrefix . $this->getSignature($place, $key);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getSignature(string $place, mixed $key): string
    {
        if (is_array($key)) {
            return $place . '_' . $this->callback($key);
        }
        if (is_string($key)) {
            $key = $this->getInputKey($key);
        }
        return sha1($place . '_' . $key . '_' . $this->getRealIp());
    }

    public function getInputKey(string $key): string
    {
        return is_string($str = $this->request->input($key)) ? $str : $key;
    }

    public function getRealIp(): string
    {
        return $this->request->getHeaderLine('X-Forwarded-For')
            ?: $this->request->getHeaderLine('X-Real-IP')
                ?: ($this->request->getServerParams()['remote_addr'] ?? '')
                    ?: '127.0.0.1';
    }

    public function getLimit(int $limit)
    {
        return max(1, $limit);
    }

    /**
     * 在指定时间内自增指定键的计数器.
     *
     * @param string $key 计数器的 key
     * @param int $timer 指定时间（s）
     * @throws RedisException
     */
    public function frequency(string $key, int $timer): int
    {
        if (!$this->redis->get($key)) {
            $this->redis->setex($key, $timer, 0);
        }
        return (int)$this->redis->incr($key);
    }

    public function getTimer($timer)
    {
        return max(1, $timer);
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

        if (!is_null($retry)) {  // 只有当用户访问频次超过了最大频次之后才会返回以下两个返回头字段
            $headers['X-RateLimit-Retry'] = $retry;  // 距离下次重试请求需要等待的时间（s）
            $headers['X-RateLimit-Reset'] = Carbon::now()->addSeconds($retry)->getTimestamp();  // 距离下次重试请求需要等待的时间戳（s）
        }

        return $headers;
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
     * 添加返回头信息.
     */
    public function addHeaders(array $headers = []): static
    {
        $response = ApplicationContext::getContainer()->get(ResponseInterface::class);

        foreach ($headers as $key => $header) {
            $response = $response->withHeader($key, $header);
        }

        Context::set(ResponseInterface::class, $response);

        return $this;
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
            return $this->callback($callback);
        }

        throw $this->buildException(exception: $callback);
    }

    public function callback(array $callback)
    {
        $obj = make($callback[0]);
        $method = $callback[1];
        $params = count($callback) > 2 ? $callback[2] : [];
        return call_user_func_array([$obj, $method], (array)$params);
    }

    /**
     * 计算距离允许下一次请求还有多少秒.
     *
     * @param string $key 计数器的 key
     * @throws RedisException
     */
    public function getTimeUntilNextRetry(string $key): int
    {
        $timer = (int)$this->redis->get($this->getKeyTimer($key));
        return max(0, $timer - Carbon::now()->getTimestamp());
    }

    public function getKeyTimer(string $key): string
    {
        return $key . $this->keySuffix;
    }

    /**
     * 超过访问次数限制时，构建异常信息.
     */
    public function buildException(?string $exception = null): Exception
    {
        return $exception && class_exists($exception)
            ? new $exception()
            : new ThrottleException();
    }
}
