<?php

namespace App\Infrastructure\Cache;

interface CacheInterface
{
    /**
     * Установка значения.
     */
    public function set(string $key, string $value, ?int $timeout = null): bool;

    /**
     * Удаление значения по ключу.
     */
    public function remove(string $key): bool;

    /**
     * ПРоверка наличия значения по ключу.
     */
    public function exists(string $key): bool;

    /**
     * @return string[]
     */
    public function getAllKeys(string $pattern): array;

    /**
     * Получить значение по ключу.
     */
    public function get(string $key): ?string;

    /**
     * Состояние доступности кеша.
     */
    public function isConnect(): bool;

    /**
     * Очистить весь кеш.
     */
    public function removeAll(): void;
}
