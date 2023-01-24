<?php
/**
 * B2SHAREBRIDGE
 *
 * PHP Version 7
 *
 * @category  Nextcloud
 * @package   B2shareBridge
 * @author    EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright 2015 EUDAT
 * @license   AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link      https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
namespace OCA\B2shareBridge\Tests\AppInfo;

use OCA\B2shareBridge\AppInfo\Application;
use OCA\B2shareBridge\Controller\PublishController;
use OCA\B2shareBridge\Controller\ServerController;
use OCA\B2shareBridge\Controller\ViewController;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class ApplicationTest extends TestCase
{
    /**
     * @var Application 
     */
    protected $app;
    /**
     * @var \OCP\AppFramework\IAppContainer 
     */
    protected $container;

    protected function setUp(): void 
    {
        parent::setUp();
        $this->app = new Application();
        $this->container = $this->app->getContainer();
        $this->container->registerParameter("UserId", "testID");
    }

    public function testContainerAppName() 
    {
        $this->app = new Application();
        $this->assertEquals('b2sharebridge', $this->app::APP_ID);
    }

    public function testControllerAvailable()
    {
        try {
            $this->container->get(PublishController::class);
            $this->container->get(ViewController::class);
            $this->container->get(ServerController::class);
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $error) {
            $this->fail("Container not found!". $error->getMessage());
        }
    }
}
