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

namespace Ella123\HyperfThrottle;

use Ella123\HyperfThrottle\Aspect\ThrottleAspect;
use Ella123\HyperfThrottle\Storage\RedisStorage;
use Ella123\HyperfThrottle\Storage\StorageInterface;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                StorageInterface::class => RedisStorage::class,
            ],
            'commands' => [],
            'listeners' => [],
            'aspects' => [
                ThrottleAspect::class,
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => '访问速率限流器配置文件',
                    'source' => __DIR__ . '/../publish/throttle_requests.php',
                    'destination' => BASE_PATH . '/config/autoload/throttle_requests.php',
                ],
            ],
        ];
    }
}
