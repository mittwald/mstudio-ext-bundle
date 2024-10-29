<?php

namespace Mittwald\MStudio\Bundle\Security;

use Mittwald\ApiClient\Error\UnexpectedResponseException;
use Mittwald\ApiClient\Generated\V2\Clients\User\GetUser\GetUserRequest;
use Mittwald\ApiClient\MittwaldAPIV2Client;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Uid\Uuid;

class UserProvider implements UserProviderInterface
{
    private readonly MittwaldAPIV2Client $client;

    public function __construct(MittwaldAPIV2Client $client)
    {
        $this->client = $client;
    }

    /**
     * Symfony calls this method if you use features like switch_user
     * or remember_me.
     *
     * If you're not using these features, you do not need to implement
     * this method.
     *
     * @throws UserNotFoundException if the user is not found
     */
    public function loadUserByIdentifier($identifier): UserInterface
    {
        try {
            $userResponse = $this->client->user()->getUser(new GetUserRequest($identifier));
            $user         = $userResponse->getBody();

            return new User(
                id: Uuid::fromString($user->getUserId()),
                email: $user->getEmail(),
                firstName: $user->getPerson()->getFirstName(),
                lastName: $user->getPerson()->getLastName(),
            );
        } catch (UnexpectedResponseException $err) {
            if ($err->response->getStatusCode() === 401) {
                return new User(id: Uuid::fromString($identifier));
            }

            throw $err;
        }
    }

    /**
     * @deprecated since Symfony 5.3, loadUserByIdentifier() is used instead
     */
    public function loadUserByUsername($username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    /**
     * Refreshes the user after being reloaded from the session.
     *
     * When a user is logged in, at the beginning of each request, the
     * User object is loaded from the session and then this method is
     * called. Your job is to make sure the user's data is still fresh by,
     * for example, re-querying for fresh User data.
     *
     * If your firewall is "stateless: true" (for a pure API), this
     * method is not called.
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', $user::class));
        }

        return $user;
    }

    /**
     * Tells Symfony to use this provider for this User class.
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
