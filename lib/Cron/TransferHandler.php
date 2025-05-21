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
use OCA\B2shareBridge\Model\CommunityMapper;
use OCA\B2shareBridge\Model\DepositStatusMapper;
use OCA\B2shareBridge\Model\DepositFileMapper;
use OCA\B2shareBridge\Model\ServerMapper;
use OCA\B2shareBridge\Publish\B2share;
use OCA\B2shareBridge\Publish\IPublish;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Notification\IManager;
use OCP\Files\IRootFolder;
use OCP\DB\Exception;
use OCP\Notification\INotification;
use Psr\Log\LoggerInterface;

use OC\Files\Filesystem;


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
    private CommunityMapper $_cmapper;
    protected IManager $notManager;
    protected LoggerInterface $logger;
    private IRootFolder $_rootFolder;

    /**
     * Create the database mapper
     *
     * @param ITimeFactory|null        $time       Time
     * @param DepositStatusMapper|null $mapper     the database mapper for transfers
     * @param DepositFileMapper|null   $dfmapper   ORM for DepositFile
     * @param IPublish|null            $publisher  publishing backend to use
     * @param ServerMapper|null        $smapper    Server Mapper
     * @param CommunityMapper|null     $cmapper    Community Mapper
     * @param IManager|null            $notManager Manager
     * @param LoggerInterface|null     $logger     LoggerInterface
     * @param IRootFolder|null         $rootFolder RootFolder
     */
    public function __construct(
        ITimeFactory $time = null,
        DepositStatusMapper $mapper = null,
        DepositFileMapper $dfmapper = null,
        IPublish $publisher = null,
        ServerMapper $smapper = null,
        Communitymapper $cmapper = null,
        IManager $notManager = null,
        LoggerInterface $logger = null,
        IRootFolder $rootFolder = null
    ) {
        parent::__construct($time);
        if ($dfmapper === null or $mapper === null or $publisher === null or $smapper === null or $cmapper === null
            or $logger === null or $notManager === null or $rootFolder === null
        ) {
            $this->fixTransferForCron();
        } else {
            $this->_mapper = $mapper;
            $this->_dfmapper = $dfmapper;
            $this->_publisher = $publisher;
            $this->_smapper = $smapper;
            $this->_cmapper = $cmapper;
            $this->logger = $logger;
            $this->notManager = $notManager;
            $this->_rootFolder = $rootFolder;
        }
    }

    /**
     * A Cron that is executed in the background needs to create the Application
     * because its not coming form the user context
     * 
     * @return void
     */
    protected function fixTransferForCron()
    {
        $application = new Application();
        $this->_mapper = $application->getContainer()->get(DepositStatusMapper::class);
        $this->_dfmapper = $application->getContainer()->get(DepositFileMapper::class);
        $this->_publisher = $application->getContainer()->get(B2share::class);
        $this->_smapper = $application->getContainer()->get(ServerMapper::class);
        $this->_cmapper = $application->getContainer()->get(CommunityMapper::class);
        $this->notManager = $application->getContainer()->get(IManager::class);
        $this->logger = $application->getContainer()->get(LoggerInterface::class);
        $this->_rootFolder = $application->getContainer()->get(IRootFolder::class);
    }

    /**
     * Check if current user is the requested user
     *
     * @param array $args array of arguments
     * 
     * @return void
     */
    public function run($args)
    {
        $notification = $this->_uploadFiles($args);
        $this->notManager->notify($notification);
    }

    /**
     * Uploads files configured by args to B2SHARE using curl
     * 
     * @param mixed $args array of arguments, need to have the right list of fields
     * 
     * @throws \BadMethodCallException
     * 
     * @return INotification
     */
    private function _uploadFiles($args): INotification
    {
        if (!$this->_publisher instanceof B2SHARE) {
            $this->logger->error("Not implemented!");
            throw new \BadMethodCallException("Not implemented!");
        }

        if (!array_key_exists('transferId', $args)
            || !array_key_exists('token', $args)
            || !array_key_exists('community', $args)
            || !array_key_exists('open_access', $args)
            || !array_key_exists('title', $args)
            || !array_key_exists('server_id', $args)
        ) {
            $message = 'Can not handle w/o id, token, community, open_access, title';
            $this->logger->error(
                $message,
                ['app' => Application::APP_ID]
            );
            throw new \BadMethodCallException($message);
        }

        $fcStatus = null;
        $notification = $this->notManager->createNotification();
        $notification->setApp(Application::APP_ID)
            ->setDateTime(new \DateTime())
            ->setObject('b2sharebridge', $args['transferId']);

        try {
            // get the file transfer object for current Cron
            $fcStatus = $this->_mapper->find($args['transferId']);
            $fcStatus->setStatus(2); //status = processing
            $this->_mapper->update($fcStatus);
            $user = $fcStatus->getOwner();

            $notification->setUser($user);
            $server = $this->_smapper->find($args['server_id']);
            $this->_publisher->setCheckSSL($server->getCheckSsl());

            $create_result = $this->_publisher->create(
                $args['token'],
                $args['community'],
                $args['open_access'],
                $args['title'],
                $server,
            );

            if (!$create_result) {
                /*
                 * External error: during creating deposit
                 */
                $fcStatus->setStatus(4);

                if (str_starts_with($this->_publisher->getErrorMessage(), '403')) {
                    $community = $this->_cmapper->find($args['community'], $server->getId());
                    $unauthorized_message = 'You are not allowed to upload to "' . $community->getName() . '"';
                    $this->logger->debug(
                        $unauthorized_message,
                        ['app' => Application::APP_ID]
                    );
                    $fcStatus->setErrorMessage($unauthorized_message);
                    $notification->setSubject('unauthorized', ['publisher_url' => $server->getPublishUrl(), 'community' => $community->getName()]);
                } else {
                    $this->logger->error(
                        "No create result, there was an error during deposit creation",
                        ['app' => Application::APP_ID]
                    );
                    $fcStatus->setErrorMessage($this->_publisher->getErrorMessage());
                    $notification->setSubject('external_error', ['publisher_url' => $server->getPublishUrl()]);
                }
                $this->_mapper->update($fcStatus);
                return $notification;
            }

            $file_upload_link = $this->_publisher->getFileUploadUrlPart();
            $rootFolder = $this->_rootFolder->getUserFolder($user);
            $files = $this->_dfmapper->findAllForDeposit($fcStatus->getId());
            $upload_result = true;

            $this->logger->warning("#upload files" . count($files), ['app' => Application::APP_ID]);

            foreach ($files as $file) {
                $fileid = $file->getFileid();

                $fileNodes = $rootFolder->getById($fileid);
                if (count($fileNodes) > 1) {
                    $this->logger->warning("Ambiguous upload, multiple files with same ID", ['app' => Application::APP_ID]);
                }
                foreach ($fileNodes as $fileNode) {
                    if ($fileNode->getType() != \OCP\Files\FileInfo::TYPE_FILE) {
                        $this->logger->warning("User somehow managed to upload a folder" . get_class($fileNode), ['app' => Application::APP_ID]);
                        continue;
                    }

                    $filename = $fileNode->getName();

                    if (!$fileNode->isReadable()) {
                        /*
                         * External error: during uploading file
                         */
                        $this->logger->error(
                            "File not accessible $filename",
                            ['app' => Application::APP_ID]
                        );
                        $fcStatus->setStatus(3);
                        $notification->setSubject('not_accessible');
                        $this->_mapper->update($fcStatus);
                        return $notification;
                    }

                    $handle = $fileNode->fopen('rb');
                    $size = $fileNode->getSize();
                    $upload_url = $file_upload_link . "/" . urlencode($filename);
                    $upload_url = $upload_url .
                        "?access_token=" . $args['token'];
                    $this->logger->debug("File upload URL: $upload_url", ['app' => Application::APP_ID]);
                    $upload_result = $upload_result &&
                        $this->_publisher->upload(
                            $upload_url,
                            $handle,
                            $size
                        );
                }

            }
            if (!$upload_result) {
                /*
                 * External error: during uploading file
                 */
                $this->logger->error(
                    'No upload result',
                    ['app' => Application::APP_ID]
                );
                $fcStatus->setErrorMessage("No upload result, please check your drafts, as it may be created anyway!");
                $fcStatus->setStatus(3);
                $this->_mapper->update($fcStatus);
                $notification->setSubject('no_upload_result', ['url' => $server->getPublishUrl(),]);
                return $notification;
            }

            $fcStatus->setStatus(0);//status = published
            $fcStatus->setUrl($create_result);
            $notification->setSubject('upload_successful', ['url' => $create_result]);
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
        } catch (MultipleObjectsReturnedException | DoesNotExistException | Exception $e) {
            $fcStatus?->setStatus(5);
            $fcStatus?->setErrorMessage("Internal Server Error!");
            $this->logger->error(
                $e->getMessage(),
                ['app' => Application::APP_ID]
            );
            $notification->setSubject('internal_error');
        }
        $this->_mapper->update($fcStatus);
        return $notification;
    }
}

