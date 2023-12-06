<?php

namespace FrockDev\ToolsForHyperf\Servers;

use FrockDev\ToolsForHyperf\Middlewares\HttpProtobufCoreMiddleware;
use Hyperf\HttpServer\Contract\CoreMiddlewareInterface;
use function Hyperf\Support\make;

class HttpProtobufServer extends \Hyperf\HttpServer\Server
{
    protected function createCoreMiddleware(): CoreMiddlewareInterface
    {
        return make(HttpProtobufCoreMiddleware::class, [$this->container, $this->serverName]);
    }
}