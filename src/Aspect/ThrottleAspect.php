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
use Ella123\HyperfThrottle\Handler\ThrottleHandler;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Exception\Exception;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RedisException;

class ThrottleAspect extends AbstractAspect
{
    public $annotations = [
        ThrottleAnnotation::class,
        ResubmitAnnotation::class,
        SmsMinuteDayAnnotation::class,
        SmsHourLimitAnnotation::class,
        SmsMinuteLimitAnnotation::class,
    ];
    protected RedisProxy $redis;
    /**
     * @var RequestInterface|mixed
     */
    private RequestInterface $request;
    /**
     * @var ResponseInterface|mixed
     */
    private ResponseInterface $response;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(
        ContainerInterface $container,
        RedisFactory       $factory,
        ConfigInterface    $config
    )
    {
        $this->redis = $factory->get($config->get('throttle.redis', 'default'));
        $this->request = $container->get(RequestInterface::class);
        $this->response = $container->get(ResponseInterface::class);
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint): mixed
    {
        $metadata = $proceedingJoinPoint->getAnnotationMetadata();

        /** @var ThrottleHandler $handler */
        $handler = make(ThrottleHandler::class, [
            $this->request,
            $this->response,
            $this->redis,
        ]);
        $place = $proceedingJoinPoint->className;
        if ($metadata->class) {
            // class 上的注解
            foreach ($metadata->class as $class => $annotation) {
                if ($annotation instanceof ThrottleInterface) {
                    $place .= '#' . $class;
                    $handler->execute(
                        place: $place,
                        limit: $annotation->limit,
                        timer: $annotation->timer,
                        key: $annotation->key,
                        callback: $annotation->callback
                    );
                }
            }
        }
        if ($metadata->method) {
            $place .= '@' . $proceedingJoinPoint->methodName;
            // method 上的注解
            foreach ($metadata->method as $class => $annotation) {
                if ($annotation instanceof ThrottleInterface) {
                    $place .= '#' . $class;
                    $handler->execute(
                        place: $place,
                        limit: $annotation->limit,
                        timer: $annotation->timer,
                        key: $annotation->key,
                        callback: $annotation->callback
                    );
                }
            }
        }

        return $proceedingJoinPoint->process();
    }
}
