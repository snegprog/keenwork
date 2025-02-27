#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/config/dependency.php';
require_once __DIR__.'/config/route.php';

use App\Keenwork\Config;
use App\Keenwork\Keenwork;
use App\Keenwork\Makers\Logger\LoggerCreator;
use App\Keenwork\Makers\SqlDb\OrmCreator;

$app = new Keenwork(
    logger: (new LoggerCreator((new OrmCreator())->getEM()))->get(),
    hostHttp: Config::get('worker-man:host'),
    portHttp: Config::get('worker-man:port'),
    debugHttp: 'prod' !== Config::getEnvironment(),
    workersHttp: 'prod' === Config::getEnvironment() ? ((int) shell_exec('nproc') * 3) : 2
);

try {
    /* Установка зависимостей в DI контейнер */
    setDependencies($app->getSlim()->getContainer());
    /* Подключаем роуты */
    setRoutes($app);
} catch (Exception $e) {
    $app->getLogger()?->error($e->getMessage());
    echo $e->getMessage();

    return;
}

// Scheduler
$testCommand = function () {
    (new App\Interface\Command\TestCommand(
    ))->execute();
};
$app->addShedule(cronExpression: new Cron\CronExpression('15 01 * * *'), job: $testCommand, name: 'testCommand');

$app->getLogger()->info('Start app. PID: '.getmypid());

try {
    Keenwork::runAll($app);
} catch (Throwable $e) {
    echo $e->getMessage()."\n";
}
