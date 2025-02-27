<?php

namespace App\Entity;

interface EntityInterface
{
    /**
     * @return mixed[]
     */
    public function toArray(): array;

    public function getHash(): string;
}
