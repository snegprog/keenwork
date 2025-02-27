<?php

declare(strict_types=1);

namespace App\Keenwork\Makers\Logger;

use Cycle\ORM\EntityManagerInterface;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use Psr\Log\LoggerInterface;

/**
 * Инициализация логера.
 */
readonly class LoggerCreator
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * @phpstan-param value-of<Level::VALUES>|value-of<Level::NAMES>|Level $logLevel
     */
    public function get(int|string|Level $logLevel = Level::Info): LoggerInterface
    {
        $logger = new Logger('app');
        $logger->pushHandler(new MonologDBHandler($this->em, $logLevel)); // Здесь используется основной логер, но его в github нет, поэтому что есть
        $logger->setExceptionHandler(function (\Throwable $e, LogRecord $record) use ($logLevel) {
            $logger = $this->getHandlerReserve($logLevel);
            $logger->error($e->getMessage());
            $logger->reset();
            (new MonologDBHandler($this->em, $logLevel))->handle($record);
        });

        return $logger;
    }

    /**
     * @phpstan-param value-of<Level::VALUES>|value-of<Level::NAMES>|Level $logLevel
     */
    private function getHandlerReserve(int|string|Level $logLevel = Level::Info): Logger
    {
        $DBHandlerReserve = new MonologDBHandler($this->em, $logLevel);
        $loggerReserve = new Logger('app_reserve');
        $loggerReserve->pushHandler($DBHandlerReserve);

        return $loggerReserve;
    }
}
