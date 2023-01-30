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
use OCA\B2shareBridge\Controller\ServerController;
use OCA\B2shareBridge\Model\CommunityMapper;
use OCA\B2shareBridge\Model\DepositStatusMapper;
use OCA\B2shareBridge\Model\DepositFileMapper;
use OCA\B2shareBridge\Model\ServerMapper;
use OCA\B2shareBridge\Model\StatusCodes;
use OCA\B2shareBridge\Cron\B2shareCommunityFetcher;
use OCA\B2shareBridge\Publish\B2share;
use OCP\AppFramework\App;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IContainer;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\Util;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * Implement a ownCloud Application for our b2sharebridge
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class Application extends App implements IBootstrap
{
    public const APP_ID = 'b2sharebridge';

    /**
     * Create a ownCloud application
     *
     * @param array(string) $urlParams a list of url parameters
     */
    public function __construct(array $urlParams = array())
    {
        parent::__construct(self::APP_ID, $urlParams);
        $container = $this->getContainer();

        // Register files tab view
        $dispatcher = $container->get(IEventDispatcher::class);
        $dispatcher->addListener('OCA\Files::loadAdditionalScripts', function() {
            Util::addScript(self::APP_ID, 'b2sharebridge-filetabmain');
        });

        /**
         * Mappers
         */
        //Note: Why do they all show as deprecated?
        $container->registerService(CommunityMapper::class, function (ContainerInterface $c): CommunityMapper {
            return new CommunityMapper(
                $c->get(IDBConnection::class)
            );
        });

        $container->registerService(DepositStatusMapper::class, function (ContainerInterface $c): DepositStatusMapper {
            return new DepositStatusMapper(
                $c->get(IDBConnection::class),
                $c->get(LoggerInterface::class)
            );
        });

        $container->registerService(DepositFileMapper::class, function (ContainerInterface $c): DepositFileMapper {
            return new DepositFileMapper(
                $c->get(IDBConnection::class)
            );
        });

        $container->registerService(ServerMapper::class, function (ContainerInterface $c): ServerMapper {
            return new ServerMapper(
                $c->get(IDBConnection::class)
            );
        });

        /**
         * Services
         */
        //Note: does this qualify as a service?
        $container->registerService(StatusCodes::class, function (): StatusCodes {
            return new StatusCodes();
        });

        $container->registerService(B2share::class, function (ContainerInterface $c): B2share {
            return new B2share(
                $c->get(IConfig::class)
            );
        });

        //Note: this is done for backwards compatability
        $container->registerAlias(B2share::class, "PublishBackend");

        /**
         * Controller
         */
        $container->registerService(PublishController::class, function (ContainerInterface $c): PublishController {
            return new PublishController(
                $c->get('appName'),
                $c->get(IRequest::class),
                $c->get(IConfig::class),
                $c->get(DepositStatusMapper::class),
                $c->get(DepositFileMapper::class),
                $c->get(StatusCodes::class),
                $c->get(ITimeFactory::class),
                $c->get(B2share::class),
                $c->get(ServerMapper::class),
                $c->get("UserId")
            );
        });

        $container->registerService(ViewController::class, function (ContainerInterface $c): ViewController {
            return new ViewController(
                $c->get('appName'),
                $c->get(IRequest::class),
                $c->get(IConfig::class),
                $c->get(DepositStatusMapper::class),
                $c->get(DepositFileMapper::class),
                $c->get(CommunityMapper::class),
                $c->get(ServerMapper::class),
                $c->get(StatusCodes::class),
                $c->get("UserId")
            );
        });

        $container->registerService(ServerController::class, function (ContainerInterface $c): ServerController {
            return new ServerController(
                $c->get('appName'),
                $c->get(IRequest::class),
                $c->get(ServerMapper::class),
                $c->get("UserId")
            );
        });
    }


    /**
     * Register Navigation Entry
     *
     * @return null
     */
    /*public function registerNavigationEntry()
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
    }*/

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
        \OC::$server->getJoblist()->add(B2shareCommunityFetcher::class);
        return;
    }

    /**
     * Load additional javascript files
     *
     * @return null
     */
    public static function loadScripts()
    {
        //Util::addScript('files', 'detailtabview');

        /*Util::addScript(self::APP_ID, 'b2sharebridgecollection');
        Util::addScript(self::APP_ID, 'b2sharebridgetabview');
        Util::addScript(self::APP_ID, 'b2sharebridge');
        Util::addStyle(self::APP_ID, 'b2sharebridgetabview');*/
        Util::addScript(self::APP_ID, "b2sharebridge-settingsadmin");
        Util::addScript(self::APP_ID, "b2sharebridge-settingspersonal");
    }

    public function register(IRegistrationContext $context): void
    {
        // Register the composer autoloader for packages shipped by this app, if applicable
        //include_once __DIR__ . '/../../vendor/autoload.php';
    }

    public function boot(IBootContext $context): void
    {
        //$this->registerNavigationEntry();
        $this->loadScripts();
        $this->registerSettings();
        $this->registerJobs();
    }

}

