## hyperf-throttle

> 适配 [hyperf](https://hyperf.wiki/) 框架的请求频率限流器。功能类似于 [laravel](https://laravel.com/)
> 框架的 [throttle](https://laravel.com/docs/7.x/middleware) 中间件。

### 安装依赖

```shell
composer require ella123/hyperf-throttle
```

### 配置说明

| 配置       | 默认值  | 说明             |
|----------|------|----------------|
| limit    | 60   | 限制频次           |
| timer    | 60   | 时间周期（单位：s）     |
| key      | null | 标识Key(支持自定义回调) | 
| callback | null | 超频回调(支持自定义回调)  |

### 使用实例

--- 

* 支持类
* 支持方法
* 支持同时，不同配置共同启效

```php
/**
 * 频率限制
 */
#[\Ella123\HyperfThrottle\Annotation\Throttle(limit: 60,timer: 60)]
class A {
    #[\Ella123\HyperfThrottle\Annotation\Throttle(limit: 60,timer: 60)] 
    public function name() {
    }
}

/**
 * 重复提交
 */
#[\Ella123\HyperfThrottle\Annotation\Resubmit(limit: 1,timer: 60)]
class B {
    #[\Ella123\HyperfThrottle\Annotation\Resubmit(limit: 1,timer: 60)]
    public function submit() {
    }
}

/**
 * 短信限制(支持定义不同规则)
 */
#[\Ella123\HyperfThrottle\Annotation\SmsLimit(limit: 1,timer: 60)]
#[\Ella123\HyperfThrottle\Annotation\SmsLimit(limit: 5,timer: 3600)]
#[\Ella123\HyperfThrottle\Annotation\SmsLimit(limit: 15,timer: 86400)]
class C {
    #[\Ella123\HyperfThrottle\Annotation\SmsLimit(limit: 1,timer: 60)]
    #[\Ella123\HyperfThrottle\Annotation\SmsLimit(limit: 5,timer: 3600)]
    #[\Ella123\HyperfThrottle\Annotation\SmsLimit(limit: 15,timer: 86400)]
    public function send() {
    }
}
```

