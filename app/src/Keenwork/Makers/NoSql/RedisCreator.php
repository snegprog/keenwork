<?php

declare(strict_types=1);

namespace App\Keenwork\Makers\NoSql;

use App\Keenwork\Config;
use Psr\Log\LoggerInterface;

/**
 * Подключение к Redis.
 */
class RedisCreator
{
    /**
     * @var \Redis - сам объект Redis
     */
    private \Redis $redis;

    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Получаем клиент NoSql.
     *
     * @throws \RedisException|\Throwable
     */
    public function get(): \Redis
    {
        if (!empty($this->redis)) {
            try {
                if (!is_string($this->redis->ping())) {
                    return $this->redis;
                }
            } catch (\RedisException $e) {
                $this->logger->error(
                    $e->getMessage(),
                    ['code' => $e->getCode(), 'file' => $e->getFile()."({$e->getLine()})"]
                );
                throw $e;
            }
        }

        $this->redis = $this->connect();

        return $this->redis;
    }

    /**
     * Проверяем подключение к NoSql.
     */
    public function isConnect(): bool
    {
        try {
            $this->get();
        } catch (\Throwable) {
            return false;
        }

        return true;
    }

    /**
     * Инициализируем полключение к NoSql.
     *
     * @throws \RedisException
     */
    private function connect(): \Redis
    {
        $redis = new \Redis();

        /* подключение к redis */
        try {
            $redis->connect(
                host: (string) Config::get('redis:host'),
                port: (int) Config::get('redis:port'),
                timeout: 0.8,
            );
        } catch (\RedisException $e) {
            $this->logger->error(
                $e->getMessage(),
                ['code' => $e->getCode(), 'file' => $e->getFile()."({$e->getLine()})"]
            );
            throw $e;
        }

        /* устанавливаем пароль подключения, если требуется */
        if ('' !== Config::get('redis:password')) {
            try {
                $redis->auth((string) Config::get('redis:password'));
            } catch (\Throwable $e) {
                $this->logger->error(
                    $e->getMessage(),
                    ['code' => $e->getCode(), 'file' => $e->getFile()."({$e->getLine()})"]
                );
                throw $e;
            }
        }

        return $redis;
    }
}
