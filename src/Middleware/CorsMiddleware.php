<?php

declare(strict_types=1);

namespace Keenwork\Middleware;

use Keenwork\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class CorsMiddleware - CORS middleware
 * @package App\Middleware
 */
final class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request)
            ->withHeader('Access-Control-Allow-Origin', Config::getCors('AccessControlAllowOrigin'))
            ->withHeader('Access-Control-Allow-Methods', Config::getCors('AccessControlAllowMethods'))
            ->withHeader('Access-Control-Allow-Headers', Config::getCors('AccessControlAllowHeaders'))
            ->withHeader('Access-Control-Expose-Headers', Config::getCors('AccessControlExposeHeaders'))
            ->withHeader('Access-Control-Allow-Credentials', Config::getCors('AccessControlAllowCredentials'));
    }
}
