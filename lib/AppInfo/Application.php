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
use OCA\B2shareBridge\Settings\Personal;
use OCP\AppFramework\App;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IContainer;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\Util;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
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
     * @param array $urlParams a list of url parameters
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(array $urlParams = array())
    {
        parent::__construct(self::APP_ID, $urlParams);
        $container = $this->getContainer();

        // Register files tab view
        $dispatcher = $container->get(IEventDispatcher::class);

        $dispatcher->addListener(
            'OCA\Files::loadAdditionalScripts', function () {
            Util::addScript(self::APP_ID, 'b2sharebridge-filetabmain');
        });
    }

    /**
     * Load additional javascript files
     */
    public static function loadScripts()
    {
        Util::addScript(self::APP_ID, "b2sharebridge-settingsadmin");
        Util::addScript(self::APP_ID, "b2sharebridge-settingspersonal");
    }

    public function register(IRegistrationContext $context): void
    {
        // Register the composer autoloader for packages shipped by this app, if applicable
        //include_once __DIR__ . '/../../vendor/autoload.php';
        /**
         * Mappers
         */
        //Note: Why do they all show as deprecated?
        $context->registerService(
            CommunityMapper::class, function (ContainerInterface $c): CommunityMapper {
            return new CommunityMapper(
                $c->get(IDBConnection::class)
            );
        }
        );

        $context->registerService(
            DepositStatusMapper::class, function (ContainerInterface $c): DepositStatusMapper {
            return new DepositStatusMapper(
                $c->get(IDBConnection::class),
                $c->get(LoggerInterface::class)
            );
        }
        );

        $context->registerService(
            DepositFileMapper::class, function (ContainerInterface $c): DepositFileMapper {
            return new DepositFileMapper(
                $c->get(IDBConnection::class)
            );
        }
        );

        $context->registerService(
            ServerMapper::class, function (ContainerInterface $c): ServerMapper {
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
            StatusCodes::class, function (): StatusCodes {
            return new StatusCodes();
        }
        );

        $context->registerService(
            B2share::class, function (ContainerInterface $c): B2share {
            return new B2share(
                $c->get(IConfig::class),
                $c->get(LoggerInterface::class)
            );
        }
        );

        //Note: this is done for backwards compatability
        //$context->registerAlias(B2share::class, "PublishBackend");

        $context->registerService(Personal::class, function (ContainerInterface $c): Personal {
            return new Personal(
                $c->get(IConfig::class),
                $c->get(ServerMapper::class),
                $c->get("UserId")
            );
        });

        /**
         * Controller
         */
        $context->registerService(
            PublishController::class, function (ContainerInterface $c): PublishController {
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
                $c->get(LoggerInterface::class),
                $c->get(IJobList::class),
                $c->get("UserId")
            );
        }
        );

        $context->registerService(
            ViewController::class, function (ContainerInterface $c): ViewController {
            return new ViewController(
                $c->get('appName'),
                $c->get(IRequest::class),
                $c->get(IConfig::class),
                $c->get(DepositStatusMapper::class),
                $c->get(DepositFileMapper::class),
                $c->get(CommunityMapper::class),
                $c->get(ServerMapper::class),
                $c->get(StatusCodes::class),
                $c->get(LoggerInterface::class),
                $c->get("UserId")
            );
        }
        );

        $context->registerService(
            ServerController::class, function (ContainerInterface $c): ServerController {
            return new ServerController(
                $c->get('appName'),
                $c->get(IRequest::class),
                $c->get(ServerMapper::class),
                $c->get("UserId")
            );
        }
        );
    }

    public function boot(IBootContext $context): void
    {
        //$this->registerNavigationEntry();
        $this->loadScripts();
        //$this->registerJobs();
    }

}

