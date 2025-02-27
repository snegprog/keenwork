<?php

namespace App\Exception;

class UnauthorizedException extends \Exception
{
    public function __construct(string $message, int $code = 401)
    {
        parent::__construct($message, $code);
    }
}
