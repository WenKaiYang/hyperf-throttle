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

use Ella123\HyperfThrottle\Exception\ThrottleException;
use Ella123\HyperfThrottle\Handler\ThrottleHandler;
use Hyperf\Context\ApplicationContext;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

/**
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 * @throws ThrottleException
 */
function throttle(
    string $rateLimits = '30,60',
    string $prefix = '',
    string $key = '',
    mixed $generateKeyCallable = [],
    mixed $tooManyAttemptsCallback = []
): void {
    if (! ApplicationContext::hasContainer()) {
        throw new RuntimeException('The application context lacks the container.');
    }
    $container = ApplicationContext::getContainer();
    $instance = $container->get(ThrottleHandler::class);

    $rates = array_map('intval', array_filter(explode(',', $rateLimits)));
    [$maxAttempts, $decaySeconds] = $rates;

    $instance->handle($maxAttempts, $decaySeconds, $prefix, $key, $generateKeyCallable, $tooManyAttemptsCallback);
}
