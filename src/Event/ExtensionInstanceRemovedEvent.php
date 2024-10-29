<?php

namespace Mittwald\MStudio\Bundle\Event;

use Symfony\Component\Uid\Uuid;

readonly class ExtensionInstanceRemovedEvent
{
    public Uuid $extensionInstanceId;

    public function __construct(Uuid $extensionInstanceId)
    {
        $this->extensionInstanceId = $extensionInstanceId;
    }
}