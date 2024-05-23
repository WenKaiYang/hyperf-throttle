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
use Closure;
use Ella123\HyperfThrottle\Exception\ThrottleException;
use Ella123\HyperfThrottle\Storage\StorageInterface;
use Hyperf\Context\Context;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use function Hyperf\Support\make;

class ThrottleHandler
{
    protected string $keyPrefix = 'throttle:';

    protected string $keySuffix = ':timer';

    public function __construct(
        public StorageInterface       $storage,
        protected ProceedingJoinPoint $proceedingJoinPoint
    )
    {
    }

    /**
     * 处理访问节流
     *
     * @param int $limit 在指定时间内允许的最大请求次数
     * @param int $timer 单位时间（s）
     * @param mixed $key 生成计数 key 的方法
     * @param mixed $callback 当触发到最大请求次数时回调方法
     * @throws ThrottleException
     */
    public function handle(
        int   $limit = 60,
        int   $timer = 60,
        mixed $key = null,
        mixed $callback = null
    ): void
    {
        // 计数器key
        $keyCounter = $this->keyPrefix() . $this->keySignature($key);

        $limit = max(1, $limit);
        if ($this->tooManyAttempts($keyCounter, $limit)) {
            $this->resolveTooManyAttempts($keyCounter, $limit, $callback);
        }

        $this->hit($keyCounter, max(1, $timer));

        $this->setHeaders($keyCounter, $limit);
    }

    /**
     * 清空所有的限流器.
     */
    public function clear(): bool
    {
        return $this->storage->clearPrefix($this->keyPrefix());
    }

    /**
     * @throws ThrottleException
     */
    protected function resolveTooManyAttempts(string $keyCounter, int $limit, mixed $callback): mixed
    {
        if ($callback) {
            return call_user_func($callback);
        }

        throw $this->buildException($keyCounter, $limit);
    }

    /**
     * 超过访问次数限制时，构建异常信息.
     *
     * @param string $keyCounter 计数器的 key
     * @param int $limit 在指定时间内允许的最大请求次数
     */
    protected function buildException(string $keyCounter, int $limit): ThrottleException
    {
        // 距离允许下一次请求还有多少秒
        $retryAfter = $this->getTimeUntilNextRetry($keyCounter);

        $this->setHeaders($keyCounter, $limit, $retryAfter);

        // 429 Too Many Requests
        return new ThrottleException();
    }

    /**
     * 设置返回头数据.
     *
     * @param string $keyCounter 计数器的 key
     * @param int $limit 在指定时间内允许的最大请求次数
     * @param null|int $retryAfter 距离下次重试请求需要等待的时间（s）
     */
    protected function setHeaders(string $keyCounter, int $limit, ?int $retryAfter = null): void
    {
        // 设置返回头数据
        $headers = $this->getHeaders(
            $limit,
            $this->calculateRemainingAttempts($keyCounter, $limit, $retryAfter),  // 计算剩余访问次数
            $retryAfter
        );

        $this->addHeaders($headers);
    }

    /**
     * 获取返回头数据.
     *
     * @param int $limit 在指定时间内允许的最大请求次数
     * @param int $remainingAttempts 在指定时间段内剩下的请求次数
     * @param null|int $retryAfter 距离下次重试请求需要等待的时间（s）
     * @return int[]
     */
    protected function getHeaders(int $limit, int $remainingAttempts, ?int $retryAfter = null): array
    {
        $headers = [
            'X-RateLimit-Limit' => $limit,  // 在指定时间内允许的最大请求次数
            'X-RateLimit-Remaining' => $remainingAttempts,  // 在指定时间段内剩下的请求次数
        ];

        if (!is_null($retryAfter)) {  // 只有当用户访问频次超过了最大频次之后才会返回以下两个返回头字段
            $headers['Retry-After'] = $retryAfter;  // 距离下次重试请求需要等待的时间（s）
            $headers['X-RateLimit-Reset'] = Carbon::now()->addSeconds($retryAfter)->getTimestamp();  // 距离下次重试请求需要等待的时间戳（s）
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
     * @param string $keyCounter 计数器的 key
     * @param int $limit 在指定时间内允许的最大请求次数
     */
    private function tooManyAttempts(string $keyCounter, int $limit): bool
    {
        $counterNumber = (int)$this->storage->get($keyCounter, 0);

        // 计时器不存在时，计数器则没有存在的意义
        if (!$this->storage->has($this->keyTimer($keyCounter))) {
            $this->storage->forget($keyCounter);
        } else {
            if ($counterNumber >= $limit) {
                return true;
            }
        }

        return false;
    }

    /**
     * 在指定时间内自增指定键的计数器.
     *
     * @param string $keyCounter 计数器的 key
     * @param int $timer 指定时间（s）
     */
    private function hit(string $keyCounter, int $timer): void
    {
        // 计时器的有效期时间戳
        $expirationTime = Carbon::now()->addSeconds($timer)->getTimestamp();
        // 计时器
        $this->storage->add($this->keyTimer($keyCounter), (string)$expirationTime, $timer);

        // 计数器
        $added = $this->storage->add($keyCounter, '0', $timer);

        $hits = $this->storage->increment($keyCounter);

        if ($added && $hits == 1) {
            // 证明是初始化
            $this->storage->put($keyCounter, '1', $timer);
        }
    }

    private function keyPrefix(): string
    {
        return $this->keyPrefix;
    }

    private function keyTimer(string $keyCounter): string
    {
        return $keyCounter . $this->keySuffix;
    }

    private function keySignature(mixed $key): string
    {
        if ($key instanceof Closure) {
            return (string)call_user_func($key);
        }
        $str = $this->proceedingJoinPoint->className;
        $str .= '_' . $this->proceedingJoinPoint->methodName;

        return sha1($str . '_' . $this->clientIp());
    }

    private function clientIp(): string
    {
        /** @var RequestInterface $request */
        $request = make(RequestInterface::class);

        return $request->getHeaderLine('X-Forwarded-For')
            ?: $request->getHeaderLine('X-Real-IP')
                ?: ($request->getServerParams()['remote_addr'] ?? '')
                    ?: '127.0.0.1';
    }

    /**
     * 计算距离允许下一次请求还有多少秒.
     *
     * @param string $keyCounter 计数器的 key
     */
    private function getTimeUntilNextRetry(string $keyCounter): int
    {
        $timer = (int)$this->storage->get($this->keyTimer($keyCounter));
        $nextRetry = $timer - Carbon::now()->getTimestamp();
        return max(0, $nextRetry);
    }

    /**
     * 计算剩余访问次数.
     *
     * @param string $keyCounter 计数器的 key
     * @param int $limit 在指定时间内允许的最大请求次数
     * @param null|int $retryAfter 距离下次重试请求需要等待的时间（s）
     */
    private function calculateRemainingAttempts(string $keyCounter, int $limit, ?int $retryAfter = null): int
    {
        if (is_null($retryAfter)) {
            $remain = $limit - (int)$this->storage->get($keyCounter, 0);
            return max(0, $remain);
        }

        return 0;
    }
}
