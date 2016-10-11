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

use OC\Files\Filesystem;
use OCP\AppFramework\Db\Entity;

/**
 * Creates a database entity for the deposit status
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class DepositStatus extends Entity
{
    protected $fileid;
    protected $status;
    protected $owner;
    protected $createdAt;
    protected $updatedAt;
    protected $url;

    /**
     * Creates the actual database entity
     */
    public function __construct()
    {
        $this->addType('fileid', 'integer');
        $this->addType('status', 'integer');
        $this->addType('owner', 'string');
        $this->addType('createdAt', 'integer');
        $this->addType('updatedAt', 'integer');
        $this->addType('url', 'string');
    }

    /**
     * Get filename for fileid
     *
     * @return \integer
     */
    public function getFilename()
    {
        return Filesystem::getPath($this->fileid);
    }

    /**
     * Get string representation
     *
     * @return \string
     */
    public function __toString()
    {
        return 'Deposit with id: '. $this->getId().
        ' and status:'.$this->getStatus().
        ' belonging to user:'.$this->getOwner();
    }
}