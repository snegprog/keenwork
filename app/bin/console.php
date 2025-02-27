#!/usr/bin/env  php
<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use App\Interface\Command\TestCommand;

if (!isset($argv[1]) || '--help' === $argv[1] || '-h' === $argv[1]) {
    echo "\n";
    echo "\033[32m test \033[39m- Команда в скрипте которой тестируется различный функционал\n";
    echo "\n";

    return;
}

if ('test' === $argv[1]) {
    (new TestCommand())->execute();

    return;
}

echo "Не найдена команда {$argv[1]}\n";
