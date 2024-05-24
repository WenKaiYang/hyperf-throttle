<?php

namespace Ella123\HyperfThrottle\Handler;


use Ella123\HyperfThrottle\Exception\ResubmitException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use function Ella123\HyperfUtils\request;

class ResubmitHandler
{
    /**
     * 异常回调
     * @return ResubmitException
     */
    public function exceptionCallback(): ResubmitException
    {
        // 429 Repeated submission
        return new ResubmitException();
    }

    /**
     * 生成 Key
     * @return string
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function generateKey(): string
    {
        return md5(json_encode(request()->post() + request()->query()));
    }
}