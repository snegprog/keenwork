#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/dependency.php';
require_once __DIR__ . '/config/route.php';

use Keenwork\Config;
use Keenwork\Keenwork;

$app = new Keenwork(
    hostHttp: Config::get('worker-man:host'),
    portHttp: Config::get('worker-man:port'),
    debugHttp: Config::getEnvironment() !== 'prod',
    workersHttp: Config::getEnvironment() === 'prod' ? ((int) shell_exec('nproc')*2) : 2
);

try {
    /** Установка зависимостей в DI контейнер */
    setDependencies($app->getSlim()->getContainer());
    /** Подключаем роуты */
    setRoutes($app);
} catch (Exception $e) {
    $app->getLogger()?->error($e->getMessage());
}

Keenwork::runAll($app);
