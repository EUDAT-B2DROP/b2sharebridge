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

namespace OCA\B2shareBridge\Publish;

use OCA\B2shareBridge\Publish\B2ShareV2;
use OCA\B2shareBridge\Publish\B2ShareV3;

/**
 * get A b2share API by version
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class B2ShareFactory
{
    protected B2ShareV2 $_v2;
    protected B2ShareV3 $_v3;

    /**
     * Create object for B2Share API creation
     *
     * @param B2ShareV2 v2
     * @param B2ShareV3 v3
     */
    public function __construct(B2ShareV2 $v2, B2ShareV3 $v3)
    {
        $this->_v2 = $v2;
        $this->_v3 = $v3;
    }

    /**
     * Get B2Share API instance 
     * @param int $version
     * @return B2ShareV2|B2ShareV3|null
     */
    public function get(int $version): B2ShareAPI|null
    {
        return match ($version) {
            2 => $this->_v2,
            3 => $this->_v3,
            default => null,
        };
    }
}