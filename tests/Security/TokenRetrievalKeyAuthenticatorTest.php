<?php
namespace Mittwald\MStudio\Bundle\Security;

use DateTime;
use Mittwald\MStudio\Authentication\AuthenticationService;
use Mittwald\MStudio\Authentication\SSOToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use function PHPUnit\Framework\assertThat;
use function PHPUnit\Framework\equalTo;
use function PHPUnit\Framework\identicalTo;
use function PHPUnit\Framework\isInstanceOf;
use function PHPUnit\Framework\isNull;
use function PHPUnit\Framework\isTrue;
use function PHPUnit\Framework\logicalNot;
use function PHPUnit\Framework\once;

#[CoversClass(TokenRetrievalKeyAuthenticator::class)]
class TokenRetrievalKeyAuthenticatorTest extends TestCase
{
    #[Test]
    public function supportsRequestWithTokenInQuery(): void
    {
        $retrievalKey = base64_encode(random_bytes(16));

        $request = new Request();
        $request->query->set('accessTokenRetrievalKey', $retrievalKey);

        $authService = $this
            ->getMockBuilder(AuthenticationService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sut = new TokenRetrievalKeyAuthenticator($authService);

        assertThat($sut->supports($request), isTrue());
    }

    #[Test]
    public function authenticatesRequestWithCorrectUser(): void
    {
        $retrievalKey = base64_encode(random_bytes(16));
        $userId = uuid_create(UUID_TYPE_RANDOM);

        $request = new Request();
        $request->query->set('accessTokenRetrievalKey', $retrievalKey);
        $request->query->set('userId', $userId);

        $token = new SSOToken(
            accessToken: base64_encode(random_bytes(16)),
            refreshToken: base64_encode(random_bytes(16)),
            expiresAt: new DateTime("now + 3 days"),
        );

        $authService = $this
            ->getMockBuilder(AuthenticationService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $authService
            ->expects(once())
            ->method('authenticate')
            ->with($userId, $retrievalKey)
            ->willReturn($token);

        $sut = new TokenRetrievalKeyAuthenticator($authService);
        $passport = $sut->authenticate($request);

        /** @var User $user */
        $user = $passport->getUser();

        assertThat($user, logicalNot(isNull()));
        assertThat($user->getUserIdentifier(), equalTo($userId));
        assertThat($user, isInstanceOf(User::class));
        assertThat($user->getToken(), identicalTo($token));
    }
}