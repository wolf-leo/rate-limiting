<?php

declare(strict_types = 1);

namespace Wolfcode\RateLimiting\Driver;

use Wolfcode\RateLimiting\Common\Constant;

final class RedisHelper
{
    private \Redis $redis;

    public function __construct(?array $config = null)
    {
        if (empty($config['host'])) $config['host'] = '127.0.0.1';
        if (empty($config['port'])) $config['port'] = 6379;
        if (empty($config['expire'])) $config['expire'] = 0;
        if (empty($config['prefix'])) $config['prefix'] = Constant::RATE_KEY_PREFIX;
        try {
            $this->redis = new \Redis();
            $this->redis->connect($config['host'], $config['port']);
            if (!empty($config['password'])) $this->redis->auth($config['password']);
            if (!empty($config['database'])) $this->redis->select($config['database']);
        }catch (\RedisException $e) {
            error_log("Redis connection error: " . $e->getMessage());
            throw $e;
        }
    }

    public function __destruct()
    {
        if ($this->redis) $this->redis->close();
    }

    /**
     * 设置键值对
     * @param string $key 键
     * @param mixed $value 值
     * @param int|null $expire 过期时间（秒），默认为 null 表示不过期
     * @return bool
     */
    public function set(string $key, mixed $value, ?int $expire = null): bool
    {
        if ($expire === null) {
            return $this->redis->set($key, $value);
        }else {
            return $this->redis->setex($key, $expire, $value);
        }
    }

    /**
     * 获取键对应的值
     * @param string $key 键
     * @return mixed
     */
    public function get(string $key)
    {
        return $this->redis->get($key);
    }

    /**
     * 删除指定的键
     * @param string|array $keys 单个键或键数组
     * @return int 删除的键的数量
     */
    public function delete(string|array $keys): int
    {
        return $this->redis->del($keys);
    }

    /**
     * 判断键是否存在
     * @param string $key 键
     * @return bool
     */
    public function exists(string $key): bool
    {
        return $this->redis->exists($key);
    }

    /**
     * 给键设置过期时间
     * @param string $key 键
     * @param int $expire 过期时间（秒）
     * @return bool
     */
    public function expire(string $key, int $expire): bool
    {
        return $this->redis->expire($key, $expire);
    }

    /**
     * 对键的值进行自增操作
     * @param string $key 键
     * @return int 自增后的值
     */
    public function incr(string $key): int
    {
        return $this->redis->incr($key);
    }
}