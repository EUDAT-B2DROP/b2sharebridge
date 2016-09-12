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

namespace OCA\B2shareBridge\Db;

use OC\Files\Filesystem;
use OCP\AppFramework\Db\Entity;

/**
 * Creates a database entity for the status code of fileuploads
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class StatusCode extends Entity
{
    protected $statusCode;
    protected $message;

    /**
     * Creates the actual database entity
     */
    public function __construct()
    {
        $this->addType('statusCode', 'integer');
        $this->addType('message', 'string');
    }
    
    /**
     * Get message for statusCode
     *
     * @return \string
     */
    public function getMessage()
    {
        return $this->message;
    }

}
