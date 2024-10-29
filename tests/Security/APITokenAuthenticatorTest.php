<?php

namespace Mittwald\MStudio\Bundle\Security;

use Mittwald\ApiClient\Generated\V2\Clients\User\GetUser\GetUserOKResponse;
use Mittwald\ApiClient\Generated\V2\Clients\User\GetUser\GetUserRequest;
use Mittwald\ApiClient\Generated\V2\Clients\User\UserClient;
use Mittwald\ApiClient\Generated\V2\Schemas\Commons\Person;
use Mittwald\ApiClient\Generated\V2\Schemas\User\User;
use Mittwald\ApiClient\MittwaldAPIV2Client;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Random\RandomException;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use function PHPUnit\Framework\any;
use function PHPUnit\Framework\assertThat;
use function PHPUnit\Framework\callback;
use function PHPUnit\Framework\equalTo;
use function PHPUnit\Framework\isFalse;
use function PHPUnit\Framework\isNull;
use function PHPUnit\Framework\isTrue;
use function PHPUnit\Framework\logicalNot;
use function PHPUnit\Framework\once;

#[CoversClass(APITokenAuthenticator::class)]
class APITokenAuthenticatorTest extends TestCase
{
    /**
     * @return array{Request, string}[]
     * @throws RandomException
     */
    public static function requestsWithToken(): array
    {
        $token = base64_encode(random_bytes(16));

        $requestWithTokenInXHeader = new Request();
        $requestWithTokenInXHeader->headers->set("X-Access-Token", $token);

        $requestWithBearerToken = new Request();
        $requestWithBearerToken->headers->set('Authorization', "Bearer {$token}");

        $requestWithTokenAsPassword = new Request();
        $requestWithTokenAsPassword->headers->set('Authorization', "Basic " . base64_encode("foo:{$token}"));

        return [
            [$requestWithTokenInXHeader, $token],
            [$requestWithBearerToken, $token],
            [$requestWithTokenAsPassword, $token],
        ];
    }

    #[Test]
    #[DataProvider('requestsWithToken')]
    public function supportsRequestWithToken(Request $request, string $token): void
    {
        $factory = $this
            ->getMockBuilder(APIClientFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sut = new APITokenAuthenticator($factory, new NullLogger());

        assertThat($sut->supports($request), isTrue());
    }

    #[Test]
    public function doesNotSupportRequestsWithoutToken(): void
    {
        $request = new Request();

        $factory = $this
            ->getMockBuilder(APIClientFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sut = new APITokenAuthenticator($factory, new NullLogger());

        assertThat($sut->supports($request), isFalse());
    }

    #[Test]
    #[DataProvider('requestsWithToken')]
    public function authenticatesRequestWithCorrectUser(Request $request, string $token): void
    {
        $response = new GetUserOKResponse(
            new User(
                userId: '7317f027-e122-440f-a71f-a8cf767e98b2',
                person: new Person(
                    firstName: "Max",
                    lastName: "Mustermann"
                ),
            ),
        );

        $userClient = $this->getMockBuilder(UserClient::class)->getMock();
        $userClient
            ->expects(once())
            ->method('getUser')
            ->with(callback(fn(GetUserRequest $r) => $r->getUserId() === "self"))
            ->willReturn($response);

        $client = $this->getMockBuilder(MittwaldAPIV2Client::class)->disableOriginalConstructor()->getMock();
        $client->expects(any())->method('user')->willReturn($userClient);

        $factory = $this->getMockBuilder(APIClientFactory::class)->disableOriginalConstructor()->getMock();
        $factory->expects(any())->method('buildAPIClientForToken')->with($token)->willReturn($client);

        $sut = new APITokenAuthenticator($factory, new NullLogger());

        $passport = $sut->authenticate($request);
        $user     = $passport->getUser();

        assertThat($user, logicalNot(isNull()));
        assertThat($user->getUserIdentifier(), equalTo('7317f027-e122-440f-a71f-a8cf767e98b2'));
    }
}