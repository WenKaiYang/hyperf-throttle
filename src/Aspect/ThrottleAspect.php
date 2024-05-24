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

namespace Ella123\HyperfThrottle\Aspect;

use Ella123\HyperfThrottle\Annotation\Resubmit as ResubmitAnnotation;
use Ella123\HyperfThrottle\Annotation\SmsDayLimit as SmsMinuteDayAnnotation;
use Ella123\HyperfThrottle\Annotation\SmsHourLimit as SmsHourLimitAnnotation;
use Ella123\HyperfThrottle\Annotation\SmsMinuteLimit as SmsMinuteLimitAnnotation;
use Ella123\HyperfThrottle\Annotation\Throttle as ThrottleAnnotation;
use Ella123\HyperfThrottle\Annotation\ThrottleInterface;
use Ella123\HyperfThrottle\Exception\InvalidArgumentException;
use Ella123\HyperfThrottle\Exception\ThrottleException;
use Ella123\HyperfThrottle\Handler\ThrottleHandler;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Exception\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RedisException;

use function Hyperf\Support\make;

#[Aspect]
class ThrottleAspect extends AbstractAspect
{
    public array $annotations = [
        ThrottleAnnotation::class,
        ResubmitAnnotation::class,
        SmsMinuteDayAnnotation::class,
        SmsHourLimitAnnotation::class,
        SmsMinuteLimitAnnotation::class,
    ];

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedisException|ThrottleException
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint): mixed
    {
        $metadata = $proceedingJoinPoint->getAnnotationMetadata();

        /** @var ThrottleHandler $handler */
        $handler = make(ThrottleHandler::class);
        $key = $proceedingJoinPoint->className;
        if ($metadata->class) {
            // class 上的注解
            foreach ($metadata->class as $class => $annotation) {
                if ($annotation instanceof ThrottleInterface) {
                    $key .= '#' . $class;
                    $handler->execute(
                        limit: $annotation->limit,
                        timer: $annotation->timer,
                        key: $annotation->key ?: $key,
                        callback: $annotation->callback
                    );
                }
            }
        }
        if ($metadata->method) {
            $key .= '@' . $proceedingJoinPoint->methodName;
            // method 上的注解
            foreach ($metadata->method as $class => $annotation) {
                if ($annotation instanceof ThrottleInterface) {
                    $key .= '#' . $class;
                    $handler->execute(
                        limit: $annotation->limit,
                        timer: $annotation->timer,
                        key: $annotation->key ?: $key,
                        callback: $annotation->callback
                    );
                }
            }
        }

        return $proceedingJoinPoint->process();
    }
}
