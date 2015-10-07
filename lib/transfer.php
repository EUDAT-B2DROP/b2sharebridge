<?php

namespace OCA\Eudat;

use OCA\Eudat\Db\FilecacheStatusMapper;
use OCA\Eudat\Db\FilecacheStatus;


class Transfer extends \OC\BackgroundJob\QueuedJob {

    public function __construct(FilecacheStatusMapper $mapper){
        $this->mapper = $mapper;
    }

    public function run($args){
        // init assertions
        if(!function_exists('pcntl_fork')){
            die("no function `pcntl_fork` install `pcntl` extension" . PHP_EOL);
        }
        if(!function_exists('posix_getpid')){
            die("no function `posix_getpid`, this feature works on a posix OS only" . PHP_EOL);
        }
        // print_r($args);
        if(!array_key_exists('fileId', $args) || !array_key_exists('userId', $args)){
            echo "bad request missing `fileId` or `userId`" . PHP_EOL;
            return;
        }
        // fork process (don't keep cron.php locked in sequence)
        $pid = \pcntl_fork();
        if ($pid == -1) {
            die("forking error" . PHP_EOL);
        } else if($pid) {
            // parent
            return;
        } else {
            // child
            $this->forked(posix_getpid(), $args);
            die();
        }
        // TODO: we need to be carefull of zombies here!
    }

    /**
     * Check if current user is the requested user
     */
    public function isPublishingUser($userId){
        return is_array($this->argument) &&
            array_key_exists('userId', $this->argument) &&
            $this->argument['userId'] == $userId;
    }

    public function getFilename(){
        \OC\Files\Filesystem::init($this->argument['userId'], '/');
        return \OC\Files\Filesystem::getPath($this->argument['fileId']);
    }

    public function getRequestDate(){
        return $this->argument['requestDate'];
    }

    /**
     * Run by child process (async)
     */
    public function forked($pid, $args){
        // get path of file
        // TODO: make sure the user can access the file
        $fcStatus = $this->mapper->find($args['fileId']);
        $fcStatus->setStatus("processing");
        $this->mapper->update($fcStatus);
        \OC\Files\Filesystem::init($args['userId'], '/');
        $path = \OC\Files\Filesystem::getPath($args['fileId']);
        // detect failed lookups
        if (strlen($path) <= 0){
            echo "cannot find path for: `" . $args['userId'] . ":" . $args['fileId'] . "`" . PHP_EOL;
            return;
        }


        echo PHP_EOL. " " . $pid . " {{ " . $path . " }} start...";
        sleep(5);
        echo PHP_EOL. " " . $pid . " {{ " . $path . " }} ... end";

        // \OC\Files\Filesystem::getFileInfo($args['fileId']);

        // $view = new \OC\Files\View('/' . $args['userId'] . '/files');
        // $fileinfo = $view->getFileInfo("blaat.txt");
        // echo "{{ " . $fileinfo->getInternalPath() . " }}" .PHP_EOL;
        // echo "{{ " . $fileinfo->getId() . " }}" .PHP_EOL;
        // echo "{{ " . $fileinfo->getMountPoint() . " }}" .PHP_EOL;
        // echo "{{ " . $fileinfo->getSize() . " }}" .PHP_EOL;
        // echo "{{ " . $fileinfo->getType() . " }}" .PHP_EOL;
        // echo "{{ " . $fileinfo->stat() . " }}" .PHP_EOL;

        // echo "... forked end \t" . $pid . PHP_EOL;


        // TODO: load user from $user_name
        // TODO: load file from $file_id

        // TODO: check permissions
        // TODO: load external config

        // TODO: start external session
        // TODO: start transfer

        // TODO: update status table
    }

}

