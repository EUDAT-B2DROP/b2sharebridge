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
use OCA\B2shareBridge\Exceptions\UploadNotificationException;
use OCA\B2shareBridge\Model\CommunityMapper;
use OCA\B2shareBridge\Model\DepositStatusMapper;
use OCA\B2shareBridge\Model\DepositFileMapper;
use OCA\B2shareBridge\Model\ServerMapper;
use OCA\B2shareBridge\Publish\B2ShareV2;
use OCA\B2shareBridge\Publish\B2ShareV3;
use OCA\B2shareBridge\Publish\B2ShareAPI;
use OCA\B2shareBridge\Util\Helper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\Notification\IManager;
use OCP\Files\IRootFolder;
use OCP\DB\Exception;
use OCP\Notification\INotification;
use PhpParser\Node\NullableType;
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
        ServerMapper $smapper = null,
        Communitymapper $cmapper = null,
        IManager $notManager = null,
        LoggerInterface $logger = null,
        IRootFolder $rootFolder = null,
    ) {
        parent::__construct($time);
        if ($dfmapper === null or $mapper === null or $smapper === null or $cmapper === null
            or $logger === null or $notManager === null or $rootFolder === null
        ) {
            $this->fixTransferForCron();
        } else {
            $this->_mapper = $mapper;
            $this->_dfmapper = $dfmapper;
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
        $this->_smapper = $application->getContainer()->get(ServerMapper::class);
        $this->_cmapper = $application->getContainer()->get(CommunityMapper::class);
        $this->notManager = $application->getContainer()->get(IManager::class);
        $this->logger = $application->getContainer()->get(LoggerInterface::class);
        $this->_rootFolder = $application->getContainer()->get(IRootFolder::class);
    }

    /**
     * Returns the B2Share API depending on the server
     *
     * @param mixed $server Server object
     * 
     * @throws \BadMethodCallException when the server has an unknown version
     * 
     * @return \OCA\B2shareBridge\Publish\B2ShareAPI B2Share API object
     */
    private function _getPublisher($server): B2ShareAPI
    {
        $application = new Application();
        if ($server->getVersion() == 2) {
            return $application->getContainer()->get(B2ShareV2::class);
        } else if ($server->getVersion() == 3) {
            return $application->getContainer()->get(B2ShareV3::class);
        }
        $version = $server->getVersion();
        throw new \BadMethodCallException("Unknown B2Share version v$version");
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
        // handle file uploads
        $notification = $this->_uploadFiles($args);
        $this->notManager->notify($notification);

        // TODO: remove old passed uploads
    }

    /**
     * Uploads files configured by args to B2SHARE using curl
     * 
     * @param mixed $args array of arguments, need to have the right list of fields
     * 
     * @throws \BadMethodCallException
     * 
     * @return INotification Returns a Notification
     */
    private function _uploadFiles($args): INotification
    {
        $this->_validateUploadParams($args);

        $mode = $args['mode'];
        $token = $args['token'];
        $transferId = $args['transferId'];
        $serverId = $args['server_id'];

        $fcStatus = null;
        $notification = $this->notManager->createNotification();
        $notification->setApp(Application::APP_ID)
            ->setDateTime(new \DateTime())
            ->setObject('b2sharebridge', $transferId);

        try {
            // get the file transfer object for current Cron
            $fcStatus = $this->_mapper->find($transferId);
            $fcStatus->setStatus(2); //status = processing
            $this->_mapper->update($fcStatus);
            $user = $fcStatus->getOwner();

            $notification->setUser($user);
            $server = $this->_smapper->find($serverId);

            $publisher = $this->_getPublisher($server);
            $publisher->setCheckSSL($server->getCheckSsl());

            // create draft or get file upload link
            $draftId = null;
            if ($mode == 'create') {
                $createResult = $this->_createNewDraft($args, $server, $fcStatus, $publisher);
                if (!$createResult) {
                    $this->logger->error(
                        'No upload result',
                        ['app' => Application::APP_ID]
                    );
                    $fcStatus->setErrorMessage("No upload result, please check your drafts, as it may be created anyway!");
                    $fcStatus->setStatus(3);
                    throw new UploadNotificationException('no_upload_result', ['url' => $server->getPublishUrl()]);    
                }

                $draftId = $createResult[0];
                $file_upload_link = $createResult[1];
            } else if ($mode == 'attach') {
                $draftId = $args['draftId'];
                $draft = $publisher->getDraft($server, $draftId, $token);
                $file_upload_link = $draft["links"]["files"];
            }

            // upload files
            $rootFolder = $this->_rootFolder->getUserFolder($user);
            $files = $this->_dfmapper->findAllForDeposit($fcStatus->getId());
            $uploadSuccess = true;

            $this->logger->warning("#upload files" . count($files), ['app' => Application::APP_ID]);

            foreach ($files as $file) {
                $fileid = $file->getFileid();

                $fileNodes = $rootFolder->getById($fileid);
                if (count($fileNodes) > 1) {
                    $this->logger->warning("Ambiguous upload, multiple files with same ID", ['app' => Application::APP_ID]);
                }
                foreach ($fileNodes as $fileNode) {
                    $uploadSuccess &= $this->_uploadFile($fileNode, $fcStatus, $file_upload_link, $token, $publisher);
                }

            }
            if (!$uploadSuccess) {
                /*
                 * External error: during uploading file
                 */
                $this->logger->error(
                    'No upload result',
                    ['app' => Application::APP_ID]
                );
                $fcStatus->setErrorMessage("No upload result, please check your drafts, as it may be created anyway!");
                $fcStatus->setStatus(3);
                throw new UploadNotificationException('no_upload_result', ['url' => $server->getPublishUrl()]);
            }

            $editDraftUrl = $publisher->getDraftUrl($server, $draftId);
            $fcStatus->setStatus(0);//status = published
            $fcStatus->setUrl($editDraftUrl);
            $notification->setSubject('upload_successful', ['url' => $editDraftUrl]);
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
        } catch (UploadNotificationException $e) {
            $notification->setSubject($e->getMessage(), $e->getSubjectParameters());
        }
        $this->_mapper->update($fcStatus);
        return $notification;
    }

    /**
     * Validate input parameters, throws if parameters are wrong
     *
     * @param array $args Array of parameters
     * 
     * @throws \BadMethodCallException
     * 
     * @return void
     */
    private function _validateUploadParams($args)
    {
        if (!Helper::arrayKeysExist(['transferId', 'token', 'server_id', 'mode', 'ids'], $args)) {
            $message = 'Can not handle w/o id, token, community, open_access, title';
            $this->logger->debug(print_r($args, true));
            $this->logger->error(
                $message,
                ['app' => Application::APP_ID]
            );
            throw new \BadMethodCallException($message);
        }

        $mode = $args['mode'];
        if ((!Helper::arrayKeysExist(['title', 'community', 'open_access'], $args) && $mode == 'create')
            || (!Helper::arrayKeysExist(['draftId'], $args) && $mode == 'attach')
        ) {
            $message = 'Missing parameters for mode';
            $this->logger->error(
                $message,
                ['app' => Application::APP_ID]
            );
            throw new \BadMethodCallException($message);
        }
    }

    /**
     * Summary of _createNewDraft
     *
     * @param array      $args      array of arguments
     * @param mixed      $server    server object
     * @param mixed      $fcStatus  Deposit status object
     * @param B2ShareAPI $publisher B2ShareAPI object
     * 
     * @throws UploadNotificationException
     * 
     * @return string          draftId
     */
    private function _createNewDraft($args, $server, $fcStatus, $publisher)
    {
        $draftId = $publisher->create(
            $args['token'],
            $args['community'],
            $args['open_access'],
            $args['title'],
            $server,
        );

        if ($draftId) {
            return $draftId;
        }

        /*
         * External error: during creating deposit
         */
        $fcStatus->setStatus(4);

        if (str_starts_with($publisher->getErrorMessage(), '403')) {
            $community = $this->_cmapper->find($args['community'], $server->getId());
            $unauthorized_message = 'You are not allowed to upload to "' . $community->getName() . '"';
            $this->logger->debug(
                $unauthorized_message,
                ['app' => Application::APP_ID]
            );
            $fcStatus->setErrorMessage($unauthorized_message);
            throw new UploadNotificationException('unauthorized', ['publisher_url' => $server->getPublishUrl(), 'community' => $community->getName()]);
        }

        $this->logger->error(
            "No create result, there was an error during deposit creation",
            ['app' => Application::APP_ID]
        );
        $fcStatus->setErrorMessage($publisher->getErrorMessage());
        throw new UploadNotificationException('external_error', ['publisher_url' => $server->getPublishUrl()]);
    }

    /**
     * Uploads a single file to the publisher
     *
     * @param \OCP\Files\Node $fileNode         file Node
     * @param mixed           $fcStatus         deposit status object
     * @param mixed           $file_upload_link (api) upload url for files
     * @param mixed           $token            b2share token of the
     * @param B2ShareAPI      $publisher        B2ShareAPI object
     * 
     * @throws UploadNotificationException
     * 
     * @return bool                              success of the upload
     */
    private function _uploadFile($fileNode, $fcStatus, $file_upload_link, $token, $publisher): bool
    {
        if ($fileNode->getType() != \OCP\Files\FileInfo::TYPE_FILE) {
            $this->logger->warning("User somehow managed to upload a folder" . get_class($fileNode), ['app' => Application::APP_ID]);
            return true; // ignore this error
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
            throw new UploadNotificationException('not_accessible', []);
        }

        $handle = $fileNode->fopen('rb');
        $size = $fileNode->getSize();

        $filenameEncoded = rawurlencode($filename);
        $upload_url = "$file_upload_link/$filenameEncoded?access_token=$token";
        $this->logger->debug("File upload URL: $upload_url", ['app' => Application::APP_ID]);
        return $publisher->upload(
            $upload_url,
            $handle,
            $size
        );
    }
}

