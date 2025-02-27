<?php

declare(strict_types=1);

namespace App\Keenwork\Makers\Logger;

use App\Entity\Log;
use Cycle\ORM\EntityManagerInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Монолог, подключение к БД.
 */
class MonologDBHandler extends AbstractProcessingHandler
{
    /**
     * @phpstan-param value-of<Level::VALUES>|value-of<Level::NAMES>|Level $logLevel
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        int|string|Level $logLevel,
    ) {
        parent::__construct($logLevel);
    }

    protected function write(LogRecord $record): void
    {
        $logEntry = new Log();
        $logEntry->setMessage($record->message);
        $logEntry->setLevel($record->level->value);
        $logEntry->setLevelName($record->level->getName());
        $logEntry->setExtra($record->extra);
        $logEntry->setContext($record->context);
        $logEntry->setType('user');

        $this->em->persist($logEntry)->run();
    }
}
