<?php
/**
 * ownCloud - b2sharebridge
 *
 * This file is licensed under the MIT License. See the LICENSE file.
 *
 * @author    Dennis Blommesteijn <dennis@blommesteijn.com>
 * @copyright Dennis Blommesteijn 2015
 */

use OCP\AppFramework\App;
use PHPUnit\Framework\TestCase;


/**
 * This test shows how to make a small Integration Test. Query your class
 * directly from the container, only pass in mocks if needed and run your tests
 * against the database
 */
class AppTest extends TestCase
{

    private $container;

    public function setUp(): void
    {
        parent::setUp();
        $app = new App('b2sharebridge');
        $this->container = $app->getContainer();
    }

    public function testAppInstalled() 
    {
        $appManager = $this->container->query('OCP\App\IAppManager');
        $this->assertTrue($appManager->isInstalled('b2sharebridge'));
    }

}
