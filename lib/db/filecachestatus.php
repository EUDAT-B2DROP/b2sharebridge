<?php

namespace OCA\Eudat\Db;

use OC\Files\Filesystem;
use OCP\AppFramework\Db\Entity;

class FilecacheStatus extends Entity {

    protected $fileid;
    protected $status;
    protected $createdAt;
    protected $updatedAt;

    public function __construct() {
        $this->addType('fileid', 'integer');
        $this->addType('status', 'string');
        $this->addType('createdAt', 'integer');
        $this->addType('updatedAt', 'integer');
    }

    /**
     * Get filename for fileid
     * @return \integer
     */
    public function getFilename(){
        return Filesystem::getPath($this->fileid);
    }

}