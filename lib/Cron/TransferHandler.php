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
use OCA\B2shareBridge\Model\DepositFileMapper;
use OCA\B2shareBridge\Model\ServerMapper;
use OCA\B2shareBridge\Publish\B2share;
use OCA\B2shareBridge\Publish\IPublish;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OC\Files\Filesystem;
use OCP\DB\Exception;
use Psr\Log\LoggerInterface;


/**
 * Create an owncloud QueuedJob to transfer files in the background
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class TransferHandler extends QueuedJob
{

    private DepositStatusMapper $_mapper;
    private IPublish $_publisher;
    private DepositFileMapper $_dfmapper;
    private ServerMapper $_smapper;
    protected LoggerInterface $logger;

    /**
     * Create the database mapper
     *
     * @param ITimeFactory|null        $time
     * @param DepositStatusMapper|null $mapper    the database mapper for transfers
     * @param DepositFileMapper|null   $dfmapper  ORM for DepositFile
     * @param IPublish|null            $publisher publishing backend to use
     * @param ServerMapper|null        $smapper
     * @param LoggerInterface|null     $logger
     */
    public function __construct(
        ITimeFactory        $time = null,
        DepositStatusMapper $mapper = null,
        DepositFileMapper   $dfmapper = null,
        IPublish            $publisher = null,
        ServerMapper        $smapper = null,
        LoggerInterface     $logger = null
    ) {
        parent::__construct($time);
        if ($dfmapper === null or $mapper === null or $publisher === null or $smapper === null or $logger === null) {
            $this->fixTransferForCron();
        } else {

            $this->_mapper = $mapper;
            $this->_dfmapper = $dfmapper;
            $this->_publisher = $publisher;
            $this->_smapper = $smapper;
            $this->logger = $logger;
        }

    }

    /**
     * A Cron that is executed in the background needs to create the Application
     * because its not coming form the user context
     */
    protected function fixTransferForCron()
    {
        $application = new Application();
        $this->_mapper = $application->getContainer()->get(DepositStatusMapper::class);
        $this->_dfmapper = $application->getContainer()->get(DepositFileMapper::class);
        $this->_publisher = $application->getContainer()->get(B2share::class);
        $this->_smapper = $application->getContainer()->get(ServerMapper::class);
        $this->logger = $application->getContainer()->get(LoggerInterface::class);
    }

    /**
     * Check if current user is the requested user
     *
     * @param array $args array of arguments
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
            $this->logger->error(
                'Can not handle w/o id, token, community, open_access, title',
                ['app' => Application::APP_ID]
            );
            return;
        }
        $fcStatus = null;
        try {
            // get the file transfer object for current Cron
            $fcStatus = $this->_mapper->find($args['transferId']);
            $fcStatus->setStatus(2); //status = processing
            $this->_mapper->update($fcStatus);
            $user = $fcStatus->getOwner();
            $server = $this->_smapper->find($args['serverId']);
            $this->_publisher->setCheckSSL($server->getCheckSsl());

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
                        $upload_url = $file_upload_link . "/" . urlencode($filename);
                        $upload_url = $upload_url .
                            "?access_token=" . $args['token'];
                        $upload_result = $upload_result &&
                            $this->_publisher->upload(
                                $upload_url, $handle, $size
                            );
                    } else {
                        /*
                         * External error: during uploading file
                         */
                        $this->logger->error(
                            "File not accessible" . $file->getFilename(),
                            ['app' => Application::APP_ID]
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
                    $this->logger->error(
                        'No upload_result', ['app' => Application::APP_ID]
                    );
                    $fcStatus->setStatus(3);
                }
            } else {
                /*
                 * External error: during creating deposit
                 */
                $this->logger->error(
                    "No create result, there was an error during deposit creation",
                    ['app' => Application::APP_ID]
                );
                $fcStatus->setErrorMessage($this->_publisher->getErrorMessage());
                $fcStatus->setStatus(4);
            }
            $fcStatus->setUpdatedAt(time());
            $this->_mapper->update($fcStatus);
            $this->logger->info(
                "Job completed, depositStatusId: " . $fcStatus->getId(),
                ['app' => Application::APP_ID]
            );

            /*
             *
             * TODO: think of a fork alternative or make it possible to not loose
             * the database connection. also it is running only one Cron per cron run...
             * TODO: we need to be careful of zombies here!
             */
        } catch (MultipleObjectsReturnedException|DoesNotExistException|Exception $e) {
            $fcStatus?->setStatus(5);
            $fcStatus?->setErrorMessage("Internal Server Error!");
            $this->logger->error(
                $e->getMessage(),
                ['app' => Application::APP_ID]
            );
        }
    }

    /**
     * Check if current user is the requested user
     *
     * @param string $userId userid
     *
     * @return boolean
     */
    public function isPublishingUser(string $userId): bool
    {
        return is_array($this->argument) &&
            array_key_exists('userId', $this->argument) &&
            $this->argument['userId'] === $userId;
    }

    /**
     * Get actual filename for fileId
     *
     * @return string
     */
    public function getFilename(): string
    {
        Filesystem::init($this->argument['userId'], '/');
        return Filesystem::getPath($this->argument['fileId']);
    }

    /**
     * Check if current user is the requested user
     *
     * @return string
     */
    public function getRequestDate(): string
    {
        return $this->argument['requestDate'];
    }
}

