<?php
/**
 * OwnCloud - B2sharebridge App
 *
 * PHP Version 5-7
 *
 * @category  Owncloud
 * @package   B2shareBridge
 * @author    EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright 2015 EUDAT
 * @license   AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link      https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */

namespace OCA\B2shareBridge\AppInfo;

use OCP\AppFramework\App;
use OCP\IContainer;

use OCA\B2shareBridge\Controller\B2shareBridge;
use OCA\B2shareBridge\Db\FilecacheStatusMapper;
use OCA\B2shareBridge\Publish;

/**
 * Implement a ownCloud Application for our b2sharebridge
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class Application extends App
{
    /**
     * Create a ownCloud application
     *
     * @param array(string) $urlParams a list of url parameters
     */
    public function __construct(array $urlParams = array())
    {
        parent::__construct('b2sharebridge', $urlParams);
        $container = $this->getContainer();

        $container->registerService(
            'EudatL10N',
            function (IContainer $c) {
                return $c->query('ServerContainer')->getL10N('b2sharebridge');
            }
        );

        $container->registerService(
            'FilecacheStatusMapper',
            function (IContainer $c) {
                $server = $c->query('ServerContainer');
                return new FilecacheStatusMapper(
                    $server->getDatabaseConnection()
                );
            }
        );

        $container->registerService(
            'B2shareBridgeController',
            function (IContainer $c) {
                $server = $c->query('ServerContainer');
                return new B2shareBridge(
                    $c->query('AppName'),
                    $server->getRequest(),
                    $server->getConfig(),
                    $c->query('FilecacheStatusMapper'),
                    $c->query('CurrentUID')
                );
            }
        );

        $container->registerService(
            'CurrentUID',
            function (IContainer $c) {
                /**
                 * Get current user ID
                 *
                 * @var \OC\Server $server
                 */
                $server = $c->query('ServerContainer');
                $user = $server->getUserSession()->getUser();
                return ($user) ? $user->getUID() : '';
            }
        );

        $container->registerService(
            'PublishBackend',
            function (IContainer $c) {
                $server = $c->query('ServerContainer');

                /* TODO: we could inject the publish backend via config.
                 * $backend = $server->getConfig()
                 *              ->getAppValue('eudat', 'publish_backend');
                 */
                $backend = 'OCA\B2shareBridge\Publish\Swift';
                $baseurl = $server->getConfig()
                    ->getAppValue('b2sharebridge', 'publish_baseurl');
                return new $backend($baseurl);
            }
        );
    }
}
