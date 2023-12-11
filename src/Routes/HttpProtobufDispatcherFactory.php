<?php

namespace FrockDev\ToolsForHyperf\Routes;

use FrockDev\ToolsForHyperf\Annotations\Grpc;
use FrockDev\ToolsForHyperf\Annotations\Http;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Router;
use ReflectionClass;

class HttpProtobufDispatcherFactory extends DispatcherFactory
{
    public function initConfigRoute()
    {
        Router::init($this);
        $httpController = AnnotationCollector::getClassesByAnnotation(Http::class);

        Router::addServer('http', function () use ($httpController) {
            /**
             * @var string $class
             * @var Http $annotation
             */
            foreach ($httpController as $class=>$annotation) {
                $route = $annotation->path;
                if (strtolower($annotation->method)=='get') {
                    Router::get('/'.ltrim($route,'/'), $class.'@__invoke');
                } else {
                    Router::post('/'.ltrim($route,'/'), $class.'@__invoke');
                }

            }
        });
    }
}