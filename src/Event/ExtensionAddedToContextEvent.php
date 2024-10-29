<?php

namespace Mittwald\MStudio\Bundle\Event;

use Symfony\Component\Uid\Uuid;

readonly class ExtensionAddedToContextEvent
{
    public Uuid $extensionInstanceId;

    public function __construct(Uuid $extensionInstanceId)
    {
        $this->extensionInstanceId = $extensionInstanceId;
    }
}