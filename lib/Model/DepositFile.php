<?php
/**
 * NextCloud - B2sharebridge App
 *
 * PHP Version 5-7
 *
 * @category  Owncloud
 * @package   B2shareBridge
 * @author    EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright 2017 EUDAT, SURFSara
 * @license   AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link      https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */

namespace OCA\B2shareBridge\Model;

use OCP\AppFramework\Db\Entity;
use OCP\Util;

/**
 * Creates a database entity for the deposit status
 *
 * @category Nextcloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class DepositFile extends Entity
{

    protected $depositStatusId;
    protected $fileid;
    protected $filename;

    /**
     * Creates the actual database entity
     */
    public function __construct()
    {

        $this->addType('depositStatusId', 'integer');
        $this->addType('fileid', 'integer');
        $this->addType('filename', 'string');

    }



    /**
     * Get string representation
     *
     * @return \string
     */
    public function __toString()
    {
        return 'DepositFile with id: '. $this->getId().
        ', fileId: '.$this->getFileid().
        ' fileName: '.$this->getFilename().
        ' and DepositId:'.$this->getDepositStatusId();
    }
    
}