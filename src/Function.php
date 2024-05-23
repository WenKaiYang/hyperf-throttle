<?php

namespace Ella123\HyperfThrottle;

use Ella123\HyperfThrottle\Exception\ThrottleException;
use Ella123\HyperfThrottle\Handler\ThrottleHandler;
use Hyperf\Context\ApplicationContext;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * @throws ContainerExceptionInterface
 * @throws NotFoundExceptionInterface
 * @throws ThrottleException
 */
function throttle(
    string $rateLimits = '30,60',
    string $prefix = '',
    string $key = '',
    mixed  $generateKeyCallable = [],
    mixed  $tooManyAttemptsCallback = []
): void
{
    if (!ApplicationContext::hasContainer()) {
        throw new \RuntimeException('The application context lacks the container.');
    }
    $container = ApplicationContext::getContainer();
    $instance = $container->get(ThrottleHandler::class);

    $rates = array_map('intval', array_filter(explode(',', $rateLimits)));
    list($maxAttempts, $decaySeconds) = $rates;

    $instance->handle($maxAttempts, $decaySeconds, $prefix, $key, $generateKeyCallable, $tooManyAttemptsCallback);
}