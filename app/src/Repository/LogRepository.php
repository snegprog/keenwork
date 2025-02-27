<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Log;
use Cycle\ORM\Select\Repository;

/**
 * @template TEntity of Log
 *
 * @extends Repository<TEntity>
 */
class LogRepository extends Repository
{
}
