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

/**
 * Creates a database entity for a community
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class Community extends Entity
{
    protected $name;

    /**
     * Creates the actual database entity
     */
    public function __construct()
    {
        $this->addType('id', 'string');
        $this->addType('name', 'string');
    }

    /**
     * Get string representation
     *
     * @return \string
     */
    public function __toString()
    {
        return 'Community with id: '. $this->getId().
        ' and name: '.$this->getName();
    }
}