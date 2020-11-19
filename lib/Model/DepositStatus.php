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

use OCP\AppFramework\Db\Entity;
use OCP\Util;

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

    protected $status;
    protected $title;
    protected $owner;
    protected $createdAt;
    protected $updatedAt;
    protected $url;
    protected $fileMapper;
    protected $fileCount;
    protected $errorMessage;
    protected $serverId;

    /**
     * Creates the actual database entity
     */
    public function __construct()
    {
        $this->addType('status', 'integer');
        $this->addType('title', 'string');
        $this->addType('owner', 'string');
        $this->addType('createdAt', 'integer');
        $this->addType('updatedAt', 'integer');
        $this->addType('url', 'string');
        $this->addType('fileCount', 'integer');
        $this->addType('errorMessage', 'string');
        $this->addType('serverId', 'integer');
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
        ' belonging to user:'.$this->getOwner().' deposited under title'.
        $this->getTitle();
    }


    /**
     * Return URL only if status = PUBLISHED
     *
     * @return \string
     */
    public function getHyperlink()
    {
        $result = "N/A";

        if ($this->getStatus()===0) {
            $result = '<a href="'.$this->getUrl()
                .'" target="_blank">B2SHARE deposit</a>';
        }

        return urldecode($result);
    }
}
