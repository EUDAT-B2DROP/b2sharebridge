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
use OCA\B2shareBridge\Model\CommunityMapper;
use OCA\B2shareBridge\Model\DepositStatusMapper;
use OCA\B2shareBridge\Model\DepositFileMapper;
use OCA\B2shareBridge\Model\ServerMapper;
use OCA\B2shareBridge\Model\StatusCodes;
use OCA\B2shareBridge\Cron\B2shareCommunityFetcher;
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
            'CommunityMapper',
            function () use ($server) {
                return new CommunityMapper(
                    $server->getDatabaseConnection()
                );
            }
        );
        $container->registerService(
            'DepositStatusMapper',
            function () use ($server) {
                return new DepositStatusMapper(
                    $server->getDatabaseConnection()
                );
            }
        );
        $container->registerService(
            'DepositFileMapper',
            function () use ($server) {
                return new DepositFileMapper(
                    $server->getDatabaseConnection()
                );
            }
        );
        $container->registerService(
            'ServerMapper',
            function () use ($server) {
                return new ServerMapper(
                    $server->getDatabaseConnection()
                );
            }
        );
        $container->registerService(
            'StatusCodes',
            function () use ($server) {
                return new StatusCodes(
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
                    $c->query('DepositFileMapper'),
                    $c->query('StatusCodes'),
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
                    $c->query('DepositFileMapper'),
                    $c->query('CommunityMapper'),
                    $c->query('ServerMapper'),
                    $c->query('StatusCodes'),
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
                $checkssl = $server->getConfig()
                    ->getAppValue('b2sharebridge', 'check_ssl', '1');
                return new $backend($checkssl);
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
        return;
    }

    /**
     * Register Settings pages
     *
     * @return null
     */
    public function registerSettings()
    {
        return;
    }

    /**
     * Register Jobs
     *
     * @return null
     */
    public function registerJobs()
    {
        //\OCP\BackgroundJob::registerJob(
        //    'OCA\B2shareBridge\Cron\B2shareCommunityFetcher'
        //);
        \OC::$server->getJoblist()->add(B2ShareCommunityFetcher::class);
        return;
    }

    /**
     * Load additional javascript files
     *
     * @return null
     */
    public static function loadScripts()
    {
        Util::addScript('files', 'detailtabview');
        Util::addScript('b2sharebridge', 'b2sharebridgecollection');
        Util::addScript('b2sharebridge', 'b2sharebridgetabview');
        Util::addScript('b2sharebridge', 'b2sharebridge');
        Util::addStyle('b2sharebridge', 'b2sharebridgetabview');
        return;
    }
}
