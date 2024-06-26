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

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [],
            'commands' => [],
            'listeners' => [],
            'aspects' => [
                ThrottleAspect::class,
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for throttle.',
                    'source' => __DIR__ . '/../publish/throttle.php',
                    'destination' => BASE_PATH . '/config/autoload/throttle.php',
                ],
            ],
        ];
    }
}
