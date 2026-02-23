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

use OCA\B2shareBridge\Util\Helper;
use PHPUnit\Framework\TestCase;

class HelperTest extends TestCase
{
    public function testArrayKeysExistAllPresent()
    {
        $keys = ['foo', 'bar'];
        $array = ['foo' => 'value1', 'bar' => 'value2', 'baz' => 'value3'];
        
        $result = Helper::arrayKeysExist($keys, $array);
        
        $this->assertTrue($result);
    }

    public function testArrayKeysExistMissing()
    {
        $keys = ['foo', 'missing'];
        $array = ['foo' => 'value1', 'bar' => 'value2'];
        
        $result = Helper::arrayKeysExist($keys, $array);
        
        $this->assertFalse($result);
    }

    public function testArrayKeysExistEmptyKeys()
    {
        $keys = [];
        $array = ['foo' => 'value1', 'bar' => 'value2'];
        
        $result = Helper::arrayKeysExist($keys, $array);
        
        $this->assertTrue($result);
    }

    public function testArrayKeysExistEmptyArray()
    {
        $keys = ['foo'];
        $array = [];
        
        $result = Helper::arrayKeysExist($keys, $array);
        
        $this->assertFalse($result);
    }

    public function testArrayKeysExistNullArray()
    {
        $keys = ['foo'];
        $array = null;
        
        $result = Helper::arrayKeysExist($keys, $array);
        
        $this->assertFalse($result);
    }

    public function testArrayKeysExistSingleKeyPresent()
    {
        $keys = ['foo'];
        $array = ['foo' => 'value1'];
        
        $result = Helper::arrayKeysExist($keys, $array);
        
        $this->assertTrue($result);
    }

    public function testArrayKeysExistSingleKeyMissing()
    {
        $keys = ['missing'];
        $array = ['foo' => 'value1'];
        
        $result = Helper::arrayKeysExist($keys, $array);
        
        $this->assertFalse($result);
    }

    public function testArrayKeysExistMultipleKeysAllMissing()
    {
        $keys = ['missing1', 'missing2'];
        $array = ['foo' => 'value1', 'bar' => 'value2'];
        
        $result = Helper::arrayKeysExist($keys, $array);
        
        $this->assertFalse($result);
    }
}
