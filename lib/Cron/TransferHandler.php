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

namespace OCA\B2shareBridge\Cron;

use OCA\B2shareBridge\AppInfo\Application;
use OCA\B2shareBridge\Model\DepositStatusMapper;
use OCA\B2shareBridge\Model\ServerMapper;
use OCA\B2shareBridge\Publish\IPublish;

use OC\BackgroundJob\QueuedJob;
use OC\Files\Filesystem;
use OCP\ILogger;
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
    private $_publisher;
    private $_dfmapper;
    private $_smapper;

    /**
     * Create the database mapper
     *
     * @param DepositStatusMapper $mapper    the database mapper for transfers
     * @param DepositFileMapper   $dfmapper  ORM for DepositFile
     * @param IPublish            $publisher publishing backend to use
     */
    public function __construct(
        DepositStatusMapper $mapper = null,
        DepositFileMapper $dfmapper = null,
        IPublish $publisher = null,
        ServerMapper $smapper = null
    ) {
        if ($mapper === null || $publisher === null || $dfmapper === null || $smapper === null) {
            $this->fixTransferForCron();
        } else {
            $this->_mapper = $mapper;
            $this->_dfmapper = $dfmapper;
            $this->_publisher = $publisher;
            $this->_smapper = $smapper;
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
            ->query('DepositStatusMapper');
        $this->_dfmapper = $application->getContainer()
            ->query('DepositFileMapper');
        $this->_publisher = $application->getContainer()->query('PublishBackend');
        $this->_smapper = $application->getContainer()->query('ServerMapper');
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
            || !array_key_exists('community', $args)
            || !array_key_exists('open_access', $args)
            || !array_key_exists('title', $args)
            || !array_key_exists('serverId', $args)
        ) {
            \OC::$server->getLogger()->error(
                'Can not handle w/o id, token, community, open_access, title',
                ['app' => 'b2sharebridge']
            );
            return;
        }
        // get the file transfer object for current Cron
        $fcStatus = $this->_mapper->find($args['transferId']);
        $fcStatus->setStatus(2); //status = processing
        $this->_mapper->update($fcStatus);
        $user = $fcStatus->getOwner();
        $server = $this->_smapper->find($args['serverId']);

        $create_result = $this->_publisher->create(
            $args['token'],
            $args['community'],
            $args['open_access'],
            $args['title'],
            $server->getPublishUrl()
        );

        if ($create_result) {
            $file_upload_link = $this->_publisher->getFileUploadUrlPart();
            Filesystem::init($user, '/');
            $view = Filesystem::getView();
            $files = $this->_dfmapper->findAllForDeposit($fcStatus->getId());
            $upload_result = true;

            foreach ($files as $file) {
                $filename = $file->getFilename();
                $fileid = $file->getFileid();
                $path = Filesystem::getPath($fileid);
                $has_access = Filesystem::isReadable($path);
                if ($has_access) {
                    $handle = $view->fopen($path, 'rb');
                    $size = $view->filesize($path);
                    $upload_url = $file_upload_link."/".urlencode($filename);
                    $upload_url = $upload_url.
                        "?access_token=".$args['token'];
                    $upload_result = $upload_result &&
                        $this->_publisher->upload(
                            $upload_url, $handle, $size
                        );
                } else {
                    /*
                     * External error: during uploading file
                     */
                    \OC::$server->getLogger()->error(
                        "File not accesable".$file->getFilename(),
                        ['app' => 'b2sharebridge']
                    );
                    $fcStatus->setStatus(3);
                }
            }
            if ($upload_result) {
                $fcStatus->setStatus(0);//status = published
                $fcStatus->setUrl($create_result);
            } else {
                /*
                 * External error: during uploading file
                 */
                \OC::$server->getLogger()->error(
                    'No upload_result', ['app' => 'b2sharebridge']
                );
                $fcStatus->setStatus(3);
            }
        } else {
            /*
             * External error: during creating deposit
             */
            \OC::$server->getLogger()->error(
                "No create result, there was an error during deposit creation",
                ['app' => 'b2sharebridge']
            );
            $fcStatus->setErrorMessage($this->_publisher->getErrorMessage());
            $fcStatus->setStatus(4);
        }
        $fcStatus->setUpdatedAt(time());
        $this->_mapper->update($fcStatus);
        \OC::$server->getLogger()->info(
            "Job completed, depositStatusId: ".$fcStatus->getId(),
            ['app' => 'b2sharebridge']
        );

        /*
         *
         * TODO: think of a fork alternative or make it possible to not loose
         * the database connection. also it is running only one Cron per cron run...
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
}

