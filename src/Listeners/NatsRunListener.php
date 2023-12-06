<?php

namespace FrockDev\ToolsForHyperf\Listeners;

use FrockDev\ToolsForHyperf\NatsJetstream\NatsConsumerManager;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeMainServerStart;
use Psr\Container\ContainerInterface;

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
        $consumerManager = $this->container->get(NatsConsumerManager::class);
        $consumerManager->run();
    }
}