<?php
/**
 * ownCloud - eudat
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE file.
 *
 * @author EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright EUDAT 2015
 */
namespace OCA\Eudat\Db;

use OC\Files\Filesystem;
use OCP\AppFramework\Db\Entity;

class FilecacheStatus extends Entity {

    protected $fileid;
    protected $status;
    protected $owner;
    protected $createdAt;
    protected $updatedAt;
    protected $url;

    public function __construct() {
        $this->addType('fileid', 'integer');
        $this->addType('status', 'string');
        $this->addType('owner', 'string');
        $this->addType('createdAt', 'integer');
        $this->addType('updatedAt', 'integer');
        $this->addType('url', 'string');
    }

    /**
     * Get filename for fileid
     * @return \integer
     */
    public function getFilename(){
        return Filesystem::getPath($this->fileid);
    }

}