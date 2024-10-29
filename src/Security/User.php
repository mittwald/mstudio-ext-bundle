<?php

namespace Mittwald\MStudio\Bundle\Security;

use Mittwald\MStudio\Authentication\SSOToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

class User implements UserInterface
{
    private Uuid $id;

    private ?SSOToken $token;

    private ?string $email;

    private ?string $firstName;

    private ?string $lastName;

    public function __construct(
        Uuid $id,
        SSOToken $token = null,
        string $email = null,
        string $firstName = null,
        string $lastName = null,
    )
    {
        $this->id = $id;
        $this->token = $token;
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function getToken(): ?SSOToken
    {
        return $this->token;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
    }
}
