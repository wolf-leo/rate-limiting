<?php

declare(strict_types = 1);

namespace Wolfcode\RateLimiting;

use Wolfcode\RateLimiting\Attributes\RateLimitingMiddleware;
use Wolfcode\RateLimiting\Common\Constant;
use Wolfcode\RateLimiting\Driver\RedisHelper;
use Wolfcode\RateLimiting\Exception\RateLimitingException;

class Bootstrap
{

    public static function init(string $controllerClass, string $action, ?array $options = [])
    {
        $reflection             = new \ReflectionClass($controllerClass);
        $methodReflection       = $reflection->getMethod($action);
        $rateLimitingAttributes = $methodReflection->getAttributes(RateLimitingMiddleware::class);
        $prefix                 = !empty($options['prefix']) ? $options['prefix'] : Constant::RATE_KEY_PREFIX;
        try {
            $redis = new RedisHelper($options);
        }catch (\RedisException) {
            throw new RateLimitingException('Redis connection exception');
        }
        foreach ($rateLimitingAttributes as $rateLimitingAttribute) {
            $rateLimiting = $rateLimitingAttribute->newInstance();
            $key          = $rateLimiting->key;
            if (empty($key)) throw new RateLimitingException("Parameter key cannot be empty");
            if (is_array($key)) $key = $key(...$rateLimiting->args);
            $key   = $prefix . ':' . $key;
            $count = $redis->incr($key);
            if ($count >= 1) {
                $redis->expire($key, $rateLimiting->seconds);
            }
            if ($count > $rateLimiting->limit) {
                throw new RateLimitingException($rateLimiting->message);
            }
        }
    }

}