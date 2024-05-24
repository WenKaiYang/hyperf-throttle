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
use Ella123\HyperfThrottle\Annotation\Throttle as ThrottleAnnotation;
use Ella123\HyperfThrottle\Annotation\ThrottleInterface;
use Ella123\HyperfThrottle\Exception\InvalidArgumentException;
use Ella123\HyperfThrottle\Exception\ThrottleException;
use Ella123\HyperfThrottle\Handler\ThrottleHandler;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Exception\Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RedisException;

use function Hyperf\Support\make;

class ThrottleAspect extends AbstractAspect
{
    public array $annotations = [
        ResubmitAnnotation::class,
        ThrottleAnnotation::class,
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
        $annotationMetadata = $proceedingJoinPoint->getAnnotationMetadata();

        $key = $proceedingJoinPoint->className;
        if ($annotationMetadata->class) {
            // 类上的注解
            foreach ($annotationMetadata->class as $class => $annotation) {
                if ($annotation instanceof ThrottleInterface) {
                    $key .= '#' . $class;
                    make(ThrottleHandler::class)->handle(
                        limit: $annotation->limit,
                        timer: $annotation->timer,
                        key: $annotation->key ?: $key,
                        callback: $annotation->callback
                    );
                }
            }
        }
        if ($annotationMetadata->method) {
            $key .= '@' . $proceedingJoinPoint->methodName;
            // 方法上的注解
            foreach ($annotationMetadata->method as $class => $annotation) {
                if ($annotation instanceof ThrottleInterface) {
                    $key .= '#' . $class;
                    make(ThrottleHandler::class)->handle(
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
