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

namespace Ella123\HyperfThrottle\Storage;

interface StorageInterface
{
    /**
     * Fetches a value from the cache.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store an item in the cache.
     */
    public function put(string $key, string $value, ?int $ttl = null): bool;

    /**
     * Store an item in the cache if the key does not exist.
     */
    public function add(string $key, string $value, ?int $ttl = null): bool;

    /**
     * Increment the value of an item in the cache.
     */
    public function increment(string $key, int $value = 1): int;

    /**
     * Remove an item from the cache.
     */
    public function forget(string $key): bool;

    /**
     * Determines whether an item is present in the cache.
     */
    public function has(string $key): bool;

    /**
     * Remove all has common prefix items from the cache.
     */
    public function clearPrefix(string $prefix): bool;
}
