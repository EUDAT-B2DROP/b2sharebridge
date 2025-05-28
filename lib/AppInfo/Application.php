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
use OCA\B2shareBridge\Controller\ServerController;
use OCA\B2shareBridge\Controller\ViewController;
use OCA\B2shareBridge\Model\CommunityMapper;
use OCA\B2shareBridge\Model\DepositStatusMapper;
use OCA\B2shareBridge\Model\DepositFileMapper;
use OCA\B2shareBridge\Model\ServerMapper;
use OCA\B2shareBridge\Model\StatusCodes;
use OCA\B2shareBridge\Notification\Notifier;
use OCA\B2shareBridge\Publish\B2ShareV2;
use OCA\B2shareBridge\Publish\B2ShareV3;
use OCA\B2shareBridge\Util\Curl;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Notification\IManager;
use OCP\Util;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * Implement a Nextcloud Application for our b2sharebridge
 *
 * @category Nextcloud
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
     * @param array $urlParams a list of url parameters
     * 
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(array $urlParams = [])
    {
        parent::__construct(self::APP_ID, $urlParams);
        $container = $this->getContainer();

        // Register files tab view
        $dispatcher = $container->get(IEventDispatcher::class);

        $dispatcher->addListener(
            LoadAdditionalScriptsEvent::class,
            function () {
                Util::addScript(self::APP_ID, 'b2sharebridge-filetabmain');
            }
        );
    }

    /**
     * Load additional javascript files
     * 
     * @deprecated 
     * 
     * @return void
     */
    public static function loadScripts()
    {
        /* Removed and moved to listener
        Util::addScript(self::APP_ID, "b2sharebridge-settingsadmin");
        Util::addScript(self::APP_ID, "b2sharebridge-settingspersonal");
        */
    }

    /**
     * Register Services into Context
     * 
     * @param \OCP\AppFramework\Bootstrap\IRegistrationContext $context Context
     * 
     * @return void
     */
    public function register(IRegistrationContext $context): void
    {
        // Register the composer autoloader for packages shipped by this app, if applicable
        //include_once __DIR__ . '/../../vendor/autoload.php';
        /**
         * Mappers
         */
        //Note: Why do they all show as deprecated?
        $context->registerService(
            CommunityMapper::class,
            function (ContainerInterface $c): CommunityMapper {
                return new CommunityMapper(
                    $c->get(IDBConnection::class)
                );
            }
        );

        $context->registerService(
            DepositStatusMapper::class,
            function (ContainerInterface $c): DepositStatusMapper {
                return new DepositStatusMapper(
                    $c->get(IDBConnection::class),
                    $c->get(LoggerInterface::class)
                );
            }
        );

        $context->registerService(
            DepositFileMapper::class,
            function (ContainerInterface $c): DepositFileMapper {
                return new DepositFileMapper(
                    $c->get(IDBConnection::class)
                );
            }
        );

        $context->registerService(
            ServerMapper::class,
            function (ContainerInterface $c): ServerMapper {
                return new ServerMapper(
                    $c->get(IDBConnection::class)
                );
            }
        );

        /**
         * Services
         */
        //Note: does this qualify as a service?
        $context->registerService(
            StatusCodes::class,
            function (): StatusCodes {
                return new StatusCodes();
            }
        );

        $context->registerService(
            B2ShareV2::class,
            function (ContainerInterface $c): B2shareV2 {
                return new B2ShareV2(
                    $c->get(IConfig::class),
                    $c->get(LoggerInterface::class),
                    $c->get(Curl::class)
                );
            }
        );

        $context->registerService(
            B2ShareV3::class,
            function (ContainerInterface $c): B2shareV3 {
                return new B2ShareV3(
                    $c->get(IConfig::class),
                    $c->get(LoggerInterface::class),
                    $c->get(Curl::class)
                );
            }
        );

        $context->registerService(
            Curl::class,
            function (ContainerInterface $c): Curl {
                return new Curl(
                    $c->get(LoggerInterface::class)
                );
            }
        );

        //Note: this is done for backwards compatability
        //$context->registerAlias(B2share::class, "PublishBackend");

        /**
         * Settings
         */
        /*$context->registerService(
            Personal::class, function (ContainerInterface $c): Personal {
                return new Personal(
                    $c->get(IConfig::class),
                    $c->get(ServerMapper::class),
                    $c->get("UserId")
                );
            }
        );

        $context->registerService(
            Admin::class, function (ContainerInterface $c): Admin {
            return new Admin(
                $c->get(IConfig::class),
                $c->get(ServerMapper::class),
            );
        }
        );*/

        /**
         * Controller
         */
        $context->registerService(
            PublishController::class,
            function (ContainerInterface $c): PublishController {
                return new PublishController(
                    $c->get('appName'),
                    $c->get(IRequest::class),
                    $c->get(IConfig::class),
                    $c->get(DepositStatusMapper::class),
                    $c->get(DepositFileMapper::class),
                    $c->get(StatusCodes::class),
                    $c->get(ITimeFactory::class),
                    $c->get(ServerMapper::class),
                    $c->get(CommunityMapper::class),
                    $c->get(IManager::class),
                    $c->get(LoggerInterface::class),
                    $c->get(IJobList::class),
                    $c->get(IRootFolder::class),
                    $c->get("userId")
                );
            }
        );

        $context->registerService(
            ViewController::class,
            function (ContainerInterface $c): ViewController {
                return new ViewController(
                    $c->get('appName'),
                    $c->get(IRequest::class),
                    $c->get(IConfig::class),
                    $c->get(DepositStatusMapper::class),
                    $c->get(DepositFileMapper::class),
                    $c->get(CommunityMapper::class),
                    $c->get(ServerMapper::class),
                    $c->get(StatusCodes::class),
                    $c->get(IRootFolder::class),
                    $c->get(IManager::class),
                    $c->get(IURLGenerator::class),
                    $c->get(Curl::class),
                    $c->get(LoggerInterface::class),
                    $c->get("userId")
                );
            }
        );

        $context->registerService(
            ServerController::class,
            function (ContainerInterface $c): ServerController {
                return new ServerController(
                    $c->get('appName'),
                    $c->get(IRequest::class),
                    $c->get(ServerMapper::class),
                    $c->get(IJobList::class),
                    $c->get(LoggerInterface::class),
                    $c->get("userId")
                );
            }
        );

        $context->registerNotifierService(Notifier::class);
    }

    /**
     * Summary of boot
     * 
     * @param \OCP\AppFramework\Bootstrap\IBootContext $context Context
     * 
     * @deprecated 
     * 
     * @return void
     */
    public function boot(IBootContext $context): void
    {
        //$this->registerNavigationEntry();
        $this->loadScripts();
        //$this->registerJobs();
    }

}

