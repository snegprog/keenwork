<?php

namespace App\Exception;

/**
 * Исключение выкидываемое если позиция исключена из рассчетного периода в ручном режиме.
 */
class ExcludedException extends \Exception
{
    public function __construct(string $message, int $code = 410)
    {
        parent::__construct($message, $code);
    }
}
