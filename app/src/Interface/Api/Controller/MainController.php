<?php

declare(strict_types=1);

namespace App\Interface\Api\Controller;

use App\Infrastructure\Cache\CacheInterface;
use App\Keenwork\Keenwork;
use App\Keenwork\Makers\SqlDb\OrmCreator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

readonly class MainController
{
    public function __construct(
        private CacheInterface $cashe,
        private OrmCreator $ormCreator,
    ) {
    }

    /**
     * @param string[] $args
     */
    public function info(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $rs = [
            'path_argument' => $args['argument'] ?? '',
            'keenwork' => Keenwork::VERSION,
            'php' => phpversion(),
            'redis_connect' => $this->cashe->isConnect(),
            'db_connect' => $this->ormCreator->isConnect(),
        ];

        $response
            ->getBody()
            ->write((string) \json_encode($rs));

        return $response;
    }
}
