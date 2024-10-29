<?php

namespace Mittwald\MStudio\Bundle\Controller;

use Mittwald\MStudio\Bundle\Service\ExtensionInstanceService;
use Mittwald\MStudio\Webhooks\Dto\ExtensionAddedToContextDto;
use Mittwald\MStudio\Webhooks\Dto\ExtensionInstanceRemovedFromContextDto;
use Mittwald\MStudio\Webhooks\Dto\ExtensionInstanceSecretRotatedDto;
use Mittwald\MStudio\Webhooks\Dto\ExtensionInstanceUpdatedDto;
use Mittwald\MStudio\Webhooks\Security\WebhookAuthorizer;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class ExtensionLifecycleController extends AbstractController
{
    readonly private WebhookAuthorizer $webhookAuthorizer;
    readonly private ExtensionInstanceService $extensionInstanceService;
    readonly private PsrHttpFactory $psrHttpFactory;

    public function __construct(
        WebhookAuthorizer $webhookAuthorizer,
        ExtensionInstanceService $extensionInstanceService,
        PsrHttpFactory $psrHttpFactory,
    )
    {
        $this->webhookAuthorizer = $webhookAuthorizer;
        $this->extensionInstanceService = $extensionInstanceService;
        $this->psrHttpFactory = $psrHttpFactory;
    }
    
    #[Route('/mstudiov1/lifecycle/added', methods: ['POST'], format: 'json')]
    public function createExtension(
        Request $request,
        #[MapRequestPayload] ExtensionAddedToContextDto $payload
    ): Response
    {
        if (!$this->authorizeRequest($request)) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $instance = $this->extensionInstanceService->createExtensionInstance($payload);
        return new JsonResponse($instance, Response::HTTP_CREATED);
    }

    #[Route('/mstudiov1/lifecycle/updated', methods: ['POST'], format: 'json')]
    public function updateExtension(
        Request $request,
        #[MapRequestPayload] ExtensionInstanceUpdatedDto $payload
    ): Response
    {
        if (!$this->authorizeRequest($request)) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $this->extensionInstanceService->updateExtensionInstance($payload);
        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[Route('/mstudiov1/lifecycle/secret-rotated', methods: ['POST'], format: 'json')]
    public function rotateExtensionSecret(
        Request $request,
        #[MapRequestPayload] ExtensionInstanceSecretRotatedDto $payload
    ): Response
    {
        if (!$this->authorizeRequest($request)) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $this->extensionInstanceService->updateExtensionInstanceSecret($payload->id, $payload->secret);
        return new Response('', Response::HTTP_NO_CONTENT);
    }

    #[Route('/mstudiov1/lifecycle/removed', methods: ['POST'], format: 'json')]
    public function removeExtension(
        Request $request,
        #[MapRequestPayload] ExtensionInstanceRemovedFromContextDto $payload
    ): Response
    {
        if (!$this->authorizeRequest($request)) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $this->extensionInstanceService->removeExtensionInstance($payload->id);
        return new Response('', Response::HTTP_NO_CONTENT);
    }

    public function authorizeRequest(Request $request): bool
    {
        return $this->webhookAuthorizer->authorize($this->psrHttpFactory->createRequest($request));
    }
}