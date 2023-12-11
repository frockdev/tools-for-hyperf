<?php

namespace FrockDev\ToolsForHyperf\Listeners;

use FrockDev\ToolsForHyperf\Annotations\Grpc;
use FrockDev\ToolsForHyperf\Annotations\Http;
use FrockDev\ToolsForHyperf\Annotations\Nats;
use FrockDev\ToolsForHyperf\Annotations\NatsJetstream;
use FrockDev\ToolsForHyperf\NatsJetstream\NatsConsumerManager;
use FrockDev\ToolsForHyperf\Servers\HttpProtobufServer;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\Server\Event;
use Hyperf\Server\Server;
use function Hyperf\Support\env;

class ServersConfigureListener  implements ListenerInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    private function getHttpDefaultTemplate() {
        return [
            'name' => 'http',
            'type' => Server::SERVER_HTTP,
            'host' => '0.0.0.0',
            'port' => 8081,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_REQUEST => function ($request, $response) {
                    $response->end('<h1>Hello Hyperf!</h1>');
                },
            ],
            'options' => [
                // Whether to enable request lifecycle event
                'enable_request_lifecycle' => false,
            ],
        ];
    }

    private function getNatsTemplate() {
        return [

                'options' => [
                    'host' => env('NATS_HOST', 'nats.nats'),
                    'port' => env('NATS_PORT', 4222),
                    'user' => env('NATS_USER'),
                    'pass' => env('NATS_PASS'),
                ],
                'pool' => [
                    'min_connections' => 1,
                    'max_connections' => 10,
                    'connect_timeout' => 10.0,
                    'wait_timeout' => 3.0,
                    'heartbeat' => -1,
                    'max_idle_time' => 60,
                ],

        ];
    }

    private array $grpcTemplate = [
        'name' => 'grpc',
        'type' => Server::SERVER_HTTP,
        'host' => '0.0.0.0',
        'port' => 9090,
        'sock_type' => SWOOLE_SOCK_TCP,
        'callbacks' => [
            Event::ON_REQUEST => [\FrockDev\ToolsForHyperf\Servers\GrpcProtobufServer::class, 'onRequest'],
        ],
    ];

    private array $httpTemplate = [
        'name' => 'http',
        'type' => Server::SERVER_HTTP,
        'host' => '0.0.0.0',
        'port' => 8080,
        'sock_type' => SWOOLE_SOCK_TCP,
        'callbacks' => [
            Event::ON_REQUEST => [HttpProtobufServer::class, 'onRequest'],
        ],
        'options' => [
            // Whether to enable request lifecycle event
            'enable_request_lifecycle' => false,
        ],
    ];

    public function process(object $event): void
    {
        $needHttp =
            count(AnnotationCollector::getClassesByAnnotation(Http::class))>0
            && (env('APP_MODE')=='http' || env('APP_MODE')=='all');
        $needGrpc = count(AnnotationCollector::getClassesByAnnotation(Grpc::class))>0
            && (env('APP_MODE')=='grpc' || env('APP_MODE')=='all');
        $needNats = ((count(AnnotationCollector::getClassesByAnnotation(Nats::class))>0)
                || (count(AnnotationCollector::getClassesByAnnotation(NatsJetstream::class))>0))
            && (env('APP_MODE')=='nats' || env('APP_MODE')=='all');

        /** @var ConfigInterface $config */
        $config = $this->container->get(ConfigInterface::class);
        $serverConfig = $config->get('server');

        //////http
        $foundConfigIndex = false;
        foreach ($serverConfig['servers'] as $index => $server) {
            if ($server['name'] == 'http') {
                $foundConfigIndex = $index;
            }
        }
        if ($needHttp) {
            $httpConfig = $this->httpTemplate;
            if ($foundConfigIndex!==false) {
                foreach ($serverConfig['servers'][$foundConfigIndex] as $index=>$value) {
                    if ($index=='callbacks') continue;
                    $httpConfig[$index] = $value;
                }
                $serverConfig['servers'][$foundConfigIndex] = $httpConfig;
            } else {
                $serverConfig['servers'][] = $httpConfig;
            }

        } else {
            unset($serverConfig['servers'][$foundConfigIndex]);
        }
        ////end of http


        ////grpc
        $foundConfigIndex = false;
        foreach ($serverConfig['servers'] as $index => $server) {
            if ($server['name'] == 'grpc') {
                $foundConfigIndex = $index;
            }
        }

        if ($needGrpc) {
            $grpcConfig = $this->grpcTemplate;
            if ($foundConfigIndex!==false) {
                foreach ($serverConfig['servers'][$foundConfigIndex] as $index => $value) {
                    if ($index=='callbacks') continue;
                    $grpcConfig[$index] = $value;
                }
                $serverConfig['servers'][$foundConfigIndex] = $grpcConfig;
            } else {
                $serverConfig['servers'][] = $grpcConfig;
            }

        } else {
            unset($serverConfig['servers'][$foundConfigIndex]);
        }
        ////end of grpc


        ////nats
        $natsConfigArray = $config->get('natsJetstream');
        $foundConfigIndex = false;
        if ($natsConfigArray) {
            foreach ($natsConfigArray as $natsName=>$natsConfig) {
                if ($natsName === 'jetstream') {
                    $foundConfigIndex = $natsName;
                }
            }
        }

        if ($needNats) {
            $natsConfig = $this->getNatsTemplate();
            if ($foundConfigIndex!==false) {
                foreach ($natsConfigArray[$foundConfigIndex] as $index => $value) {
                    $natsConfig[$index] = $value;
                }
                $natsConfigArray[$foundConfigIndex] = $natsConfig;
            } else {
                $natsConfigArray['jetstream'] = $natsConfig;
            }
        } else {
            unset($natsConfigArray[$foundConfigIndex]);
        }
        $config->set('natsJetstream', $natsConfigArray);

        ////end of nats
        


        if (count($serverConfig['servers'])==0) {
            $serverConfig['servers'][] = $this->getHttpDefaultTemplate();
        }

        $config->set('server', $serverConfig);

        $this->runNatsListeners();
    }

    private function runNatsListeners() {
        $needNats = ((count(AnnotationCollector::getClassesByAnnotation(Nats::class))>0)
                || (count(AnnotationCollector::getClassesByAnnotation(NatsJetstream::class))>0))
            && (env('APP_MODE')=='nats' || env('APP_MODE')=='all');
        if ($needNats) {
            $consumerManager = $this->container->get(NatsConsumerManager::class);
            $consumerManager->run();
        }
    }
}