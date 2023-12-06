<?php

namespace FrockDev\ToolsForHyperf\NatsJetstream;

use FrockDev\ToolsForHyperf\Annotations\NatsJetstream;
use FrockDev\ToolsForHyperf\NatsJetstream\Processes\NatsConsumerProcess;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\ProcessManager;
use Psr\Container\ContainerInterface;
use function Hyperf\Support\make;

class NatsJetstreamConsumerManager
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function run()
    {

        $classes = AnnotationCollector::getClassesByAnnotation(NatsJetstream::class);

        foreach ($classes as $class => $annotation) {
            /** @var NatsJetstream $annotation */
            $nums = $annotation->nums;
            $process = $this->createProcess(
                $this->container->get($class),
                $annotation->subject,
                $annotation->pool ?? 'jetstream',
                $annotation->queue,
                $annotation->streamName,
            );
            $process->nums = $nums;
            $process->name = $annotation->name . '-' . $annotation->subject;
            ProcessManager::register($process);
        }
    }

    private function createProcess(object $consumer, string $subject, string $poolName, string $queue = '', string $streamName = ''): AbstractProcess
    {
        return new NatsConsumerProcess(
            $this->container,
            $consumer,
            $subject,
            $poolName,
            $queue,
            $streamName,
        );
    }
}