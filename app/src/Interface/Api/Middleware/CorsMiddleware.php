<?php

declare(strict_types=1);

namespace App\Interface\Api\Middleware;

use App\Keenwork\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CorsMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (
            is_array($request->getHeader('origin'))
            && array_key_exists(0, $request->getHeader('origin'))
            && null !== $request->getHeader('origin')[0]
            && str_contains($request->getHeader('origin')[0], Config::getCors('AccessControlAllowOrigin'))
        ) {
            $accessControlAllowOrigin = $request->getHeader('origin')[0];
        } else {
            $accessControlAllowOrigin = Config::getCors('AccessControlAllowOrigin');
        }


        return $handler->handle($request)
            ->withHeader('Access-Control-Allow-Origin', $accessControlAllowOrigin)
            ->withHeader('Access-Control-Allow-Methods', Config::getCors('AccessControlAllowMethods'))
            ->withHeader('Access-Control-Allow-Headers', Config::getCors('AccessControlAllowHeaders'))
            ->withHeader('Access-Control-Expose-Headers', Config::getCors('AccessControlExposeHeaders'))
            ->withHeader('Access-Control-Allow-Credentials', Config::getCors('AccessControlAllowCredentials'))
            ->withHeader('Content-Type', 'application/json');
    }
}
