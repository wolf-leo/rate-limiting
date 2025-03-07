# PHP rate-limiting

## 1. Require

> php >= 8.1
>
> Redis

## 2. Install

```shell
composer require "wolfcode/rate-limiting"
```

## 3. Usage

```php
use Wolfcode\RateLimiting\Attributes\RateLimitingMiddleware;

class Test
{
    // 每1秒只能请求1次
    // Only one request can be made per second
    #[RateLimitingMiddleware(key: 'test', seconds: 1, limit: 1, message: '请求过于频繁~')]
    public function index(Request $request): string
    
    // 每60秒只能请求100次
    // Only 100 requests can be made every 60 seconds
    #[RateLimitingMiddleware(key: 'test', seconds: 60, limit: 100, message: '你好快啊，我好喜欢~')]
    public function index(Request $request): string
    
    // 每3秒只能请求10次 key可以使用数组回调方式 参考下方例子
    // Only 10 key requests can be made every 3 seconds. An array callback method can be used, as shown in the example below
    #[RateLimitingMiddleware(key: [Some:class,'getIp'], seconds: 3, limit: 10, message: '我记住你了~')]
    public function index(Request $request): string
    
    // 每3秒只能请求10次 key可以使用数组回调方式 参考下方例子
    // Only 10 key requests can be made every 3 seconds. An array callback method can be used, as shown in the example below
    #[RateLimitingMiddleware(key: [Some:class,'customIp'], seconds: 3, limit: 10, message: '我记住你了~'),args:[__METHOD__])]
    public function index(Request $request): string
}


// 需要自行创建一个 Some 类 并且存在静态方法 getIp
// Need to create a Some class on your own and have a static method getIp
class Some
{
    public static function getIp(): string
    {
        return $request->ip();
    }
    
    public static function customIp(...$args): string
    {
        return $args[0] . $request->ip();
    }
}
```

## 4. Suggestion

> 可以在 .env 文件中设置一个 RATE_LIMITING_STATUS 开关，来控制是否开启限流
>
> 建议在中间件中使用
>
> You can set a RATE_LIMITING-STATUS switch in the. env file to control whether to enable current limiting
>
> Suggest using it in middleware

## 5. Example

#### ThinkPHP8.1

```php
<?php

namespace app\admin\middleware;

use app\common\traits\JumpTrait;
use app\Request;
use Closure;
use Wolfcode\RateLimiting\Bootstrap;

class RateLimiting
{
    use JumpTrait;

    /**
     * 启用限流器需要开启Redis
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // 是否启用限流器
        if (!env('RATE_LIMITING_STATUS', false)) return $next($request);

        $controller      = $request->controller();
        $module          = app('http')->getName();
        $appNamespace    = config('app.app_namespace');
        $controllerClass = "app\\{$module}\\controller\\{$controller}{$appNamespace}";
        $controllerClass = str_replace('.', '\\', $controllerClass);
        $action          = $request->action();
        try {
            Bootstrap::init($controllerClass, $action, [
                # Redis 相关配置
                'host'     => env('REDIS_HOST', '127.0.0.1'),
                'port'     => (int)env('REDIS_PORT', 6379),
                'password' => env('REDIS_PASSWORD', ''),
                'prefix'   => env('REDIS_PREFIX', ''),
                'database' => (int)env('REDIS_DATABASE', 0),
            ]);
        }catch (\Throwable $exception) {
            $this->error($exception->getMessage());
        }
        return $next($request);
    }
}
```