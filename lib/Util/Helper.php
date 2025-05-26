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

namespace OCA\B2shareBridge\Util;

/**
 * Util class for widely used functions
 */
class Helper
{
    /**
     * Summary of arrayKeysExist
     *
     * @param  mixed $keys  array of keys to check for existance
     * @param  mixed $array array to check if it contains the keys
     * @return bool
     */
    public static function arrayKeysExist($keys, $array)
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $array)) {
                return false;
            }
        }
        return true;
    }
}
