<?php

namespace FrockDev\ToolsForHyperf\Routes;

use FrockDev\ToolsForHyperf\Annotations\Grpc;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Router;

class GrpcDispatcherFactory extends DispatcherFactory
{
    public function initConfigRoute()
    {
        Router::init($this);
        $grpcControllers = AnnotationCollector::getClassesByAnnotation(Grpc::class);

        Router::addServer('grpc', function () use ($grpcControllers) {
            foreach ($grpcControllers as $class=>$controller) {
                $route = $class::GRPC_ROUTE;
                Router::post('/'.ltrim($route, '/'), $class.'@__invoke');
            }
        });
    }
}