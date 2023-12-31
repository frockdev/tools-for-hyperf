<?php

namespace FrockDev\ToolsForHyperf\Servers;

use FrockDev\ToolsForHyperf\Middlewares\GrpcProtobufCoreMiddleware;
use Hyperf\Contract\ConfigInterface;
use Hyperf\GrpcServer\CoreMiddleware;
use Hyperf\GrpcServer\Exception\Handler\GrpcExceptionHandler;
use Hyperf\HttpServer\Contract\CoreMiddlewareInterface;
use function Hyperf\Support\make;

class GrpcProtobufServer extends \Hyperf\GrpcServer\Server
{
    public function initCoreMiddleware(string $serverName): void
    {
        $this->serverName = $serverName;
        $this->coreMiddleware = $this->createCoreMiddleware();

        $config = $this->container->get(ConfigInterface::class);
        $this->middlewares = $config->get('middlewares.' . $serverName, []);
        $this->exceptionHandlers = $config->get('exceptions.handler.' . $serverName, [
            GrpcExceptionHandler::class,
        ]);

        $this->initOption();
    }

    protected function createCoreMiddleware(): CoreMiddlewareInterface
    {
        return make(GrpcProtobufCoreMiddleware::class, [$this->container, $this->serverName]);
    }
}