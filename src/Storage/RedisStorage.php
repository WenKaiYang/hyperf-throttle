<?php

namespace Ella123\HyperfThrottle\Storage;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Hyperf\Redis\Redis;
use Psr\Container\NotFoundExceptionInterface;

class RedisStorage implements StorageInterface
{
    /**
     * @var Redis
     */
    protected mixed $redis;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        $this->redis = $container->get(Redis::class);
    }

    /**
     * @throws \RedisException
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($key);

        if (false === $value) {
            return $default;
        }

        return $value;
    }

    /**
     * @throws \RedisException
     */
    public function put(string $key, string $value, ?int $ttl = null): bool
    {
        if ($ttl !== null) {
            return $this->redis->setex($key, max(1, $ttl), $value);
        }

        return $this->redis->set($key, $value);
    }

    /**
     * @throws \RedisException
     */
    public function add(string $key, string $value, ?int $ttl = null): bool
    {
        if ($ttl !== null) {
            return $this->redis->set($key, $value, ['NX', 'EX' => $ttl]);
        }

        return $this->redis->set($key, $value, ['NX']);
    }

    /**
     * @throws \RedisException
     */
    public function increment(string $key, int $value = 1): int
    {
        return $this->redis->incrBy($key, $value);
    }

    /**
     * @throws \RedisException
     */
    public function forget(string $key): bool
    {
        return (bool)$this->redis->del($key);
    }

    /**
     * @throws \RedisException
     */
    public function has(string $key): bool
    {
        return (bool)$this->redis->exists($key);
    }

    /**
     * @throws \RedisException
     */
    public function clearPrefix(string $prefix): bool
    {
        $iterator = null;
        $key = $prefix . '*';

        while ($iterator !== 0) {
            $keys = $this->redis->scan($iterator, $key, 10000);
            if ($keys) {
                $this->redis->del(...$keys);
            }
        }

        return true;
    }

}