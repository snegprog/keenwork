<?php

namespace App\Infrastructure\Logger;

use App\Exception\BadRequestException;
use App\Exception\NotFoundException;

interface LoggerInterface
{
    /**
     * @param array<string, mixed> $data - ключем является название поля
     *
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws \ErrorException
     */
    public function insert(string $tableName, array $data): void;
}
