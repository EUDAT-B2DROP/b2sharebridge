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

use OCA\B2shareBridge\AppInfo\Application;
use OCA\B2shareBridge\Publish\OidcTokenProvider;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Stand-in for the user_oidc ExternalTokenRequestedEvent (test seam of
 * OidcTokenProvider avoids a hard dependency on the user_oidc app).
 */
class TestExternalTokenEvent extends \OCP\EventDispatcher\Event {
    private $token = null;

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token): void
    {
        $this->token = $token;
    }
}

/**
 * Stand-in for the user_oidc InternalTokenRequestedEvent.
 */
class TestInternalTokenEvent extends \OCP\EventDispatcher\Event {
    private string $targetAudience;
    private array $extraScopes;
    private string $resource;
    private $token = null;

    public function __construct(string $targetAudience, array $extraScopes = [], string $resource = '') {
        parent::__construct();
        $this->targetAudience = $targetAudience;
        $this->extraScopes = $extraScopes;
        $this->resource = $resource;
    }

    public function getTargetAudience(): string
    {
        return $this->targetAudience;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token): void
    {
        $this->token = $token;
    }
}

/**
 * Stand-in for OCA\UserOIDC\Model\Token with the methods used by OidcTokenProvider.
 */
class TestOidcToken {
    private string $accessToken;
    private bool $expired;

    public function __construct(string $accessToken, bool $expired = false) {
        $this->accessToken = $accessToken;
        $this->expired = $expired;
    }

    public function getAccessToken(): string {
        return $this->accessToken;
    }

    public function isExpired(): bool {
        return $this->expired;
    }
}

class OidcTokenProviderTest extends TestCase
{
    private IEventDispatcher $eventDispatcher;
    private IAppConfig $appConfig;
    private LoggerInterface $logger;

    /** @var array<string, string> app id + key => value */
    private array $configValues = [];

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(IEventDispatcher::class);
        $this->appConfig = $this->createMock(IAppConfig::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->configValues = [];

        $this->appConfig
            ->method('getValueString')
            ->willReturnCallback(function (string $app, string $key, string $default = '') {
                return $this->configValues[$app . '/' . $key] ?? $default;
            });
    }

    private function createProvider(): OidcTokenProvider
    {
        return new OidcTokenProvider(
            $this->eventDispatcher,
            $this->appConfig,
            $this->logger,
            TestExternalTokenEvent::class,
            TestInternalTokenEvent::class
        );
    }

    private function setStoreLoginToken(string $value): void
    {
        $this->configValues['user_oidc/store_login_token'] = $value;
    }

    private function setAudience(string $value): void
    {
        $this->configValues[Application::APP_ID . '/' . OidcTokenProvider::CONFIG_OIDC_AUDIENCE] = $value;
    }

    /**
     * Dispatch fake events: set the given token (or throw) per event type.
     */
    private function dispatchReturns(?TestOidcToken $external, ?TestOidcToken $internal, bool $throw = false): void
    {
        $this->eventDispatcher
            ->method('dispatchTyped')
            ->willReturnCallback(function ($event) use ($external, $internal, $throw) {
                if ($throw) {
                    throw new \Exception('token request failed');
                }
                if ($event instanceof TestInternalTokenEvent) {
                    $event->setToken($internal);
                } elseif ($event instanceof TestExternalTokenEvent) {
                    $event->setToken($external);
                }
            });
    }

    public function testIsNotAvailableWithoutAnySource()
    {
        $provider = $this->createProvider();

        $this->assertFalse($provider->isAvailable());
        $this->assertNull($provider->getOidcAccessToken());
    }

    public function testIsNotAvailableIfEventClassesMissing()
    {
        $this->setStoreLoginToken('1');
        $this->setAudience('b2share-audience');

        $provider = new OidcTokenProvider(
            $this->eventDispatcher,
            $this->appConfig,
            $this->logger,
            'OCA\\UserOIDC\\Event\\ThisEventDoesNotExist',
            'OCA\\UserOIDC\\Event\\ThisEventDoesNotExistEither'
        );

        $this->assertFalse($provider->isInternalAvailable());
        $this->assertFalse($provider->isExternalAvailable());
        $this->assertNull($provider->getOidcAccessToken());
    }

    public function testExternalSourceIsAvailableWithStoreLoginToken()
    {
        $this->setStoreLoginToken('1');

        $this->assertTrue($this->createProvider()->isExternalAvailable());
    }

    public function testInternalSourceIsAvailableWithAudience()
    {
        $this->setAudience('b2share-audience');

        $this->assertTrue($this->createProvider()->isInternalAvailable());
    }

    public function testInternalTokenIsPreferredAndUsesAudience()
    {
        $this->setStoreLoginToken('1');
        $this->setAudience('b2share-audience');

        $capturedAudience = null;
        $this->eventDispatcher
            ->method('dispatchTyped')
            ->willReturnCallback(function ($event) use (&$capturedAudience) {
                if ($event instanceof TestInternalTokenEvent) {
                    $capturedAudience = $event->getTargetAudience();
                    $event->setToken(new TestOidcToken('internal-token'));
                } else {
                    $event->setToken(new TestOidcToken('external-login-token'));
                }
            });

        $this->assertEquals('internal-token', $this->createProvider()->getOidcAccessToken());
        $this->assertEquals('b2share-audience', $capturedAudience);
    }

    public function testFallsBackToExternalIfInternalHasNoToken()
    {
        $this->setStoreLoginToken('1');
        $this->setAudience('b2share-audience');
        $this->dispatchReturns(new TestOidcToken('external-login-token'), null);

        $this->assertEquals('external-login-token', $this->createProvider()->getOidcAccessToken());
    }

    public function testFallsBackToExternalIfInternalTokenIsExpired()
    {
        $this->setStoreLoginToken('1');
        $this->setAudience('b2share-audience');
        $this->dispatchReturns(new TestOidcToken('external-login-token'), new TestOidcToken('internal-token', true));

        $this->assertEquals('external-login-token', $this->createProvider()->getOidcAccessToken());
    }

    public function testFallsBackToExternalIfInternalRequestThrows()
    {
        $this->setStoreLoginToken('1');
        $this->setAudience('b2share-audience');

        $this->eventDispatcher
            ->method('dispatchTyped')
            ->willReturnCallback(function ($event) {
                if ($event instanceof TestInternalTokenEvent) {
                    throw new \Exception('oidc app error');
                }
                $event->setToken(new TestOidcToken('external-login-token'));
            });

        $this->assertEquals('external-login-token', $this->createProvider()->getOidcAccessToken());
    }

    public function testExternalTokenIsValid()
    {
        $this->setStoreLoginToken('1');
        $this->dispatchReturns(new TestOidcToken('external-login-token'), null);

        $this->assertEquals('external-login-token', $this->createProvider()->getOidcAccessToken());
    }

    public function testReturnsNullIfExternalTokenIsExpired()
    {
        $this->setStoreLoginToken('1');
        $this->dispatchReturns(new TestOidcToken('external-login-token', true), null);

        $this->assertNull($this->createProvider()->getOidcAccessToken());
    }

    public function testReturnsNullIfExternalRequestThrows()
    {
        $this->setStoreLoginToken('1');
        $this->dispatchReturns(null, null, true);
        $this->logger
            ->method('warning')
            ->with($this->stringContains('Could not obtain OIDC login token'));

        $this->assertNull($this->createProvider()->getOidcAccessToken());
    }
}
