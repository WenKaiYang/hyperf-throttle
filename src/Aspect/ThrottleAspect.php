<?php

namespace Ella123\HyperfThrottle\Aspect;

use Ella123\HyperfThrottle\Annotation\Throttle as ThrottleAnnotation;
use Ella123\HyperfThrottle\Exception\InvalidArgumentException;
use Ella123\HyperfThrottle\Exception\ThrottleException;
use Ella123\HyperfThrottle\Handler\ThrottleHandler;
use Ella123\HyperfThrottle\Storage\RedisStorage;
use Ella123\HyperfThrottle\Storage\StorageInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Exception\Exception;
use Hyperf\Engine\Contract\Http\V2\RequestInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use function Hyperf\Support\make;
use function Hyperf\Tappable\tap;

class ThrottleAspect extends AbstractAspect
{
    public array $annotations = [
        ThrottleAnnotation::class
    ];
    protected array $annotationProperty;

    protected array $config;


    public function __construct(
        ConfigInterface              $config,
        protected ContainerInterface $container
    ) {
        $this->annotationProperty = get_object_vars(new ThrottleAnnotation());
        $this->config = $this->parseConfig($config);
    }

    /**
     * @param ProceedingJoinPoint $proceedingJoinPoint
     * @return mixed
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ThrottleException
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint): mixed
    {
        $annotation = $this->getWeightingAnnotation($this->getAnnotations($proceedingJoinPoint));

        $throttleHandlerInstance = make(
            ThrottleHandler::class,
            [$this->container->get(RequestInterface::class), $this->getStorageDriver()]
        );
        /** @var ThrottleHandler $throttleHandlerInstance */
        $throttleHandlerInstance->handle(
            $annotation->maxAttempts,
            $annotation->decaySeconds,
            $annotation->prefix,
            $annotation->key,
            $annotation->generateKeyCallable,
            $annotation->tooManyAttemptsCallback
        );

        return $proceedingJoinPoint->process();
    }

    /**
     * @param array $annotations
     * @return ThrottleAnnotation
     */
    public function getWeightingAnnotation(array $annotations): ThrottleAnnotation
    {
        $property = array_merge($this->annotationProperty, $this->getConfig());

        /*** @var null|ThrottleAnnotation $annotation */
        foreach ($annotations as $annotation) {
            if (! $annotation) {
                continue;
            }

            $property = array_merge($property, array_filter(get_object_vars($annotation)));
        }

        return tap(new ThrottleAnnotation(), static function (ThrottleAnnotation $ThrottleAnnotation) use ($property) {
            foreach ($property as $k => $v) {
                $ThrottleAnnotation->{$k} = $v;
            }
        });
    }

    /**
     * @param ProceedingJoinPoint $proceedingJoinPoint
     * @return array
     */
    public function getAnnotations(ProceedingJoinPoint $proceedingJoinPoint): array
    {
        $metadata = $proceedingJoinPoint->getAnnotationMetadata();  // 得到注解上的元数据
        return [
            $metadata->class[ThrottleAnnotation::class] ?? null,  // 类上面的注解元数据
            $metadata->method[ThrottleAnnotation::class] ?? null  // 类方法上面的注解元数据
        ];
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param ConfigInterface $config
     * @return array
     */
    private function parseConfig(ConfigInterface $config): array
    {
        if ($config->has('throttle_requests')) {
            return $config->get('throttle_requests');
        }

        return [];
    }

    /**
     * @return StorageInterface
     * @throws InvalidArgumentException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getStorageDriver(): StorageInterface
    {
        $config = $this->getConfig();

        $driverClass = $config['storage'] ?? RedisStorage::class;
        if (! $this->container->has($driverClass)) {
            throw new InvalidArgumentException(sprintf('The storage driver class [%s] is not exists.', $driverClass));
        }

        $instance = $this->container->get($driverClass);
        if (! $instance instanceof StorageInterface) {
            throw new InvalidArgumentException(sprintf('The storage driver class [%s] is invalid.', $driverClass));
        }

        return $instance;
    }
}