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
    private CommunityMapper $_cmapper;
    protected IManager $notManager;
    protected LoggerInterface $logger;

    /**
     * Create the database mapper
     *
     * @param ITimeFactory|null        $time
     * @param DepositStatusMapper|null $mapper    the database mapper for transfers
     * @param DepositFileMapper|null   $dfmapper  ORM for DepositFile
     * @param IPublish|null            $publisher publishing backend to use
     * @param ServerMapper|null        $smapper
     * @param CommunityMapper|null     $cmapper
     * @param LoggerInterface|null     $logger
     * @param IManager|null            $notManager
     */
    public function __construct(
        ITimeFactory $time = null,
        DepositStatusMapper $mapper = null,
        DepositFileMapper $dfmapper = null,
        IPublish $publisher = null,
        ServerMapper $smapper = null,
        Communitymapper $cmapper = null,
        IManager $notManager = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct($time);
        if (
            $dfmapper === null or $mapper === null or $publisher === null or $smapper === null or $cmapper === null
            or $logger === null or $notManager === null
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
        $this->_cmapper = $application->getContainer()->get(CommunityMapper::class);
        $this->notManager = $application->getContainer()->get(IManager::class);
        $this->logger = $application->getContainer()->get(LoggerInterface::class);
    }

    /**
     * Check if current user is the requested user
     *
     * @param array $args array of arguments
     */
    public function run($args)
    {
        if (
            !array_key_exists('transferId', $args)
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
                                $upload_url,
                                $handle,
                                $size
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
                        $notification->setSubject('not_accessible');
                    }
                }
                if ($upload_result) {
                    $fcStatus->setStatus(0);//status = published
                    $fcStatus->setUrl($create_result);
                    $notification->setSubject('upload_successful', ['url' => $create_result]);
                } else {
                    /*
                     * External error: during uploading file
                     */
                    $this->logger->error(
                        'No upload result',
                        ['app' => Application::APP_ID]
                    );
                    $fcStatus->setErrorMessage("No upload result, please check your drafts, as it may be created anyway!");
                    $fcStatus->setStatus(3);
                    $notification->setSubject('no_upload_result', ['url' => $server->getPublishUrl(),]);
                }
            } else {
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
        } catch (MultipleObjectsReturnedException | DoesNotExistException | Exception $e) {
            $fcStatus?->setStatus(5);
            $fcStatus?->setErrorMessage("Internal Server Error!");
            $this->logger->error(
                $e->getMessage(),
                ['app' => Application::APP_ID]
            );
            $notification->setSubject('internal_error');
        }
        $this->notManager->notify($notification);
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

