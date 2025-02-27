<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\LogRepository;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\ORM\Entity\Behavior;
use Cycle\ORM\Entity\Behavior\Uuid\Uuid6;

#[Entity(
    role: 'log',
    repository: LogRepository::class,
    table: 'logs',
)]
#[Uuid6(
    field: 'uuid',
    node: '33330fffffff',
    clockSeq: 0xFFFF
)]
#[Behavior\CreatedAt(
    field: 'createdAt',
    column: 'created_at'
)]
#[Behavior\UpdatedAt(
    field: 'updatedAt',
    column: 'updated_at'
)]
class Log extends SuperclassDate implements EntityInterface
{
    #[Column(type: 'string(2596)', name: 'message', nullable: false, default: '')]
    private string $message;

    #[Column(type: 'string(8)', name: 'type', nullable: false, default: '')]
    private string $type;

    #[Column(type: 'json', name: 'context', nullable: false, default: null)]
    private string $context;

    #[Column(type: 'integer', name: 'level', nullable: false, default: null)]
    private int $level;

    #[Column(type: 'string(50)', name: 'level_name', nullable: false, default: null)]
    private string $levelName;

    #[Column(type: 'json', name: 'extra', nullable: false, default: null)]
    private string $extra;

    public function __construct()
    {
        $this->message = '';
        $this->type = '';
        $this->context = '';
        $this->level = 0;
        $this->levelName = '';
        $this->extra = '';
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getContext(): array
    {
        /** @var string[] $result */
        $result = $this->context ? (array) json_decode($this->context, true) : [];

        return $result;
    }

    /**
     * @param array<mixed> $context
     */
    public function setContext(array $context): self
    {
        $this->context = json_encode($context) ?: '';

        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getLevelName(): string
    {
        return $this->levelName;
    }

    public function setLevelName(string $levelName): self
    {
        $this->levelName = $levelName;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getExtra(): array
    {
        /** @var string[] $result */
        $result = $this->extra ? (array) json_decode($this->extra, true) : [];

        return $result;
    }

    /**
     * @param array<mixed> $extra
     */
    public function setExtra(array $extra): self
    {
        $this->extra = json_encode($extra) ?: '';

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): Log
    {
        $this->type = $type;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->getUuid()->toString(),
            'message' => $this->getMessage(),
            'type' => $this->getType(),
            'context' => $this->getContext(),
            'level' => $this->getLevel(),
            'level_name' => $this->getLevelName(),
            'extra' => $this->getExtra(),
            'created_at' => $this->getCreatedAt(),
            'updated_at' => $this->getUpdatedAt(),
        ];
    }

    public function getHash(): string
    {
        return sha1($this->getUuid().$this->getUpdatedAt()->getTimestamp());
    }
}
