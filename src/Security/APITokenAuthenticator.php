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

/**
 * Authenticator that authenticates users based on existing mStudio API tokens.
 *
 * NOTE:
 * Using mStudio API tokens is NOT an officially supported way of authenticating
 * users and is explicitly DISCOURAGED for 3rd-party extensions.
 */
class APITokenAuthenticator extends AbstractAuthenticator
{
    private readonly LoggerInterface $logger;
    private readonly APIClientFactory $clientFactory;

    public function __construct(APIClientFactory $clientFactory, LoggerInterface $logger)
    {
        $this->clientFactory = $clientFactory;
        $this->logger = $logger;
    }

    /**
     * Tests if a request is supported for API token authentication.
     *
     * For this, either an `X-Access-Token` header or an `Authorization` header,
     * with either a Bearer token or an API token as a password must be present.
     */
    public function supports(Request $request): ?bool
    {
        return $this->getAPITokenFromRequest($request) !== null;
    }

    /**
     * Extract the API token from the request headers.
     */
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

    /**
     * Authenticates a user based on the API token present in a request.
     */
    public function authenticate(Request $request): Passport
    {
        $apiToken = $this->getAPITokenFromRequest($request);
        if ($apiToken === null) {
            throw new AuthenticationException('No API token provided');
        }

        $client = $this->clientFactory->buildAPIClientForToken($apiToken);
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