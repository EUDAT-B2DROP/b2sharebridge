<?php

namespace OCA\Eudat\Db;

use OCP\AppFramework\Db\Entity;

class FilecacheStatus extends Entity {

    protected $fileid;
    protected $jobid;
    protected $status;
    protected $createdAt;
    protected $updatedAt;

    public function __construct() {
        // add types in constructor
        $this->addType('fileid', 'integer');
        $this->addType('jobid', 'integer');
        $this->addType('status', 'string');
        $this->addType('createdAt', 'datetime');
        $this->addType('updatedAt', 'datetime');
        // $this->updatedAt = time();
    }

    public function getFilename(){
    	return \OC\Files\Filesystem::getPath($this->fileid);
    }

}