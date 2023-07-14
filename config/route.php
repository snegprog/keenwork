<?php

use Keenwork\Controller\ExampleController;
use Keenwork\Keenwork;
use Keenwork\Middleware\CorsMiddleware;
use Keenwork\Request;
use Keenwork\Response;
use Psr\Http\Message\ResponseInterface;

function setRoutes(Keenwork $keenwork)
{
    $keenwork->getSlim()->add(CorsMiddleware::class);

    // OPTION
    $keenwork->getSlim()->map(['OPTIONS'], '/{routes:.+}', function (Request $request, ResponseInterface $response): ResponseInterface {
        return $response;
    });

    $keenwork->getSlim()->get('/info/{argument}', ExampleController::class . ':info');

    // 404
    $keenwork->getSlim()->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}',
        function (Request $request, ResponseInterface $response) {
            $response
                ->getBody()
                ->write(json_encode(["error" => "Page not found"]) ?: '');

            return $response->withHeader('Content-Type', 'application/json')->withStatus(Response::HTTP_NOT_FOUND);
        });

}
