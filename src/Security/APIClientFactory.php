<?php

namespace Mittwald\MStudio\Bundle\Security;

use Mittwald\ApiClient\MittwaldAPIV2Client;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * Factory class for building authenticated clients for the mittwald mStudio v2 API.
 *
 * @todo
 *   This (except the buildAPIClientForCurrentUser function) should be moved
 *   upstream to the mittwald/api-client package!
 */
class APIClientFactory
{
    private readonly Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * Builds an API client for a given API token.
     *
     * @param string $token The API token
     * @return MittwaldAPIV2Client An authenticated API client
     */
    public function buildAPIClientForToken(string $token): MittwaldAPIV2Client
    {
        return MittwaldAPIV2Client::newWithToken($token);
    }

    /**
     * Builds an API client that is already authenticated for a given user.
     *
     * @param User $user The user for which to build the API client.
     * @return MittwaldAPIV2Client An authenticated API client
     */
    public function buildAPIClientForUser(User $user): MittwaldAPIV2Client
    {
        $tokenObj = $user->getToken();
        if (is_null($tokenObj)) {
            throw new AuthenticationException('User is not authenticated');
        }

        return MittwaldAPIV2Client::newWithToken($tokenObj->getAccessToken());
    }

    /**
     * Builds an API client that is already authenticated for the currently
     * authenticated user.
     *
     * @return MittwaldAPIV2Client An authenticated API client
     */
    public function buildAPIClientForCurrentUser(): MittwaldAPIV2Client
    {
        $user = $this->security->getUser();
        if (!($user instanceof User)) {
            throw new AuthenticationException('User is not authenticated');
        }

        return $this->buildAPIClientForUser($user);
    }
}