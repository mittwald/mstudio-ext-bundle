<?php

namespace Mittwald\MStudio\Bundle\Security;

use Mittwald\MStudio\Authentication\AuthenticationError;
use Mittwald\MStudio\Authentication\AuthenticationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Uid\Uuid;

class TokenRetrievalKeyAuthenticator extends AbstractAuthenticator
{
    const ATREK_QUERY_PARAM = "accessTokenRetrievalKey";
    const USERID_QUERY_PARAM = "userId";

    readonly private AuthenticationService $authenticationService;

    public function __construct(AuthenticationService $authenticationService)
    {
        $this->authenticationService = $authenticationService;
    }

    public function supports(Request $request): ?bool
    {
        return $request->query->has(self::ATREK_QUERY_PARAM);
    }

    public function authenticate(Request $request): Passport
    {
        $userId            = $request->query->get(self::USERID_QUERY_PARAM);
        $tokenRetrievalKey = $request->query->get(self::ATREK_QUERY_PARAM);

        try {
            $token = $this->authenticationService->authenticate(
                userId: $userId,
                tokenRetrievalKey: $tokenRetrievalKey,
            );

            $loader = function () use ($userId, $token) {
                return new User(Uuid::fromString($userId), $token);
            };

            $passport = new SelfValidatingPassport(new UserBadge($userId, $loader));
            $passport->addBadge(new TokenRetrievalKeyBadge($token));

            return $passport;
        } catch (AuthenticationError $err) {
            throw new AuthenticationException($err->getMessage(), previous: $err);
        }
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['message' => $exception->getMessage()], Response::HTTP_UNAUTHORIZED);
    }

}