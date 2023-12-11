<?php

namespace FrockDev\ToolsForHyperf\Listeners;

use FrockDev\ToolsForHyperf\Annotations\Nats;
use FrockDev\ToolsForHyperf\Annotations\NatsJetstream;
use FrockDev\ToolsForHyperf\NatsJetstream\NatsConsumerManager;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeMainServerStart;
use Psr\Container\ContainerInterface;
use function Hyperf\Support\env;

class NatsRunListener implements ListenerInterface
{

    public function __construct(private ContainerInterface $container)
    {
    }

    public function listen(): array
    {
        return [
            BeforeMainServerStart::class,
        ];
    }

    public function process(object $event): void
    {
        $needNats = ((count(AnnotationCollector::getClassesByAnnotation(Nats::class))>0)
            || (count(AnnotationCollector::getClassesByAnnotation(NatsJetstream::class))>0))
            && (env('APP_MODE')=='nats' || env('APP_MODE')=='all');
        if ($needNats) {
            $consumerManager = $this->container->get(NatsConsumerManager::class);
            $consumerManager->run();
        }
    }
}