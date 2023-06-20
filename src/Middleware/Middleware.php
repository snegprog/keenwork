<?php

declare(strict_types=1);

namespace Keenwork\Middleware;

use Keenwork\Config;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class Middleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $contentType = $request->getHeaderLine('Content-Type');

        if (str_contains($contentType, 'application/json')) {
            $body = (string) $request->getBody();
            /** @var string[] $json */
            $json = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request = $request->withParsedBody($json);
            }
        }

        return $handler->handle($request)->withHeader('Content-Type', (string) Config::get('content-type'));
    }
}
