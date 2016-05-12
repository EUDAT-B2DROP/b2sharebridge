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

namespace OCA\B2shareBridge\Job;

use OCA\B2shareBridge\AppInfo\Application;
use OCA\B2shareBridge\Db\FilecacheStatusMapper;
use OCA\B2shareBridge\Publish\IPublish;

use OC\BackgroundJob\QueuedJob;
use OC\Files\Filesystem;
use OCP\IConfig;
use OCP\Util;


/**
 * Create a owncloud QueuedJob to transfer files int he background
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class TransferHandler extends QueuedJob
{

    private $_mapper;
    private $_config;
    private $_publisher;

    /**
     * Create the database mapper
     *
     * @param FilecacheStatusMapper $mapper    the database mapper for transfers
     * @param IConfig               $config    the owncloud config
     * @param IPublish              $publisher publishing backend to use
     */
    public function __construct(
        FilecacheStatusMapper $mapper = null,
        IConfig $config = null,
        IPublish $publisher = null
    ) {
        if ($mapper === null || $config === null || $publisher === null) {
            $this->fixTransferForCron();
        } else {
            $this->_mapper = $mapper;
            $this->_config = $config;
            $this->_publisher = $publisher;
        }
    }

    /**
     * A Cron that is executed in the background needs to create the Application
     * because its not coming form the user context
     *
     * @return null
     */
    protected function fixTransferForCron() 
    {
        $application = new Application();
        $this->_mapper = $application->getContainer()
            ->query('FilecacheStatusMapper');
        $this->_publisher = $application->getContainer()->query('PublishBackend');
        $this->_config = \OC::$server->getConfig();
    }

    /**
     * Check if current user is the requested user
     *
     * @param array(string) $args array of arguments
     *
     * @return null
     */
    public function run($args)
    {
        if (!array_key_exists('transferId', $args)
            || !array_key_exists('token', $args)
        ) {
            Util::writeLog(
                'transfer',
                'Bad request, can not handle transfer without transferId / token',
                3
            );
            return;
        }
        // get the file transfer object for current job
        $fcStatus = $this->_mapper->find($args['transferId']);

        $fcStatus->setStatus("processing");
        $this->_mapper->update($fcStatus);
        $user = $fcStatus->getOwner();
        $fileId = $fcStatus->getFileid();

        Filesystem::init($user, '/');
        $filename = Filesystem::getPath($fileId);
        $has_access = Filesystem::isReadable($filename);
        if ($has_access) {
            $view = Filesystem::getView();
            // TODO: is it good to take the owncloud fopen?

            $this->_publisher->create($args['token'], $filename);

            $handle = $view->fopen($filename, 'rb');
            $size = $view->filesize($filename);
            $this->_publisher->upload($handle, $size);

            $result = $this->_publisher->finalize();

            if ($result['status'] === 'success') {
                Util::writeLog(
                    'transfer_path',
                    'Communication successfull: '.$result['output'].$result['url'],
                    0
                );
                $fcStatus->setStatus('published');
                $fcStatus->setUrl($result['url']);
            } else {
                Util::writeLog(
                    'transfer_path',
                    'Error communicating with B2SHARE'.$result['output'],
                    3
                );
                $fcStatus->setStatus('external error');
            }
        } else {
            Util::writeLog(
                'transfer_path',
                'Internal error: file not accessible',
                3
            );
            $fcStatus->setStatus('internal error');
        }
        $fcStatus->setUpdatedAt(time());
        $this->_mapper->update($fcStatus);


        /*
         *
         * TODO: think of a fork alternative or make it possible to not loose
         * the database connection. also it is running only one job per cron run...
         * TODO: we need to be carefull of zombies here!
         */
    }

    /**
     * Check if current user is the requested user
     *
     * @param string $userId userid
     *
     * @return boolean
     */
    public function isPublishingUser($userId)
    {
        return is_array($this->argument) &&
            array_key_exists('userId', $this->argument) &&
            $this->argument['userId'] === $userId;
    }

    /**
     * Get actual filename for fileId
     *
     * @return \string
     */
    public function getFilename()
    {
        Filesystem::init($this->argument['userId'], '/');
        return Filesystem::getPath($this->argument['fileId']);
    }

    /**
     * Check if current user is the requested user
     *
     * @return \boolean
     */
    public function getRequestDate()
    {
        return $this->argument['requestDate'];
    }

    /**
     * Fork process that uploads the file to b2share
     *
     * @param string $pid  process id
     * @param array  $args arguments
     *
     * @return null
     */
    public function forked($pid, $args)
    {
        Util::writeLog('transfer', 'FORKED', 3);
        foreach ($args as &$value) {
            Util::writeLog('transfer_array', $value, 3);
        }
        // get path of file
        // TODO: make sure the user can access the file
        $fcStatus = $this->_mapper->find($args['transferId']);

        $fcStatus->setStatus("processing");
        $this->_mapper->update($fcStatus);
        Util::writeLog('transfer', 'FORKED2', 3);

        Filesystem::init($args['userId'], '/');
        $path = Filesystem::getPath($args['fileId']);
        Util::writeLog('transfer', 'FORKED3', 3);
        // detect failed lookups
        if (strlen($path) <= 0) {
            Util::writeLog(
                'transfer',
                "cannot find path: `".$args['userId'].":".$args['fileId']."`",
                3
            );
            return;
        }


        Util::writeLog('transfer', 'start...', 3);
        sleep(5);
        Util::writeLog('transfer', '...end', 3);

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

        // TODO: start external session
        // TODO: start transfer

    }

}

