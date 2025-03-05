<?php

declare(strict_types = 1);

namespace Wolfcode\RateLimiting\Attributes;

use Attribute;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
final class RateLimitingMiddleware
{
    /**
     * RateLimiter constructor.
     * @param mixed $key Key
     * @param int $seconds 过期时间（秒）
     * @param int $limit 时间内最大请求次数
     * @param string $message 提示语
     * @param array $args 额外参数
     */
    public function __construct(public mixed $key, public int $seconds = 1, public int $limit = 1, public string $message = '请求过于频繁', public array $args = []) {}
}