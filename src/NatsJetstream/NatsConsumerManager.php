<?php

namespace FrockDev\ToolsForHyperf\NatsJetstream;

use FrockDev\ToolsForHyperf\Annotations\Grpc;
use FrockDev\ToolsForHyperf\Annotations\Nats;
use FrockDev\ToolsForHyperf\NatsJetstream\Events\AfterConsume;
use FrockDev\ToolsForHyperf\NatsJetstream\Events\AfterSubscribe;
use FrockDev\ToolsForHyperf\NatsJetstream\Events\BeforeConsume;
use FrockDev\ToolsForHyperf\NatsJetstream\Events\BeforeSubscribe;
use FrockDev\ToolsForHyperf\NatsJetstream\Events\FailToConsume;
use FrockDev\ToolsForHyperf\NatsJetstream\Processes\NatsConsumerProcess;
use Google\Protobuf\Internal\Message;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Nats\AbstractConsumer;
use Hyperf\Nats\Annotation\Consumer as ConsumerAnnotation;
use Hyperf\Nats\Driver\DriverFactory;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\ProcessManager;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use function Hyperf\Support\make;

class NatsConsumerManager
{
    public function __construct(private ContainerInterface $container)
    {
    }

    public function run()
    {

        $classes = AnnotationCollector::getClassesByAnnotation(Nats::class);

        foreach ($classes as $class => $annotation) {
            /** @var Nats $annotation */
            $nums = $annotation->nums;
            $process = $this->createProcess(
                $this->container->get($class),
                $annotation->subject,
                    $annotation->pool ?? 'jetstream',
                $annotation->queue,
            );
            $process->nums = $nums;
            $process->name = $annotation->name . '-' . $annotation->subject;
            ProcessManager::register($process);
        }
    }

    private function createProcess(object $consumer, string $subject, string $poolName, string $queue = ''): AbstractProcess
    {
        return new NatsConsumerProcess(
            $this->container,
            $consumer,
            $subject,
            $poolName,
            $queue,
        );
    }
}