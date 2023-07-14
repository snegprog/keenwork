<?php

declare(strict_types=1);

namespace Keenwork\Controller;

use Keenwork\Keenwork;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use function json_encode;

class ExampleController
{
    /**
     * @param string[] $args
     */
    public function info(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $rs = [
            'path argument ' => $args['argument'] ?? '',
            'keenwork' => Keenwork::VERSION,
            'php' => phpversion(),
        ];

        $response
            ->getBody()
            ->write((string) json_encode($rs));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
