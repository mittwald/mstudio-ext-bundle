<?php

namespace Mittwald\MStudio\Bundle\Entity;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[Entity]
class ExtensionInstance
{
    #[Column(type: UuidType::NAME)]
    #[Id]
    private Uuid $id;

    #[Embedded(class: ExtensionInstanceContext::class)]
    private ExtensionInstanceContext $context;

    #[Column(type: 'string')]
    private string $secret;

    /** @var string[] */
    #[Column(type: 'json')]
    private array $consentedScopes;

    #[Column(type: 'boolean')]
    private bool $enabled;

    /**
     * @param Uuid $id
     * @param ExtensionInstanceContext $context
     * @param string $secret
     * @param string[] $consentedScopes
     * @param bool $enabled
     */
    public function __construct(
        Uuid                     $id,
        ExtensionInstanceContext $context,
        string                   $secret,
        array                    $consentedScopes = [],
        bool                     $enabled = true
    )
    {
        $this->id              = $id;
        $this->context         = $context;
        $this->secret          = $secret;
        $this->consentedScopes = $consentedScopes;
        $this->enabled         = $enabled;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setContext(ExtensionInstanceContext $context): void
    {
        $this->context = $context;
    }

    public function getContext(): ExtensionInstanceContext
    {
        return $this->context;
    }

    public function setSecret(string $secret): void
    {
        $this->secret = $secret;
    }

    public function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @param string[] $consentedScopes
     * @return void
     */
    public function setConsentedScopes(array $consentedScopes): void
    {
        $this->consentedScopes = $consentedScopes;
    }

    /**
     * @return string[]
     */
    public function getConsentedScopes(): array
    {
        return $this->consentedScopes;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

}