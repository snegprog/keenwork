<?php

declare(strict_types=1);

namespace Keenwork\Factory;

use Keenwork\Request;

class Psr17Factory extends \Slim\Factory\Psr17\Psr17Factory
{
    protected static string $responseFactoryClass = ResponseFactory::class;
    protected static string $streamFactoryClass = StreamFactory::class;
    protected static string $serverRequestCreatorClass = Request::class;
    protected static string $serverRequestCreatorMethod = 'fromGlobals';
}
