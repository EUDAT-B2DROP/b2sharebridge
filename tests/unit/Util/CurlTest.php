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

namespace OCA\B2shareBridge\Tests\Util;

use OCA\B2shareBridge\Util\Curl;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CurlTest extends TestCase
{
    private Curl $curl;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->curl = new Curl($this->logger);
    }

    public function testConstructorDefaultSsl()
    {
        $curl = new Curl($this->logger);
        $this->assertTrue($curl->getSsl());
    }

    public function testConstructorWithSslEnabled()
    {
        $curl = new Curl($this->logger, true);
        $this->assertTrue($curl->getSsl());
    }

    public function testConstructorWithSslDisabled()
    {
        $curl = new Curl($this->logger, false);
        $this->assertFalse($curl->getSsl());
    }

    public function testSetSSL()
    {
        $this->curl->setSSL(false);
        $this->assertFalse($this->curl->getSsl());
        
        $this->curl->setSSL(true);
        $this->assertTrue($this->curl->getSsl());
    }

    public function testGetError()
    {
        $ch = curl_init('https://invalid-domain-123456789.com');
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        
        curl_exec($ch);
        $error = $this->curl->getError($ch);
        
        curl_close($ch);
        $this->assertIsString($error);
    }


}
