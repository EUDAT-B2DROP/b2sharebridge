<?php
/**
 * OwnCloud - B2sharebridge App
 *
 * PHP Version 5-7
 *
 * @category  Owncloud
 * @package   B2shareBridge
 * @author    EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright 2026 EUDAT
 * @license   AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link      https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */

namespace OCA\B2shareBridge\Publish;

use OCA\B2shareBridge\AppInfo\Application;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Provides an OIDC access token of the current user for use against the
 * B2SHARE (InvenioRDM) API, obtained via the user_oidc app.
 *
 * Two sources are supported:
 * - Internal token: a fresh token minted by the Nextcloud "oidc" identity
 *   provider app for the configured target audience (app config
 *   "oidc_audience"). Does not require the user_oidc "store_login_token"
 *   setting, but needs the "oidc" app (>= 1.4.0) with a client for the
 *   audience. Preferred if available.
 * - External login token: the login token of the external OpenID Connect
 *   provider the user logged into Nextcloud with. Requires user_oidc to be
 *   configured with "store_login_token=1".
 *
 * Since user_oidc is an optional dependency, all access is guarded by
 * class_exists() checks.
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class OidcTokenProvider
{
    /**
     * App config key holding the target audience (client id) for tokens
     * requested from the internal "oidc" identity provider app
     */
    public const CONFIG_OIDC_AUDIENCE = 'oidc_audience';

    protected IEventDispatcher $eventDispatcher;
    protected IAppConfig $appConfig;
    protected LoggerInterface $logger;
    protected string $externalEventClass;
    protected string $internalEventClass;

    /**
     * Create the OIDC token provider
     *
     * @param IEventDispatcher $eventDispatcher    Nextcloud event dispatcher
     * @param IAppConfig       $appConfig          Access to app configuration
     * @param LoggerInterface  $logger             A logger
     * @param string           $externalEventClass user_oidc external token event class (test seam)
     * @param string           $internalEventClass user_oidc internal token event class (test seam)
     */
    public function __construct(
        IEventDispatcher $eventDispatcher,
        IAppConfig $appConfig,
        LoggerInterface $logger,
        string $externalEventClass = 'OCA\\UserOIDC\\Event\\ExternalTokenRequestedEvent',
        string $internalEventClass = 'OCA\\UserOIDC\\Event\\InternalTokenRequestedEvent'
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->appConfig = $appConfig;
        $this->logger = $logger;
        $this->externalEventClass = $externalEventClass;
        $this->internalEventClass = $internalEventClass;
    }

    /**
     * Check if any OIDC token source could be available
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->isInternalAvailable() || $this->isExternalAvailable();
    }

    /**
     * Check if the internal token source (Nextcloud "oidc" identity provider
     * app) is usable, i.e. the user_oidc app provides the internal token event
     * and a target audience is configured for this app
     *
     * @return bool
     */
    public function isInternalAvailable(): bool
    {
        return class_exists($this->internalEventClass) && $this->getAudience() !== '';
    }

    /**
     * Check if the external token source (login token of the external OIDC
     * provider) is usable, i.e. user_oidc is installed and stores login tokens
     *
     * @return bool
     */
    public function isExternalAvailable(): bool
    {
        return class_exists($this->externalEventClass)
            && $this->appConfig->getValueString('user_oidc', 'store_login_token', '0', true) === '1';
    }

    /**
     * Get the configured target audience for internal tokens
     *
     * @return string Empty string if not configured
     */
    public function getAudience(): string
    {
        return $this->appConfig->getValueString(Application::APP_ID, self::CONFIG_OIDC_AUDIENCE, '', true);
    }

    /**
     * Get an OIDC access token for the current user.
     *
     * Prefers a freshly minted internal token for the configured audience,
     * falling back to the stored login token of the external OIDC provider.
     *
     * Attention: both tokens are bound to the user session, so they can only
     * be obtained within logged-in web requests. Background jobs run in an
     * unauthenticated context and will get null.
     *
     * @return string|null null if no (usable) OIDC access token is available
     */
    public function getOidcAccessToken(): string|null
    {
        if ($this->isInternalAvailable()) {
            $token = $this->requestInternalToken();
            if ($token !== null) {
                return $token;
            }
        }

        if ($this->isExternalAvailable()) {
            return $this->requestExternalToken();
        }

        return null;
    }

    /**
     * Request a token minted by the internal "oidc" identity provider app
     *
     * @return string|null null if no (usable) token was generated
     */
    private function requestInternalToken(): string|null
    {
        try {
            $eventClass = $this->internalEventClass;
            $event = new $eventClass($this->getAudience());
            $this->eventDispatcher->dispatchTyped($event);
            $token = $event->getToken();
            if ($token === null) {
                // oidc app missing, not enabled, or no client for the audience
                return null;
            }
            if ($token->isExpired()) {
                return null;
            }
            return $token->getAccessToken();
        } catch (Throwable $e) {
            // never log the token itself, only the failure reason
            $this->logger->warning(
                'Could not obtain internal OIDC token: ' . $e->getMessage(),
                ['app' => Application::APP_ID]
            );
            return null;
        }
    }

    /**
     * Request the stored login token of the external OIDC provider
     *
     * @return string|null null if no (usable) token is available
     */
    private function requestExternalToken(): string|null
    {
        try {
            $eventClass = $this->externalEventClass;
            $event = new $eventClass();
            $this->eventDispatcher->dispatchTyped($event);
            $token = $event->getToken();
            if ($token === null) {
                // user not logged in via OIDC or no token stored
                return null;
            }
            if ($token->isExpired()) {
                // user_oidc could not refresh the stored token
                return null;
            }
            return $token->getAccessToken();
        } catch (Throwable $e) {
            // never log the token itself, only the failure reason
            $this->logger->warning(
                'Could not obtain OIDC login token: ' . $e->getMessage(),
                ['app' => Application::APP_ID]
            );
            return null;
        }
    }
}
