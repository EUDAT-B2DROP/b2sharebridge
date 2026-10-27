<?php
/**
 * B2SHAREBRIDGE
 *
 * PHP Version 7
 *
 * @category  Nextcloud
 * @package   B2shareBridge
 * @author    EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright 2026 EUDAT
 * @license   AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link      https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */

namespace OCA\B2shareBridge\Tests\Publish;

use OCA\B2shareBridge\Model\Server;
use OCA\B2shareBridge\Publish\B2ShareV3;
use OCA\B2shareBridge\Publish\OidcTokenProvider;
use OCA\B2shareBridge\Util\Curl;
use OCP\IConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class B2ShareV3Test extends TestCase
{
    private const VALID_RESPONSE = '{"hits":[]}';
    private const INVALID_RESPONSE = '{"status":403}';

    private LoggerInterface $logger;
    private IConfig $config;
    private Curl $curl;
    private Server $server;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->config = $this->createMock(IConfig::class);
        $this->curl = $this->createMock(Curl::class);

        $this->server = new Server();
        $this->server->setId(1);
        $this->server->setPublishUrl('https://b2share.example.com');
        $this->server->setVersion(3);
    }

    private function createPublisher(?OidcTokenProvider $oidcTokenProvider = null): B2ShareV3
    {
        return new B2ShareV3(
            'b2sharebridge',
            $this->config,
            $this->logger,
            $this->curl,
            $oidcTokenProvider
        );
    }

    private function createOidcProvider(?string $token): OidcTokenProvider
    {
        $provider = $this->createMock(OidcTokenProvider::class);
        $provider
            ->method('getOidcAccessToken')
            ->willReturn($token);
        return $provider;
    }

    public function testOidcTokenIsPreferred()
    {
        $this->curl
            ->method('request')
            ->willReturn(self::VALID_RESPONSE);
        $this->config
            ->expects($this->never())
            ->method('getUserValue');

        $publisher = $this->createPublisher($this->createOidcProvider('oidc-jwt'));

        $this->assertEquals('oidc-jwt', $publisher->getAccessToken($this->server, 'user'));
    }

    public function testFallsBackToManualWhenOidcTokenIsRejected()
    {
        $this->curl
            ->method('request')
            ->willReturnOnConsecutiveCalls(self::INVALID_RESPONSE, self::VALID_RESPONSE);
        $this->config
            ->method('getUserValue')
            ->willReturn('manual-token');

        $publisher = $this->createPublisher($this->createOidcProvider('oidc-jwt'));

        $this->assertEquals('manual-token', $publisher->getAccessToken($this->server, 'user'));
    }

    public function testManualTokenWithoutProvider()
    {
        $this->curl
            ->method('request')
            ->willReturn(self::VALID_RESPONSE);
        $this->config
            ->method('getUserValue')
            ->willReturn('manual-token');

        $publisher = $this->createPublisher();

        $this->assertEquals('manual-token', $publisher->getAccessToken($this->server, 'user'));
    }

    public function testManualTokenWhenProviderHasNoToken()
    {
        $this->curl
            ->method('request')
            ->willReturn(self::VALID_RESPONSE);
        $this->config
            ->method('getUserValue')
            ->willReturn('manual-token');

        $publisher = $this->createPublisher($this->createOidcProvider(null));

        $this->assertEquals('manual-token', $publisher->getAccessToken($this->server, 'user'));
    }

    public function testReturnsNullWhenNoTokenIsValid()
    {
        $this->curl
            ->method('request')
            ->willReturn(self::INVALID_RESPONSE);
        $this->config
            ->method('getUserValue')
            ->willReturn('manual-token');

        $publisher = $this->createPublisher($this->createOidcProvider('oidc-jwt'));

        $this->assertNull($publisher->getAccessToken($this->server, 'user'));
    }

    public function testDisplayReturnsMarkerForValidOidcToken()
    {
        $this->curl
            ->method('request')
            ->willReturn(self::VALID_RESPONSE);

        $publisher = $this->createPublisher($this->createOidcProvider('oidc-jwt'));

        $this->assertEquals(
            B2ShareV3::OIDC_TOKEN_MARKER,
            $publisher->getAccessTokenDisplay($this->server, 'user')
        );
    }

    public function testDisplayReturnsManualToken()
    {
        $this->curl
            ->method('request')
            ->willReturn(self::VALID_RESPONSE);
        $this->config
            ->method('getUserValue')
            ->willReturn('manual-token');

        $publisher = $this->createPublisher($this->createOidcProvider(null));

        $this->assertEquals('manual-token', $publisher->getAccessTokenDisplay($this->server, 'user'));
    }

    public function testDisplayReturnsEmptyStringWithoutToken()
    {
        $this->curl
            ->method('request')
            ->willReturn(self::INVALID_RESPONSE);
        $this->config
            ->method('getUserValue')
            ->willReturn(null);

        $publisher = $this->createPublisher($this->createOidcProvider(null));

        $this->assertEquals('', $publisher->getAccessTokenDisplay($this->server, 'user'));
    }
}
