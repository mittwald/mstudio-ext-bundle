<?php

namespace Mittwald\MStudio\Bundle\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[Embeddable]
class ExtensionInstanceContext
{
    #[Column(type: UuidType::NAME)]
    private Uuid $id;

    #[Column(type: 'string')]
    private string $kind;

    public function __construct(Uuid $id, string $kind)
    {
        $this->id = $id;
        $this->kind = $kind;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

}