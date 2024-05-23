## hyperf-throttle

> 适配 [hyperf](https://hyperf.wiki/) 框架的请求频率限流器。功能类似于 [laravel](https://laravel.com/)
> 框架的 [throttle](https://laravel.com/docs/7.x/middleware) 中间件。

### 安装依赖

```shell
composer require ella123/hyperf-throttle
```

### 发布配置

```shell
php bin/hyperf.php vendor:publish ella123/hyperf-throttle
```

### 配置说明

| 配置       | 默认值                 | 说明                                |
|----------|---------------------|-----------------------------------|
| storage  | RedisStorage::class | 数据存储驱动                            |
| limit    | 60                  | 在指定时间内允许的最大请求次数                   |
| timer    | 60                  | 单位时间（单位：s）                        |
| key      | null                | 默认以当前 类名（方法）+ IP                  | 
| callback | null                | 异常回调方法（默认会抛出 `ThrottleException`） |

### 使用实例



