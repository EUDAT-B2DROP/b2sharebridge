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
use OCA\B2shareBridge\Publish\IPublish;

use OC\BackgroundJob\QueuedJob;
use OC\Files\Filesystem;
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

    /**
     * Create the database mapper
     *
     * @param DepositStatusMapper $mapper    the database mapper for transfers
     * @param IPublish            $publisher publishing backend to use
     */
    public function __construct(
        DepositStatusMapper $mapper = null,
        IPublish $publisher = null
    ) {
        if ($mapper === null || $publisher === null) {
            $this->fixTransferForCron();
        } else {
            $this->_mapper = $mapper;
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
            ->query('DepositStatusMapper');
        $this->_publisher = $application->getContainer()->query('PublishBackend');
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
        ) {
            Util::writeLog(
                'transfer',
                'Can not handle w/o id, token, community, open_access, title',
                3
            );
            return;
        }
        // get the file transfer object for current Cron
        $fcStatus = $this->_mapper->find($args['transferId']);
        Util::writeLog('b2sharebridge', 'Title: '.$args['title'], 3);
        $fcStatus->setStatus(2);//status = processing
        $this->_mapper->update($fcStatus);
        $user = $fcStatus->getOwner();
        $fileId = $fcStatus->getFileid();

        Filesystem::init($user, '/');
        $filename = Filesystem::getPath($fileId);
        $has_access = Filesystem::isReadable($filename);
        if ($has_access) {
            $view = Filesystem::getView();
            // TODO: is it good to take the owncloud fopen?

            $create_result = $this->_publisher->create(
                $args['token'],
                urlencode(basename($filename)),
                $args['community'],
                $args['open_access'],
                $args['title']
            );
            if ($create_result) {
                $handle = $view->fopen($filename, 'rb');
                $size = $view->filesize($filename);
                $upload_result = $this->_publisher->upload($handle, $size);

                if ($upload_result) {
                    $fcStatus->setStatus(0);//status = published
                    $fcStatus->setUrl($create_result);
                } else {
                    /**External error: during uploading file*/
                    $fcStatus->setStatus(3);
                }
            } else {
                /**External error: during creating deposit*/
                $fcStatus->setStatus(4);
            }
        } else {
            /**Internal error: file not accessible*/
            $fcStatus->setStatus(5);
        }
        $fcStatus->setUpdatedAt(time());
        $this->_mapper->update($fcStatus);


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

