<?php

namespace Mittwald\MStudio\Bundle\Service;

use Mittwald\MStudio\Bundle\Entity\ExtensionInstance;
use Mittwald\MStudio\Bundle\Entity\ExtensionInstanceContext;
use Mittwald\MStudio\Bundle\Event\ExtensionAddedToContextEvent;
use Mittwald\MStudio\Bundle\Event\ExtensionInstanceRemovedEvent;
use Mittwald\MStudio\Bundle\Event\ExtensionInstanceUpdatedEvent;
use Mittwald\MStudio\Bundle\Repository\ExtensionInstanceRepository;
use Mittwald\MStudio\Webhooks\Dto\ExtensionAddedToContextDto;
use Mittwald\MStudio\Webhooks\Dto\ExtensionInstanceUpdatedDto;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ExtensionInstanceService
{
    public function __construct(
        private ExtensionInstanceRepository $repository,
        private EventDispatcherInterface $eventDispatcher,
    )
    {
    }

    public function createExtensionInstance(ExtensionAddedToContextDto $dto): ExtensionInstance
    {
        $existing = $this->repository->find($dto->id);
        if ($existing) {
            return $existing;
        }

        $instance = new ExtensionInstance(
            id: Uuid::fromString($dto->id),
            context: new ExtensionInstanceContext(
                id: Uuid::fromString($dto->context->id),
                kind: $dto->context->kind,
            ),
            secret: $dto->secret,
            consentedScopes: $dto->consentedScopes,
            enabled: $dto->state->enabled,
        );

        $this->repository->persist($instance);
        $this->repository->flush();

        $this->eventDispatcher->dispatch(new ExtensionAddedToContextEvent($instance->getId()));

        return $instance;
    }

    public function updateExtensionInstance(ExtensionInstanceUpdatedDto $dto): void
    {
        $instance = $this->repository->mustFind(Uuid::fromString($dto->id));
        $instance->setConsentedScopes($dto->consentedScopes);
        $instance->setEnabled($dto->state->enabled);
        $instance->setContext(new ExtensionInstanceContext(
            id: Uuid::fromString($dto->context->id),
            kind: $dto->context->kind,
        ));

        $this->repository->flush();

        $this->eventDispatcher->dispatch(new ExtensionInstanceUpdatedEvent($instance->getId()));
    }

    public function updateExtensionInstanceSecret(string $id, string $secret): void
    {
        $instance = $this->repository->mustFind(Uuid::fromString($id));
        $instance->setSecret($secret);

        $this->repository->flush();
        $this->eventDispatcher->dispatch(new ExtensionInstanceUpdatedEvent($instance->getId()));
    }

    public function removeExtensionInstance(string $id): void
    {
        $instance = $this->repository->find(Uuid::fromString($id));
        if ($instance === null) {
            return;
        }

        $this->repository->remove($instance);
        $this->repository->flush();

        $this->eventDispatcher->dispatch(new ExtensionInstanceRemovedEvent($instance->getId()));
    }
}