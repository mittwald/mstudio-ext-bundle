<?php

namespace Mittwald\MStudio\Bundle\Security;

use Mittwald\ApiClient\Generated\V2\Clients\User\GetUser\GetUserRequest;
use Mittwald\ApiClient\MittwaldAPIV2Client;
use Mittwald\MStudio\Authentication\SSOToken;
use Psr\Log\LoggerInterface;
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

class APITokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function supports(Request $request): ?bool
    {
        return $this->getAPITokenFromRequest($request) !== null;
    }

    private function getAPITokenFromRequest(Request $request): ?string
    {
        if ($request->headers->has('x-access-token')) {
            return $request->headers->get('x-access-token');
        }

        if ($request->headers->has('authorization')) {
            $authHeader = $request->headers->get('authorization');
            if (is_string($authHeader)) {
                if (preg_match('/Bearer (.+)/', $authHeader, $matches)) {
                    return $matches[1];
                }

                if (preg_match('/Basic (.+)/', $authHeader, $matches)) {
                    [, $password] = explode(':', base64_decode($matches[1]), limit: 2);
                    return $password;
                }
            }
        }

        return null;
    }

    public function authenticate(Request $request): Passport
    {
        $apiToken = $this->getAPITokenFromRequest($request);
        if ($apiToken === null) {
            throw new AuthenticationException('No API token provided');
        }

        $client = MittwaldAPIV2Client::newWithToken($apiToken);
        $user = $client->user()->getUser(new GetUserRequest("self"))->getBody();

        $this->logger->info('Authenticated user {user_id} with mStudio API token ', ['user_id' => $user->getUserId()]);

        $loader = function () use ($user, $apiToken) {
            return new User(Uuid::fromString($user->getUserId()), new SSOToken($apiToken));
        };

        $passport = new SelfValidatingPassport(new UserBadge($user->getUserId(), $loader));

        return $passport;
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