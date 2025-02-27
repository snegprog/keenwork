<?php

namespace App\Interface\Command;

interface CommandInterface
{
    /**
     * Выполнение действий.
     * Обязательно для всех комманд.
     */
    public function execute(): void;
}
