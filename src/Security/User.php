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

    /**
     * @var list<string> The user roles
     */
    private $roles = [];

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
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}
