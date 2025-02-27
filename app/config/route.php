<?php

declare(strict_types=1);

use App\Keenwork\Keenwork;
use App\Keenwork\Request;
use App\Keenwork\Response;
use Psr\Http\Message\ResponseInterface;

function setRoutes(Keenwork $keenwork): void
{
    $keenwork->getSlim()->setBasePath('/v1');
    $keenwork->getSlim()->add(App\Interface\Api\Middleware\CorsMiddleware::class);

    // OPTION
    $keenwork->getSlim()->map(['OPTIONS'], '/{routes:.+}', function (Request $request, ResponseInterface $response): ResponseInterface {
        return $response;
    });

    // Info
    $keenwork->getSlim()->get('/info/{argument}', App\Interface\Api\Controller\MainController::class.':info');

    // 404
    $keenwork->getSlim()->map(
        ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
        '/{routes:.+}',
        function (Request $request, ResponseInterface $response) {
            $response
                ->getBody()
                ->write(json_encode(['error' => 'Page not found']) ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(Response::HTTP_NOT_FOUND);
        }
    );
}
