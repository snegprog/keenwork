<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use App\Keenwork\Makers\NoSql\RedisCreator;

readonly class RedisCache implements CacheInterface
{
    public function __construct(
        private RedisCreator $redisCreator,
    ) {
    }

    public function set(string $key, string $value, ?int $timeout = 60 * 60 * 24 * 8): bool
    {
        try {
            return $this->redisCreator->get()->set($key, $value, (int) $timeout);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @throws \Throwable
     * @throws \RedisException
     */
    public function remove(string $key): bool
    {
        return (bool) $this->redisCreator->get()->del($key);
    }

    /**
     * @throws \Throwable
     * @throws \RedisException
     */
    public function exists(string $key): bool
    {
        return (bool) $this->redisCreator->get()->exists($key);
    }

    /**
     * @throws \Throwable
     * @throws \RedisException
     */
    public function getAllKeys(string $pattern): array
    {
        $keys = $this->redisCreator->get()->keys($pattern);
        if (!is_array($keys)) {
            return [];
        }

        return $keys;
    }

    /**
     * @throws \Throwable
     * @throws \RedisException
     */
    public function get(string $key): ?string
    {
        $result = $this->redisCreator->get()->get($key);
        if (!$result || !is_string($result)) {
            return null;
        }

        return $result;
    }

    public function isConnect(): bool
    {
        return $this->redisCreator->isConnect();
    }

    /**
     * @throws \Throwable
     * @throws \RedisException
     */
    public function removeAll(): void
    {
        $this->redisCreator->get()->flushAll(true);
    }
}
