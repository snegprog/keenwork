<?php

namespace App\Exception;

class NotFoundException extends \Exception
{
    public function __construct(string $message, int $code = 404)
    {
        parent::__construct($message, $code);
    }
}
