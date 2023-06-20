#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/dependencies.php';

use Keenwork\Config;
use Keenwork\Keenwork;
use Keenwork\Request;
use Keenwork\Response;
use Keenwork\Middleware\CorsMiddleware;
use Psr\Http\Message\ResponseInterface;

if (Config::getEnvironment() === 'prod') {
    $workers = ((int) shell_exec('nproc')*2);
} else {
    $workers = 1;
}

$app = new Keenwork(null);
$app->initHttp([
    'debug' => Config::getEnvironment() !== 'prod',
    'host' => Config::get('worker-man:host'),
    'port' => Config::get('worker-man:port'),
    'workers' => $workers,
]);

try {
    /** Установка зависимостей в DI контейнер */
    setDependencies($app->getSlim()->getContainer());
} catch (Exception $e) {
    $app->getLogger()?->error($e->getMessage());
}

$app->getSlim()->add(CorsMiddleware::class);

// OPTION
$app->getSlim()->map(['OPTIONS'], '/{routes:.+}', function (Request $request, Response $response): ResponseInterface {
    return $response;
});

$app->getSlim()->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    return $response->with(['hello ' => $args['name'] ?? 'world']);
});

// 404
$app->getSlim()->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($req, $res) {
    return $res->with(["error" => "Page not found"], Response::HTTP_NOT_FOUND);
});


Keenwork::runAll($app);
