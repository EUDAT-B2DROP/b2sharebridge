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

namespace OCA\B2shareBridge\Tests\Publish;

use OCA\B2shareBridge\Publish\B2ShareFactory;
use OCA\B2shareBridge\Publish\B2ShareV2;
use OCA\B2shareBridge\Publish\B2ShareV3;
use OCA\B2shareBridge\Util\Curl;
use OCP\IConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class B2ShareFactoryTest extends TestCase
{
    private B2ShareFactory $factory;
    private B2ShareV2 $v2;
    private B2ShareV3 $v3;
    private LoggerInterface $logger;
    private IConfig $config;
    private Curl $curl;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->config = $this->createMock(IConfig::class);
        $this->curl = new Curl($this->logger);
        
        $this->v2 = new B2ShareV2('b2sharebridge', $this->config, $this->logger, $this->curl);
        $this->v3 = new B2ShareV3('b2sharebridge', $this->config, $this->logger, $this->curl);
        
        $this->factory = new B2ShareFactory($this->v2, $this->v3);
    }

    public function testGetVersion2()
    {
        $result = $this->factory->get(2);
        
        $this->assertInstanceOf(B2ShareV2::class, $result);
    }

    public function testGetVersion3()
    {
        $result = $this->factory->get(3);
        
        $this->assertInstanceOf(B2ShareV3::class, $result);
    }

    public function testGetInvalidVersion()
    {
        $result = $this->factory->get(1);
        
        $this->assertNull($result);
    }

    public function testGetZeroVersion()
    {
        $result = $this->factory->get(0);
        
        $this->assertNull($result);
    }

    public function testGetNegativeVersion()
    {
        $result = $this->factory->get(-1);
        
        $this->assertNull($result);
    }

    public function testGetLargeVersion()
    {
        $result = $this->factory->get(99);
        
        $this->assertNull($result);
    }
}
