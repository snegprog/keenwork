<?php

declare(strict_types=1);

namespace App\Entity;

use Cycle\Annotated\Annotation\Column;
use Ramsey\Uuid\UuidInterface;

/**
 * Базовый класс для сущностей БД.
 */
class SuperclassBase
{
    #[Column(type: 'uuid', primary: true)]
    protected UuidInterface $uuid;

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }
}
