<?php

namespace Mittwald\MStudio\Bundle\Security;

use Mittwald\MStudio\Authentication\SSOToken;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

class TokenRetrievalKeyBadge implements BadgeInterface
{
    private readonly SSOToken $token;

    public function __construct(SSOToken $session)
    {
        $this->token = $session;
    }

    public function isResolved(): bool
    {
        return true;
    }

    public function getToken(): SSOToken
    {
        return $this->token;
    }
}