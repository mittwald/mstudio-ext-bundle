<?php

namespace Mittwald\MStudio\Bundle\Security;

use Mittwald\ApiClient\MittwaldAPIV2Client;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class APIClientFactory
{
    private readonly Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function buildAPIClientForUser(User $user): MittwaldAPIV2Client
    {
        return MittwaldAPIV2Client::newWithToken($user->getToken()->getAccessToken());
    }

    public function buildAPIClientForCurrentUser(): MittwaldAPIV2Client
    {
        $user = $this->security->getUser();
        if (!($user instanceof User)) {
            throw new AuthenticationException('User is not authenticated');
        }

        return $this->buildAPIClientForUser($user);
    }
}