# PHP限流器

## 1.要求

> php >= 8.1
>
> Redis 服务

## 2. 安装

```shell
composer require "wolfcode/rate-limiting"
```

# 3. 文档

```php
use Wolfcode\RateLimiting\RateLimiting;

class Test
{
    // 每1秒只能请求1次
    #[RateLimiting(key: 'test', seconds: 1, limit: 1, message: '请求过于频繁~')]
    public function index(Request $request): string
    
    // 每60秒只能请求100次
    #[RateLimiting(key: 'test', seconds: 60, limit: 100, message: '你好快啊，我好喜欢~')]
    public function index(Request $request): string
    
    // 每3秒只能请求10次 key可以使用数组回调方式 参考下方例子
    #[RateLimiting(key: [Some:class,'getIp'], seconds: 10, limit: 3, message: '我记住你了~')]
    public function index(Request $request): string
}


// 需要自行创建一个 Some 类 并且存在静态方法 getIp
class Some
{
    public static function getIp(): string
    {
        return $request->ip();
    }
}
```

# 4.建议

> 可以在 .env 文件中设置一个 RATE_LIMITING_STATUS 开关，来控制是否开启限流
>
> 建议在中间件中使用

# 5.示例

#### 以下示例在ThinkPHP8.1中使用

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
                'port'     => env('REDIS_PORT, 6379'),
                'password' => env('REDIS_PASSWORD', ''),
                'prefix'   => env('REDIS_PREFIX', ''),
                'database' => env('REDIS_DATABASE', 0),
            ]);
        }catch (\Throwable $exception) {
            $this->error($exception->getMessage());
        }
        return $next($request);
    }
}
```