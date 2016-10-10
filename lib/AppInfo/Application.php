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


use OCA\B2shareBridge\Controller\PublishController;
use OCA\B2shareBridge\Controller\ViewController;
use OCA\B2shareBridge\Data;
use OCA\B2shareBridge\Db\DepositStatusMapper;
use OCA\B2shareBridge\Db\StatusCodeMapper;
use OCA\B2shareBridge\View\Navigation;
use OCP\AppFramework\App;
use OCP\IContainer;
use OCP\Util;


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
            'DepositStatusMapper',
            function () use ($server) {
                return new DepositStatusMapper(
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
            'PublishController',
            function (IContainer $c) use ($server) {
                return new PublishController(
                    $c->query('AppName'),
                    $server->getRequest(),
                    $server->getConfig(),
                    $c->query('DepositStatusMapper'),
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
                    $c->query('Request'),
                    $server->getConfig(),
                    $c->query('DepositStatusMapper'),
                    $c->query('StatusCodeMapper'),
                    $c->query('CurrentUID'),
                    $c->query('Navigation')
                );
            }
        );

        $container->registerService(
            'Navigation',
            function (IContainer $c) {
                $server = $c->query('ServerContainer');

                return new Navigation(
                    $server->getURLGenerator()
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
                    ->linkToRoute('b2sharebridge.View.depositList'),
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
