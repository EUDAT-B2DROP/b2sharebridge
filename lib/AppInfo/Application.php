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
use OCP\Util;


use OCA\B2shareBridge\Controller\B2shareBridge;
use OCA\B2shareBridge\Controller\ViewController;
use OCA\B2shareBridge\Db\FilecacheStatusMapper;
use OCA\B2shareBridge\Db\StatusCodeMapper;

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
        $server = $container->getServer();

        $container->registerService(
            'EudatL10N',
            function (IContainer $c) {
                return $c->query('ServerContainer')->getL10N('b2sharebridge');
            }
        );

        $container->registerService(
            'FilecacheStatusMapper',
            function () use ($server) {
                return new FilecacheStatusMapper(
                    $server->getDatabaseConnection()
                );
            }
        );
        
        $container->registerService(
            'StatusCodeMapper',
            function () use ($server) {
                return new StatusCodeMapper(
                    $server->getDatabaseConnection()
                );
            }
        );

        $container->registerService(
            'B2shareBridgeController',
            function (IContainer $c) use ($server) {
                return new B2shareBridge(
                    $c->query('AppName'),
                    $server->getRequest(),
                    $server->getConfig(),
                    $c->query('FilecacheStatusMapper'),
                    $c->query('StatusCodeMapper'),
                    $c->query('CurrentUID')
                );
            }
        );

        $container->registerService(
            'ViewController',
            function (IContainer $c) use ($server) {
                return new ViewController(
                    $c->query('AppName'),
                    $server->getRequest(),
                    $server->getConfig(),
                    $c->query('FilecacheStatusMapper'),
                    $c->query('StatusCodeMapper'),
                    $c->query('CurrentUID')
                );
            }
        );

        $container->registerService(
            'CurrentUID',
            function () use ($server) {
                $user = $server->getUserSession()->getUser();
                return ($user) ? $user->getUID() : '';
            }
        );

        $container->registerService(
            'PublishBackend',
            function () use ($server) {

                /* TODO: we could inject the publish backend via config.
                 * $backend = $server->getConfig()
                 *              ->getAppValue('eudat', 'publish_backend');
                 */
                $backend = 'OCA\B2shareBridge\Publish\B2share';
                $baseurl = $server->getConfig()
                    ->getAppValue('b2sharebridge', 'publish_baseurl');
                return new $backend($baseurl);
            }
        );
    }


    /**
     * Register Navigation Entry
     *
     * @return null
     */
    public function registerNavigationEntry()
    {
        $c = $this->getContainer();
        $server = $c->getServer();

        $navigationEntry = function () use ($c, $server) {
            return [
                'id' => $c->getAppName(),
                'order' => 100,
                'name' => 'B2SHARE',
                'href' => $server->getURLGenerator()
                    ->linkToRoute('b2sharebridge.View.index'),
                'icon' => $server->getURLGenerator()
                    ->imagePath('b2sharebridge', 'appbrowsericon.svg'),
            ];
        };
        $server->getNavigationManager()->add($navigationEntry);
    }

    /**
     * Register settings pages
     *
     * @return null
     */
    public function registerSettings()
    {
        \OCP\App::registerAdmin('b2sharebridge', 'lib/settings/admin');
        \OCP\App::registerPersonal('b2sharebridge', 'lib/settings/personal');
    }


    /**
     * Load additional javascript files
     *
     * @return null
     */
    public static function loadScripts()
    {
        Util::addScript('b2sharebridge', 'fileactions');
    }
}
