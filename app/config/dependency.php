<?php

declare(strict_types=1);

use App\Infrastructure\Cache\CacheInterface;
use App\Infrastructure\Cache\RedisCache;
use App\Keenwork\Makers\Logger\LoggerCreator;
use App\Keenwork\Makers\NoSql\RedisCreator;
use App\Keenwork\Makers\SqlDb\OrmCreator;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Добавление зависимостей в DI контейнер
 *
 * @param DI\Container $container
 */
function setDependencies(ContainerInterface $container): ContainerInterface
{
    $container->set(OrmCreator::class, function () {
        return new OrmCreator();
    });

    $container->set(LoggerInterface::class, function ($c) {
        return (new LoggerCreator(
            $c->get(OrmCreator::class)->getEM(),
        ))->get();
    });

    $container->set(RedisCreator::class, function ($c) {
        return new RedisCreator($c->get(LoggerInterface::class));
    });

    $container->set(RedisCache::class, function ($c) {
        return new RedisCache($c->get(RedisCreator::class));
    });

    $container->set(CacheInterface::class, function ($c) {
        return $c->get(RedisCache::class);
    });

    return $container;
}
