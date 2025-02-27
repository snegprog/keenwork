<?php

declare(strict_types=1);

namespace App\Entity;

use Cycle\Annotated\Annotation\Column;

/**
 * Базовый класс для сущностей с полями даты.
 */
class SuperclassDate extends SuperclassBase
{
    #[Column(type: 'datetime', name: 'created_at', nullable: false)]
    protected \DateTimeImmutable $createdAt;

    #[Column(type: 'datetime', name: 'updated_at', nullable: true)]
    protected \DateTimeImmutable $updatedAt;

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createAt): static
    {
        $this->createdAt = $createAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updateAt): static
    {
        $this->updatedAt = $updateAt;

        return $this;
    }
}
