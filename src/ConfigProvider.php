<?php

namespace FrockDev\ToolsForHyperf;

use FrockDev\ToolsForHyperf\Commands\AddGeneratedNamespacesToComposerJson;
use FrockDev\ToolsForHyperf\Commands\AddToArrayToGrpcObjects;
use FrockDev\ToolsForHyperf\Commands\CreateEndpointsFromProto;
use FrockDev\ToolsForHyperf\Commands\PrepareProtoFiles;
use FrockDev\ToolsForHyperf\Listeners\NatsRunListener;
use FrockDev\ToolsForHyperf\Listeners\ServersConfigureListener;

class ConfigProvider
{
    public function __invoke(): array {
        return [
            'commands' => [
                PrepareProtoFiles::class,
                AddGeneratedNamespacesToComposerJson::class,
                AddToArrayToGrpcObjects::class,
                CreateEndpointsFromProto::class,
            ],
            'dependencies'=>[

            ],
            'listeners'=>[
                NatsRunListener::class,
                ServersConfigureListener::class,
            ]
        ];
    }
}