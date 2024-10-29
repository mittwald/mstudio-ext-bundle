<?php

namespace Mittwald\MStudio\Bundle\Controller;

use Mittwald\MStudio\Bundle\Repository\ExtensionInstanceRepository;
use Mittwald\MStudio\Bundle\Security\TokenRetrievalKeyAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class SSOController extends AbstractController
{
    private readonly Security $security;
    private readonly string $targetRouteAfterAuthentication;
    private readonly ExtensionInstanceRepository $repository;

    public function __construct(
        Security $security,
        ExtensionInstanceRepository $repository,
        string $targetRouteAfterAuthentication,
    )
    {
        $this->security = $security;
        $this->repository = $repository;
        $this->targetRouteAfterAuthentication = $targetRouteAfterAuthentication;
    }

    #[Route('/sso/{extensionInstanceId}/{contextId}', methods: ['GET'])]
    public function authenticate(
        string $extensionInstanceId,
        string $contextId,
        #[MapQueryParameter(name: "accessTokenRetrievalKey")] string $tokenRetrievalKey,
        #[MapQueryParameter(name: "userId")] string $userId,
    )
    {
        $user = $this->getUser();

        $this->security->login($user, TokenRetrievalKeyAuthenticator::class);

        $instance = $this->repository->find($extensionInstanceId);
        if (!$instance) {
            throw new NotFoundHttpException("extension instance does not exist");
        }

        return $this->redirectToRoute(
            $this->targetRouteAfterAuthentication,
            [
                'context' => $instance->getContext()->getId(),
                'contextKind' => $instance->getContext()->getKind(),
            ]
        );
        //return new Response('Hello ' . $user->getUserIdentifier());
    }
}