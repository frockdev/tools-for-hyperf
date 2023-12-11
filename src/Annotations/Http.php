<?php

namespace FrockDev\ToolsForHyperf\Annotations;

use Hyperf\Di\Annotation\AbstractAnnotation;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Http extends AbstractAnnotation
{
    public function __construct(
        public string $method,
        public string $path,
    ) {
    }
}