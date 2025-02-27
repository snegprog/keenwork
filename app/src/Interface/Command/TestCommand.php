<?php

declare(strict_types=1);

namespace App\Interface\Command;

readonly class TestCommand implements CommandInterface
{
    public function __construct(
    ) {
    }

    public function execute(): void
    {
        echo sprintf("TestCommand PID: %d \n", getmypid());
    }
}
