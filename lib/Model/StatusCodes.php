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

namespace OCA\B2shareBridge\Model;


/**
 * Wrapper for array containing mapping between integers and corresponding
 * status code messages
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class StatusCodes
{

    protected $statusCodes;
    /**
     * Creates the actual database entity
     */
    public function __construct()
    {
        $this->statusCodes = [
            0 => 'published',
            1 => 'new',
            2 => 'processing',
            3 => 'External error: during uploading file',
            4 => 'External error: during creating deposit',
            5 => 'Internal error: file not accessible'
        ];
    }


    /**
     * Get string for a given status code
     *
     * @param \integer $code status code to look up string for
     *
     * @return \string
     */
    public function getForNumber($code)
    {
        return $this->statusCodes[$code];
    }

    /**
     * Get integer for a given status code string
     *
     * @param \string $code get integer for a status code string
     *
     * @return \integer
     */
    public function getForString($code)
    {
        return array_search($code, $this->statusCodes);
    }
}