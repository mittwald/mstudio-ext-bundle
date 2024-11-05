<?php

namespace Mittwald\MStudio\Bundle\Service;

use GuzzleHttp\Exception\GuzzleException;
use Mittwald\ApiClient\Error\UnexpectedResponseException;
use Mittwald\ApiClient\Generated\V2\Clients\Marketplace\ExtensionAuthenticateInstance\ExtensionAuthenticateInstanceRequest;
use Mittwald\ApiClient\Generated\V2\Clients\Marketplace\ExtensionAuthenticateInstance\ExtensionAuthenticateInstanceRequestBody;
use Mittwald\ApiClient\MittwaldAPIV2Client;
use Mittwald\MStudio\Authentication\SSOToken;
use Mittwald\MStudio\Bundle\Entity\ExtensionInstance;
use Mittwald\MStudio\Bundle\Entity\ExtensionInstanceContext;
use Mittwald\MStudio\Bundle\Event\ExtensionAddedToContextEvent;
use Mittwald\MStudio\Bundle\Event\ExtensionInstanceRemovedEvent;
use Mittwald\MStudio\Bundle\Event\ExtensionInstanceUpdatedEvent;
use Mittwald\MStudio\Bundle\Repository\ExtensionInstanceRepository;
use Mittwald\MStudio\Bundle\Security\ExtensionInstanceSealer;
use Mittwald\MStudio\Bundle\Security\ExtensionInstanceSealerException;
use Mittwald\MStudio\Webhooks\Dto\ExtensionAddedToContextDto;
use Mittwald\MStudio\Webhooks\Dto\ExtensionInstanceUpdatedDto;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ExtensionInstanceService
{
    public function __construct(
        private ExtensionInstanceRepository $repository,
        private ExtensionInstanceSealer     $sealer,
        private EventDispatcherInterface    $eventDispatcher,
        private MittwaldAPIV2Client         $client,
    )
    {
    }

    /**
     * Creates a new extension instance.
     *
     * Emits an ExtensionAddedToContextEvent.
     *
     * @param ExtensionAddedToContextDto $dto DTO representing the new extension instance.
     * @return ExtensionInstance The created extension instance.
     * @throws ExtensionInstanceSealerException
     */
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

        $this->sealer->sealExtensionInstance($instance);

        $this->repository->persist($instance);
        $this->repository->flush();

        $this->eventDispatcher->dispatch(new ExtensionAddedToContextEvent($instance->getId()));

        return $instance;
    }

    /**
     * Updates the metadata of an extension instance (NOT the secret).
     *
     * Emits an ExtensionInstanceUpdatedEvent.
     *
     * @param ExtensionInstanceUpdatedDto $dto DTO representing the new metadata.
     * @return void
     */
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

    /**
     * Updates an extension instance secret.
     *
     * Emits an ExtensionInstanceUpdatedEvent.
     *
     * @param string $id The extension instance ID.
     * @param string $secret The new secret
     * @return void
     * @throws ExtensionInstanceSealerException
     */
    public function updateExtensionInstanceSecret(string $id, string $secret): void
    {
        $instance = $this->repository->mustFind(Uuid::fromString($id));
        $instance->setSecret($secret);

        $this->sealer->sealExtensionInstance($instance);

        $this->repository->flush();
        $this->eventDispatcher->dispatch(new ExtensionInstanceUpdatedEvent($instance->getId()));
    }

    /**
     * Deletes an extension instance from the database.
     *
     * Emits an ExtensionInstanceRemovedEvent.
     *
     * @param string $id The extension instance ID.
     * @return void
     */
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

    /**
     * Retrieves an mStudio API token for a given extension instance.
     *
     * @param string $id The extension instance ID
     * @return SSOToken The API token (in a container together with the token's expiration date)
     * @throws GuzzleException
     * @throws UnexpectedResponseException
     * @throws ExtensionInstanceSealerException
     */
    public function retrieveAPITokenForExtension(string $id): SSOToken
    {
        $instance = $this->repository->mustFind($id);
        $secret   = $this->sealer->unsealExtensionInstanceSecret($instance->getSecret());

        $request  = new ExtensionAuthenticateInstanceRequest($instance->getId()->toString(), new ExtensionAuthenticateInstanceRequestBody($secret));
        $response = $this->client->marketplace()->extensionAuthenticateInstance($request);

        return new SSOToken(
            accessToken: $response->getBody()->getPublicToken(),
            expiresAt: $response->getBody()->getExpiry(),
        );
    }
}